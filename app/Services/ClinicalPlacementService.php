<?php

namespace App\Services;

use App\Models\ClinicalLogbookEntry;
use App\Models\ClinicalRotationAttendanceMark;
use App\Models\ClinicalRotationGroup;
use App\Models\Semester;
use App\Models\Student;
use App\Support\ClinicalRotationCatalog;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClinicalPlacementService
{
    /**
     * @return array{
     *     placements: Collection<int, ClinicalRotationGroup>,
     *     primary: ClinicalRotationGroup|null,
     *     semester: Semester|null,
     *     attendance_summary: array<string, mixed>|null,
     *     logbook_counts: array<string, int>,
     * }
     */
    public function placementContext(Student $student): array
    {
        $student->loadMissing(['programme', 'clinicalRotationGroups.round.semester', 'clinicalRotationGroups.round.programme']);

        $placements = $student->clinicalRotationGroups
            ->filter(fn (ClinicalRotationGroup $g) => $g->round !== null)
            ->sortByDesc(fn (ClinicalRotationGroup $g) => $g->round->semester?->academic_year ?? 0)
            ->values();

        $primary = $placements->first();
        $semester = $primary?->round?->semester;

        $logbookCounts = [
            'total' => 0,
            'approved' => 0,
            'pending' => 0,
            'draft' => 0,
        ];

        if ($semester) {
            $entries = ClinicalLogbookEntry::query()
                ->where('student_id', $student->id)
                ->where('semester_id', $semester->id)
                ->get();
            $logbookCounts['total'] = $entries->count();
            $logbookCounts['approved'] = $entries->where('status', ClinicalLogbookEntry::STATUS_APPROVED)->count();
            $logbookCounts['pending'] = $entries->where('status', ClinicalLogbookEntry::STATUS_SUBMITTED)->count();
            $logbookCounts['draft'] = $entries->where('status', ClinicalLogbookEntry::STATUS_DRAFT)->count();
        }

        return [
            'placements' => $placements,
            'primary' => $primary,
            'semester' => $semester,
            'attendance_summary' => $primary ? $this->attendanceSummary($primary, $student) : null,
            'logbook_counts' => $logbookCounts,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attendanceSummary(ClinicalRotationGroup $group, Student $student): array
    {
        $round = $group->round;
        $weekStart = $round?->rotation_week_monday
            ? Carbon::parse($round->rotation_week_monday)->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $mark = ClinicalRotationAttendanceMark::query()
            ->where('clinical_rotation_group_id', $group->id)
            ->where('student_id', $student->id)
            ->where('week_starting', $weekStart->toDateString())
            ->first();

        $days = ['mon', 'tue', 'wed', 'thu', 'fri'];
        $present = 0;
        foreach ($days as $d) {
            if ($mark && $mark->{$d.'_present'} === true) {
                $present++;
            }
        }

        return [
            'week_start' => $weekStart,
            'week_label' => $weekStart->format('j M Y').' – '.$weekStart->copy()->addDays(4)->format('j M Y'),
            'days_present' => $present,
            'days_total' => 5,
            'has_record' => $mark !== null,
            'department' => ClinicalRotationCatalog::departmentLabel($group->department_code),
            'hospital' => ClinicalRotationCatalog::hospitalLabel($group->hospital_code),
            'group_name' => $group->name,
            'round_title' => $round?->title,
            'nta_level' => $round?->nta_level,
        ];
    }

    public function resolvePlacementGroup(Student $student, ?int $groupId = null): ?ClinicalRotationGroup
    {
        $ctx = $this->placementContext($student);
        if ($groupId) {
            return $ctx['placements']->firstWhere('id', $groupId);
        }

        return $ctx['primary'];
    }
}
