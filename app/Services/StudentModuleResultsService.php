<?php

namespace App\Services;

use App\Models\Result;
use App\Models\ResultSemesterSummary;
use App\Models\Semester;
use App\Models\Student;
use App\Support\GradingScale;
use Illuminate\Support\Collection;

class StudentModuleResultsService
{
    /**
     * @return Collection<int, array{
     *     academic_year: int,
     *     title: string,
     *     academic_year_label: string,
     *     summary: array{total_credits: ?float, total_grade_points: ?float, gpa: ?float, remarks: ?string},
     *     semesters: list<array{
     *         semester: Semester,
     *         title: string,
     *         academic_year_label: string,
     *         summary: ?ResultSemesterSummary,
     *         results: \Illuminate\Support\Collection<int, Result>
     *     }>
     * }>
     */
    public function yearSections(Student $student): Collection
    {
        $semesters = Semester::query()
            ->whereHas('results', fn ($q) => $q->where('student_id', $student->id)->approved())
            ->with(['results' => fn ($q) => $q->where('student_id', $student->id)->approved()->with('course')->orderBy('course_id')])
            ->orderBy('academic_year')
            ->orderBy('number')
            ->get();

        $summaries = ResultSemesterSummary::query()
            ->where('student_id', $student->id)
            ->whereIn('semester_id', $semesters->pluck('id'))
            ->get()
            ->keyBy('semester_id');

        $distinctYears = $semesters->pluck('academic_year')->unique()->sort()->values();
        $yearOrdinals = [];
        foreach ($distinctYears as $idx => $y) {
            $yearOrdinals[$y] = $this->ordinalYearOfStudy($idx + 1);
        }

        return $distinctYears->map(function (int $year) use ($semesters, $summaries, $yearOrdinals) {
            $yearSemesters = $semesters->where('academic_year', $year)->values();

            $semesterBlocks = $yearSemesters->map(function (Semester $semester) use ($summaries) {
                $periodLabel = match ((int) $semester->number) {
                    Semester::PERIOD_FIRST => 'Semester One',
                    Semester::PERIOD_SECOND => 'Semester Two',
                    default => 'Semester '.$semester->number,
                };

                $computed = GradingScale::summarize($semester->results);

                return [
                    'semester' => $semester,
                    'title' => $periodLabel,
                    'academic_year_label' => $semester->academicYearRange().' Academic Year',
                    'summary' => $summaries->get($semester->id),
                    'computed' => $computed,
                    'results' => $semester->results,
                ];
            })->values()->all();

            return [
                'academic_year' => $year,
                'title' => ($yearOrdinals[$year] ?? 'Year').' of Study',
                'academic_year_label' => $year.'/'.($year + 1).' Academic Year',
                'summary' => $this->yearSummary($yearSemesters, $summaries),
                'semesters' => $semesterBlocks,
            ];
        })->values();
    }

    /**
     * @param  Collection<int, Semester>  $yearSemesters
     * @param  Collection<int|string, ResultSemesterSummary>  $summaries
     * @return array{total_credits: ?float, total_grade_points: ?float, gpa: ?float, remarks: ?string}
     */
    private function yearSummary(Collection $yearSemesters, Collection $summaries): array
    {
        $results = $yearSemesters->flatMap(fn (Semester $s) => $s->results);
        $summary = GradingScale::summarize($results);

        $importedRemarks = $yearSemesters
            ->map(fn (Semester $s) => $summaries->get($s->id)?->academic_remarks)
            ->filter()
            ->last();

        if ($importedRemarks) {
            $summary['remarks'] = (string) $importedRemarks;
        }

        return $summary;
    }

    private function ordinalYearOfStudy(int $n): string
    {
        $suffix = match ($n % 10) {
            1 => $n % 100 === 11 ? 'th' : 'st',
            2 => $n % 100 === 12 ? 'th' : 'nd',
            3 => $n % 100 === 13 ? 'th' : 'rd',
            default => 'th',
        };

        return $n.$suffix;
    }
}
