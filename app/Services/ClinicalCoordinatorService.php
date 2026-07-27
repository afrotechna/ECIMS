<?php

namespace App\Services;

use App\Models\ClinicalLogbookEntry;
use App\Models\ClinicalRemediationPlan;
use App\Models\ClinicalRotationAttendanceMark;
use App\Models\ClinicalRotationRound;
use App\Models\Student;
use App\Support\ClinicalRotationCatalog;
use Illuminate\Support\Collection;

class ClinicalCoordinatorService
{
    public function __construct(
        private readonly ClinicalPlacementService $placements,
        private readonly ClinicalCompetencyService $competency,
    ) {}

    /**
     * @return array{
     *     round: ClinicalRotationRound|null,
     *     rounds: Collection,
     *     rows: Collection<int, array<string, mixed>>,
     *     stats: array<string, int>,
     * }
     */
    public function dashboard(?int $roundId = null): array
    {
        $rounds = ClinicalRotationRound::query()
            ->with('semester', 'programme')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $round = $roundId
            ? $rounds->firstWhere('id', $roundId) ?? ClinicalRotationRound::with('semester', 'programme')->find($roundId)
            : $rounds->first();

        if (! $round) {
            return [
                'round' => null,
                'rounds' => $rounds,
                'rows' => collect(),
                'stats' => ['students' => 0, 'alerts' => 0, 'pending_logbook' => 0, 'open_remediation' => 0],
            ];
        }

        $round->load('groups.students.programme');
        $semesterId = $round->semester_id;

        $rows = collect();
        foreach ($round->groups as $group) {
            foreach ($group->students as $student) {
                $placement = $this->placements->placementContext($student);
                $attendance = $this->placements->attendanceSummary($group, $student);
                $checklist = $this->competency->checklistForStudent($student, $semesterId);
                $metCount = collect($checklist)->where('met', true)->count();
                $reqCount = count($checklist);
                $pending = ClinicalLogbookEntry::where('student_id', $student->id)
                    ->where('semester_id', $semesterId)
                    ->where('status', ClinicalLogbookEntry::STATUS_SUBMITTED)
                    ->count();
                $openRemediation = ClinicalRemediationPlan::where('student_id', $student->id)
                    ->where('status', ClinicalRemediationPlan::STATUS_OPEN)
                    ->count();

                $alerts = [];
                if ($attendance['days_present'] < 3 && $attendance['has_record']) {
                    $alerts[] = 'Low attendance this week';
                }
                if (($placement['logbook_counts']['total'] ?? 0) === 0) {
                    $alerts[] = 'No logbook entries';
                }
                if ($pending > 0) {
                    $alerts[] = "{$pending} awaiting review";
                }
                if ($openRemediation > 0) {
                    $alerts[] = 'Open remediation';
                }
                if ($reqCount > 0 && $metCount < $reqCount) {
                    $alerts[] = 'Competency incomplete';
                }

                $rows->push([
                    'student' => $student,
                    'group' => $group,
                    'attendance' => $attendance,
                    'logbook' => $placement['logbook_counts'],
                    'competency_met' => $metCount,
                    'competency_total' => $reqCount,
                    'pending' => $pending,
                    'open_remediation' => $openRemediation,
                    'alerts' => $alerts,
                ]);
            }
        }

        return [
            'round' => $round,
            'rounds' => $rounds,
            'rows' => $rows->sortBy(fn ($r) => $r['student']->last_name),
            'stats' => [
                'students' => $rows->count(),
                'alerts' => $rows->filter(fn ($r) => count($r['alerts']) > 0)->count(),
                'pending_logbook' => $rows->sum('pending'),
                'open_remediation' => $rows->sum('open_remediation'),
            ],
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function siteSummary(?int $roundId = null): Collection
    {
        $data = $this->dashboard($roundId);
        $round = $data['round'];
        if (! $round) {
            return collect();
        }

        $bySite = [];
        foreach ($data['rows'] as $row) {
            $code = $row['group']->hospital_code ?: 'unassigned';
            $key = $row['group']->department_code.'|'.$code;
            if (! isset($bySite[$key])) {
                $bySite[$key] = [
                    'department' => ClinicalRotationCatalog::departmentLabel($row['group']->department_code),
                    'hospital' => ClinicalRotationCatalog::hospitalLabel($code) ?: 'Not assigned',
                    'students' => 0,
                    'alerts' => 0,
                ];
            }
            $bySite[$key]['students']++;
            if (count($row['alerts']) > 0) {
                $bySite[$key]['alerts']++;
            }
        }

        return collect(array_values($bySite))->sortBy('department');
    }

    /**
     * Students with attendance but no approved logbook (or vice versa) in current week.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function attendanceLogbookMismatches(?int $roundId = null): Collection
    {
        $data = $this->dashboard($roundId);
        $mismatches = collect();

        foreach ($data['rows'] as $row) {
            $present = $row['attendance']['days_present'] ?? 0;
            $approved = $row['logbook']['approved'] ?? 0;
            $total = $row['logbook']['total'] ?? 0;

            if ($present >= 3 && $approved === 0) {
                $mismatches->push(array_merge($row, ['issue' => 'Attending but no approved logbook entries']));
            } elseif ($present === 0 && $row['attendance']['has_record'] && $total > 0) {
                $mismatches->push(array_merge($row, ['issue' => 'Logbook activity but marked absent this week']));
            }
        }

        return $mismatches;
    }
}
