<?php

namespace App\Services;

use App\Models\Programme;
use App\Models\Result;
use App\Models\Semester;
use App\Models\Student;

/**
 * Pivots pending CA results into the same student x module wide-grid shape as the
 * upload template, for the approver to review before approving/rejecting a semester.
 */
class ResultApprovalGridBuilder
{
    /**
     * @return array{
     *     programme: Programme,
     *     nta_level: ?int,
     *     courses: \Illuminate\Support\Collection,
     *     rows: list<array{student: Student, cells: array<int, array|null>}>,
     *     summary: array{total: int, pass: int, fail: int},
     *     student_summary: array{total: int, pass: int, fail: int},
     *     module_summary: array<int, array{total: int, pass: int, fail: int}>
     * }
     */
    public function build(Semester $semester, Programme $programme, ?int $ntaLevel): array
    {
        $courses = ResultImportCourseQuery::forTemplate($semester, $programme, $ntaLevel);

        $students = Student::query()
            ->where('programme_id', $programme->id)
            ->when($ntaLevel, fn ($q) => $q->where('nta_level', $ntaLevel))
            ->where('status', 'active')
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('last_name')
            ->get();

        $results = Result::query()
            ->where('semester_id', $semester->id)
            ->where('status', 'pending_approval')
            ->whereIn('course_id', $courses->pluck('id'))
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy(fn ($r) => $r->student_id.'-'.$r->course_id);

        $summary = ['total' => 0, 'pass' => 0, 'fail' => 0];
        $studentSummary = ['total' => 0, 'pass' => 0, 'fail' => 0];
        $moduleSummary = [];
        foreach ($courses as $course) {
            $moduleSummary[$course->id] = ['total' => 0, 'pass' => 0, 'fail' => 0];
        }

        $rows = [];

        foreach ($students as $student) {
            $cells = [];
            $hasAnyResult = false;
            $studentFailed = false;

            foreach ($courses as $course) {
                $result = $results->get($student->id.'-'.$course->id);
                if (! $result) {
                    $cells[$course->id] = null;

                    continue;
                }

                $hasAnyResult = true;
                $remark = $result->caModuleRemark();
                $summary['total']++;
                $moduleSummary[$course->id]['total']++;
                if ($remark === 'FAIL') {
                    $summary['fail']++;
                    $moduleSummary[$course->id]['fail']++;
                    $studentFailed = true;
                } elseif ($remark === 'PASS') {
                    $summary['pass']++;
                    $moduleSummary[$course->id]['pass']++;
                }

                $cells[$course->id] = [
                    'theory' => $result->ca_theory,
                    'practical' => $result->ca_practical,
                    'ca' => $result->ca_mark,
                    'cellClass' => $result->caRemarkCellClass(),
                ];
            }

            if ($hasAnyResult) {
                $rows[] = ['student' => $student, 'cells' => $cells];
                $studentSummary['total']++;
                if ($studentFailed) {
                    $studentSummary['fail']++;
                } else {
                    $studentSummary['pass']++;
                }
            }
        }

        return [
            'programme' => $programme,
            'nta_level' => $ntaLevel,
            'courses' => $courses,
            'rows' => $rows,
            'summary' => $summary,
            'student_summary' => $studentSummary,
            'module_summary' => $moduleSummary,
        ];
    }
}
