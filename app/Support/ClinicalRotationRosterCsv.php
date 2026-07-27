<?php

namespace App\Support;

use App\Models\ClinicalRotationRound;
use Illuminate\Support\Collection;

/**
 * CSV layout aligned with print roster: banner row (Group — Department | Hospital), then student rows.
 */
final class ClinicalRotationRosterCsv
{
    /**
     * @param  resource  $out
     */
    public static function writeBom($out): void
    {
        fwrite($out, "\xEF\xBB\xBF");
    }

    /**
     * @param  resource  $out
     */
    public static function writeGroupBanner($out, int $slot, string $department, string $hospital, ?string $departmentAbbr = null): void
    {
        $label = 'Group '.$slot.' — '.$department;
        if ($departmentAbbr !== null && $departmentAbbr !== '' && $departmentAbbr !== $department) {
            $label .= ' ('.$departmentAbbr.')';
        }
        fputcsv($out, [$label, 'Hospital: '.$hospital]);
    }

    /**
     * @param  resource  $out
     */
    public static function writeStudentHeaderRow($out): void
    {
        fputcsv($out, ['SN', 'Student name', 'Registration no.']);
    }

    /**
     * @param  resource  $out
     * @param  Collection<int, \App\Models\Student>|iterable  $students
     */
    public static function writeStudentRows($out, iterable $students): void
    {
        $i = 0;
        $hasAny = false;
        foreach ($students as $stu) {
            $hasAny = true;
            $i++;
            fputcsv($out, [
                $i,
                $stu->full_name,
                $stu->registrationNumberDisplay(),
            ]);
        }
        if (! $hasAny) {
            fputcsv($out, ['', '(no students)', '']);
        }
    }

    /**
     * @param  resource  $out
     */
    public static function writeBlankRow($out): void
    {
        fputcsv($out, []);
    }

    /**
     * @param  resource  $out
     */
    public static function writeRoundPostingRoster($out, ClinicalRotationRound $round): void
    {
        $round->loadMissing(['programme', 'semester', 'groups.students']);

        fputcsv($out, ['Clinical rotation posting roster']);
        fputcsv($out, [
            $round->programme?->name.' ('.$round->programme?->code.')',
            'NTA Level '.$round->nta_level,
        ]);
        if ($round->semester) {
            fputcsv($out, ['Semester', $round->semester->label]);
        }
        if ($round->rotation_week_monday && $round->rotation_week_friday) {
            fputcsv($out, [
                'Posting week',
                $round->rotation_week_monday->format('d M Y').' – '.$round->rotation_week_friday->format('d M Y'),
            ]);
        }
        self::writeBlankRow($out);

        foreach ($round->groups->sortBy('slot_number') as $group) {
            $dept = ClinicalRotationCatalog::departmentLabel($group->department_code);
            $hosp = ClinicalRotationCatalog::hospitalLabel($group->hospital_code);
            self::writeGroupBanner($out, (int) $group->slot_number, $dept, $hosp);
            self::writeStudentHeaderRow($out);
            self::writeStudentRows($out, $group->students);
            self::writeBlankRow($out);
        }
    }

    /**
     * @param  resource  $out
     * @param  array $roster from ClinicalRotationFullRoster::build()
     */
    public static function writeFullWeeksRoster($out, array $roster): void
    {
        $round = $roster['round'];

        fputcsv($out, ['Clinical rotation roster — all weeks']);
        fputcsv($out, [
            $round->programme?->name.' ('.$round->programme?->code.')',
            'NTA Level '.$roster['nta_level'],
        ]);
        fputcsv($out, [
            'Rotation start',
            $roster['start_monday']->format('l, d M Y'),
        ]);
        fputcsv($out, [
            'Total weeks',
            (string) $roster['total_weeks'].' ('.$roster['weeks_per_block'].' week'.($roster['weeks_per_block'] > 1 ? 's' : '').' per department)',
        ]);
        self::writeBlankRow($out);

        foreach ($roster['weeks'] as $week) {
            fputcsv($out, [$week['week_label'].' — '.$week['date_range'], 'Week starting: '.$week['week_monday']->format('Y-m-d')]);
            self::writeBlankRow($out);

            foreach ($week['groups'] as $group) {
                self::writeGroupBanner(
                    $out,
                    (int) $group['slot'],
                    $group['department'],
                    $group['hospital'],
                    $group['department_abbr'] ?? null,
                );
                self::writeStudentHeaderRow($out);
                self::writeStudentRows($out, $group['students']);
                self::writeBlankRow($out);
            }
        }
    }
}
