<?php

namespace App\Support;

use App\Models\ClinicalRotationGroup;
use App\Models\ClinicalRotationRound;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Student posting roster for every Mon–Fri week in the NTA rotation cycle.
 */
final class ClinicalRotationFullRoster
{
    /**
     * @return array{
     *     round: ClinicalRotationRound,
     *     nta_level: int,
     *     weeks_per_block: int,
     *     total_weeks: int,
     *     start_monday: Carbon,
     *     legend: array<string, string>,
     *     weeks: list<array{
     *         week_label: string,
     *         date_range: string,
     *         week_monday: Carbon,
     *         week_friday: Carbon,
     *         groups: list<array{
     *             slot: int,
     *             name: string,
     *             department_abbr: string,
     *             department: string,
     *             hospital: string,
     *             students: Collection<int, \App\Models\Student>
     *         }>
     *     }>
     * }
     */
    public static function build(
        ClinicalRotationRound $round,
        Carbon $startMonday,
        int $weeksPerBlock,
    ): array {
        if (! in_array((int) $round->nta_level, [4, 5, 6], true)) {
            throw new \InvalidArgumentException('Full roster is only available for NTA levels 4, 5, and 6.');
        }

        $round->loadMissing(['semester', 'programme', 'groups.students']);
        $schedule = ClinicalRotationScheduleTemplate::documentPayload($startMonday, (int) $round->nta_level, $weeksPerBlock);
        /** @var array<int, ClinicalRotationGroup> $groupsBySlot */
        $groupsBySlot = $round->groups->keyBy('slot_number')->all();
        $legend = $schedule['legend'];

        $weeks = [];
        foreach ($schedule['block_rows'] as $gw => $row) {
            $weekMonday = $startMonday->copy()->addWeeks($gw);
            $weekFriday = $weekMonday->copy()->addDays(4);
            $groupRows = [];

            foreach ($row['cells'] as $gi => $abbr) {
                $slot = $gi + 1;
                /** @var ClinicalRotationGroup|null $group */
                $group = $groupsBySlot[$slot] ?? null;
                $groupRows[] = [
                    'slot' => $slot,
                    'name' => $group?->name ?? 'Rotation Group '.$slot,
                    'department_abbr' => $abbr,
                    'department' => $legend[$abbr] ?? $abbr,
                    'hospital' => ClinicalRotationCatalog::hospitalLabel($group?->hospital_code),
                    'students' => $group?->students ?? collect(),
                ];
            }

            $weeks[] = [
                'week_label' => $row['week_label'],
                'date_range' => $row['date_range'],
                'week_monday' => $weekMonday,
                'week_friday' => $weekFriday,
                'groups' => $groupRows,
            ];
        }

        return [
            'round' => $round,
            'nta_level' => (int) $round->nta_level,
            'weeks_per_block' => $weeksPerBlock,
            'total_weeks' => (int) $schedule['total_weeks'],
            'start_monday' => $schedule['start_monday'],
            'legend' => $legend,
            'weeks' => $weeks,
        ];
    }
}
