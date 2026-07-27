<?php

namespace App\Services;

use App\Models\Programme;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * TMTB-style CSV: one file per programme/semester with a column per module (AVCA, etc.).
 */
class ResultImportCsvTemplate
{
    public function downloadCa(Semester $semester, Programme $programme, ?int $ntaLevel): StreamedResponse
    {
        $courses = ResultImportCourseQuery::forTemplate($semester, $programme, $ntaLevel);
        $rows = $this->buildWideSheet($semester, $programme, $courses, $ntaLevel, 'ca', 'CONTINUOUS ASSESSMENT (CA) RESULTS', false);
        $filename = $this->filename('ca', $semester, $programme, $ntaLevel);

        return $this->stream($filename, $rows);
    }

    public function downloadFinal(Semester $semester, Programme $programme, ?int $ntaLevel): StreamedResponse
    {
        $courses = ResultImportCourseQuery::forTemplate($semester, $programme, $ntaLevel);
        $rows = $this->buildWideSheet($semester, $programme, $courses, $ntaLevel, 'final', 'EXAMINATION RESULTS', false);
        $filename = $this->filename('final', $semester, $programme, $ntaLevel);

        return $this->stream($filename, $rows);
    }

    public function downloadCaDemoExcel(
        Semester $semester,
        Programme $programme,
        ?int $ntaLevel,
        ResultImportSpreadsheetExport $export
    ): StreamedResponse {
        $courses = ResultImportCourseQuery::forTemplate($semester, $programme, $ntaLevel);
        $rows = $this->buildWideSheet($semester, $programme, $courses, $ntaLevel, 'ca', 'CONTINUOUS ASSESSMENT (CA) RESULTS', true);
        $filename = $this->excelFilename('ca-demo', $semester, $programme, $ntaLevel);

        return $export->download($filename, $rows, 'CA Results');
    }

    public function downloadFinalDemoExcel(
        Semester $semester,
        Programme $programme,
        ?int $ntaLevel,
        ResultImportSpreadsheetExport $export
    ): StreamedResponse {
        $courses = ResultImportCourseQuery::forTemplate($semester, $programme, $ntaLevel);
        $rows = $this->buildWideSheet($semester, $programme, $courses, $ntaLevel, 'final', 'EXAMINATION RESULTS', true);
        $filename = $this->excelFilename('final-demo', $semester, $programme, $ntaLevel);

        return $export->download($filename, $rows, 'Final Results');
    }

    /**
     * @return list<list<string>>
     */
    public function demoRowsForCa(Semester $semester, Programme $programme, ?int $ntaLevel): array
    {
        $courses = ResultImportCourseQuery::forTemplate($semester, $programme, $ntaLevel);

        return $this->buildWideSheet($semester, $programme, $courses, $ntaLevel, 'ca', 'CONTINUOUS ASSESSMENT (CA) RESULTS', true);
    }

    /**
     * @return list<list<string>>
     */
    public function demoRowsForFinal(Semester $semester, Programme $programme, ?int $ntaLevel): array
    {
        $courses = ResultImportCourseQuery::forTemplate($semester, $programme, $ntaLevel);

        return $this->buildWideSheet($semester, $programme, $courses, $ntaLevel, 'final', 'EXAMINATION RESULTS', true);
    }

    /**
     * @param  Collection<int, \App\Models\Course>  $courses
     * @return list<list<string>>
     */
    private function buildWideSheet(
        Semester $semester,
        Programme $programme,
        Collection $courses,
        ?int $ntaLevel,
        string $mode,
        string $examTitle,
        bool $withDemoMarks = false
    ): array {
        $nta = $ntaLevel ?? (int) ($courses->first()?->resolvedNtaLevel() ?? 4);
        $qualification = $this->qualificationLine($programme);
        $yearLabel = $semester->academicYearRange();
        $semesterLabel = $semester->periodName();

        if ($mode === 'ca' && ! $withDemoMarks) {
            $rows = [NactvetExamResultsSheet::wideDataHeaderRow($courses, 'ca')];
        } else {
            $rows = NactvetExamResultsSheet::headerBlock(
                $qualification,
                $nta,
                $semesterLabel,
                $examTitle,
                config('college.institution_name', config('app.name')),
                $yearLabel
            );
            $rows[] = ['PROGRAMME:', strtoupper($programme->name.' ('.$programme->code.')')];
            $rows[] = ['MODULES:', $courses->pluck('code')->map(fn ($c) => NactvetExamResultsSheet::moduleColumnLabel($c))->implode(', ')];
            $rows[] = [];
            $rows[] = NactvetExamResultsSheet::wideDataHeaderRow($courses, $mode);
        }

        $students = Student::query()
            ->where('programme_id', $programme->id)
            ->where('status', 'active')
            ->when($ntaLevel, fn ($q) => $q->where('nta_level', $ntaLevel))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $sn = 1;
        foreach ($students as $student) {
            $row = [
                (string) $sn,
                strtoupper($student->full_name),
                strtoupper(substr((string) ($student->gender ?? ''), 0, 1)) ?: '',
                (string) ($student->nactvet_reg_no ?? ''),
                $mode === 'ca' ? '' : (string) ($student->reg_no ?? ''),
                'ACTIVE',
                'FIRST SITTING',
            ];

            foreach ($courses as $courseIndex => $course) {
                if ($mode === 'ca') {
                    $row[] = $withDemoMarks
                        ? (string) ResultImportDemoData::caMark($sn - 1, $courseIndex)
                        : '';
                } else {
                    if ($withDemoMarks) {
                        $marks = ResultImportDemoData::finalMarks($sn - 1, $courseIndex);
                        $row[] = (string) $marks['ca'];
                        $row[] = (string) $marks['se'];
                        $row[] = (string) $marks['fscore'];
                        $row[] = $marks['grade'];
                    } else {
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                    }
                }
            }

            $rows[] = $row;
            $sn++;
        }

        if ($sn === 1) {
            $example = ['1', 'EXAMPLE CANDIDATE', 'M', 'NACTE000000', $mode === 'ca' ? '' : 'EXAM0001', 'ACTIVE', 'FIRST SITTING'];
            foreach ($courses as $courseIndex => $course) {
                if ($mode === 'ca') {
                    $example[] = $withDemoMarks
                        ? (string) ResultImportDemoData::caMark(0, $courseIndex)
                        : '';
                } else {
                    if ($withDemoMarks) {
                        $marks = ResultImportDemoData::finalMarks(0, $courseIndex);
                        array_push($example, (string) $marks['ca'], (string) $marks['se'], (string) $marks['fscore'], $marks['grade']);
                    } else {
                        array_push($example, '', '', '', '');
                    }
                }
            }
            $rows[] = $example;
        }

        return $rows;
    }

    private function qualificationLine(Programme $programme): string
    {
        $level = strtoupper(trim((string) ($programme->level ?? '')));
        $name = strtoupper(trim((string) $programme->name));

        if ($level !== '' && $name !== '') {
            return $level.' IN '.$name;
        }

        return $name ?: $level ?: strtoupper($programme->code);
    }

    private function filename(string $type, Semester $semester, Programme $programme, ?int $ntaLevel): string
    {
        $parts = [
            $type.'-results-all-modules',
            'sem'.$semester->number,
            $semester->academic_year,
            strtolower($programme->code ?? 'prog'),
        ];
        if ($ntaLevel) {
            $parts[] = 'nta'.$ntaLevel;
        }

        return implode('-', $parts).'.csv';
    }

    private function excelFilename(string $type, Semester $semester, Programme $programme, ?int $ntaLevel): string
    {
        $parts = [
            $type,
            'sem'.$semester->number,
            $semester->academic_year,
            strtolower($programme->code ?? 'prog'),
        ];
        if ($ntaLevel) {
            $parts[] = 'nta'.$ntaLevel;
        }

        return implode('-', $parts).'.xls';
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function stream(string $filename, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
