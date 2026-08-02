<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Result;
use App\Models\ResultSemesterSummary;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * Imports CA or final rows from TMTB-style CSV (single or all-modules columns).
 */
class NactvetResultCsvImporter
{
    /** @var list<string> */
    private array $errors = [];

    private ?string $fileModuleCode = null;

    public function __construct(
        private readonly string $mode,
        private readonly Semester $semester
    ) {
        if (! in_array($mode, ['ca', 'final'], true)) {
            throw new \InvalidArgumentException('mode must be ca or final');
        }
    }

    /**
     * @return array{rows_touched: int, errors: list<string>}
     */
    public function import(string $absolutePath): array
    {
        $this->errors = [];
        $this->fileModuleCode = null;

        if (! is_readable($absolutePath)) {
            return ['rows_touched' => 0, 'errors' => ['File could not be read.']];
        }

        $parsed = $this->parseFile($absolutePath);
        if ($parsed['errors']) {
            return ['rows_touched' => 0, 'errors' => $parsed['errors']];
        }

        $this->fileModuleCode = $parsed['module_code'];
        $rowsTouched = 0;

        DB::transaction(function () use ($parsed, &$rowsTouched) {
            foreach ($parsed['rows'] as $row) {
                if ($parsed['wide_format']) {
                    $rowsTouched += $this->importWideRow($row, $parsed['fixed_headers'], $parsed['module_columns']);
                } else {
                    $assoc = [];
                    foreach ($parsed['fixed_headers'] as $colIdx => $name) {
                        $assoc[$name] = isset($row[$colIdx]) ? trim((string) $row[$colIdx]) : '';
                    }
                    if ($this->rowIsEmpty($assoc)) {
                        continue;
                    }
                    if ($this->importRow($assoc)) {
                        $rowsTouched++;
                    }
                }
            }
        });

        return ['rows_touched' => $rowsTouched, 'errors' => $this->errors];
    }

    /**
     * @param  array<int, string>  $fixedHeaders
     * @param  array<int, array{code: string, field: string}>  $moduleColumns
     */
    private function importWideRow(array $row, array $fixedHeaders, array $moduleColumns): int
    {
        $assoc = [];
        foreach ($fixedHeaders as $colIdx => $name) {
            if ($name === 'sn') {
                continue;
            }
            $assoc[$name] = isset($row[$colIdx]) ? trim((string) $row[$colIdx]) : '';
        }

        $nacte = $assoc['nactvet_reg_no'] ?? '';
        $examNo = $assoc['reg_no'] ?? '';
        if ($nacte === '' && $examNo === '' && ($assoc['candidate_name'] ?? '') === '') {
            return 0;
        }

        $student = $this->resolveStudent($nacte, $examNo);
        if (! $student) {
            $this->errors[] = 'No student found for: '.($nacte ?: $examNo ?: ($assoc['candidate_name'] ?? '?'));

            return 0;
        }

        $byModule = [];
        foreach ($moduleColumns as $colIdx => $mod) {
            $val = isset($row[$colIdx]) ? trim((string) $row[$colIdx]) : '';
            $code = $mod['code'];
            if (! isset($byModule[$code])) {
                $byModule[$code] = [];
            }
            $byModule[$code][$mod['field']] = $val;
        }

        $touched = 0;
        foreach ($byModule as $code => $marks) {
            if (! $this->marksHaveData($marks)) {
                continue;
            }
            if ($this->saveResult($student, $code, $marks, $assoc)) {
                $touched++;
            }
        }

        return $touched;
    }

    /** @param array<string, string> $marks */
    private function marksHaveData(array $marks): bool
    {
        foreach ($marks as $v) {
            if ($v !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, string>  $marks
     * @param  array<string, string>  $studentCols
     */
    private function saveResult(Student $student, string $code, array $marks, array $studentCols): bool
    {
        $course = $this->resolveCourse($student, $code);
        if (! $course) {
            $this->errors[] = "Student {$student->reg_no}: module {$code} not found for this semester.";

            return false;
        }

        $result = Result::firstOrNew([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'semester_id' => $this->semester->id,
        ]);

        if ($result->exists && $result->is_locked) {
            $this->errors[] = "{$student->reg_no} {$code}: locked; skipped.";

            return false;
        }

        if ($this->mode === 'ca') {
            if (isset($marks['theory']) && $marks['theory'] !== '') {
                $result->ca_theory = round((float) str_replace(',', '.', $marks['theory']), 2);
            }
            if (isset($marks['practical']) && $marks['practical'] !== '') {
                $result->ca_practical = round((float) str_replace(',', '.', $marks['practical']), 2);
            }
            if (isset($marks['ca']) && $marks['ca'] !== '') {
                $result->ca_mark = round((float) str_replace(',', '.', $marks['ca']), 2);

                // Theory column present on THIS row (current template): gate on the theory/
                // practical thresholds, since a no-practical module's CA(40%) is theory alone
                // and can't be judged by the generic percent-of-ca_max_mark check. Whether a
                // theory value happens to already be stored from an earlier import doesn't
                // count — only what this row's file actually supplied.
                $result->ca_eligibility = (isset($marks['theory']) && $marks['theory'] !== '')
                    ? $result->caEligibilityFromComponents()
                    : Result::caRemarkFromMark($result->ca_mark);
            }
        } else {
            if (isset($marks['ca']) && $marks['ca'] !== '') {
                $result->ca_mark = round((float) str_replace(',', '.', $marks['ca']), 2);
            }
            if (isset($marks['se']) && $marks['se'] !== '') {
                $result->exam_mark = round((float) str_replace(',', '.', $marks['se']), 2);
            }
            if (isset($marks['fscore']) && $marks['fscore'] !== '') {
                $result->total_mark = round((float) str_replace(',', '.', $marks['fscore']), 2);
            }
            if (isset($marks['grade']) && $marks['grade'] !== '') {
                $result->grade = substr(trim($marks['grade']), 0, 5);
            }
        }

        if ($this->mode !== 'ca') {
            $elig = $studentCols['current_status'] ?? '';
            if ($elig !== '' && ! in_array(strtoupper($elig), ['PASS', 'FAIL'], true)) {
                $result->ca_eligibility = substr($elig, 0, 80);
            }
        }

        $result->grade_source = 'nactvet';
        $result->status = 'pending_approval';
        $result->approved_by = null;
        $result->approved_at = null;
        $result->save();

        return true;
    }

    private function resolveStudent(string $nacte, string $examNo): ?Student
    {
        if ($nacte !== '') {
            $s = Student::where('nactvet_reg_no', $nacte)->first();
            if ($s) {
                return $s;
            }
        }
        if ($examNo !== '') {
            return Student::where('reg_no', $examNo)
                ->orWhere('nactvet_reg_no', $examNo)
                ->first();
        }

        return null;
    }

    private function resolveCourse(Student $student, string $code): ?Course
    {
        $normalized = strtolower(str_replace(' ', '', $code));

        $course = Course::where('programme_id', $student->programme_id)
            ->whereRaw("LOWER(REPLACE(code, ' ', '')) = ?", [$normalized])
            ->whereHas('semesters', fn ($q) => $q->where('semesters.id', $this->semester->id))
            ->first();

        if ($course) {
            return $course;
        }

        return Course::where('programme_id', $student->programme_id)
            ->whereRaw('LOWER(code) = ?', [strtolower($code)])
            ->whereHas('semesters', fn ($q) => $q->where('semesters.id', $this->semester->id))
            ->first();
    }

    /**
     * @return array{
     *     module_code: ?string,
     *     wide_format: bool,
     *     fixed_headers: array<int, string>,
     *     module_columns: array<int, array{code: string, field: string}>,
     *     rows: list<array<int, string|null>>,
     *     errors: list<string>
     * }
     */
    private function parseFile(string $absolutePath): array
    {
        if (NactvetSpreadsheetXmlReader::isSpreadsheetXml($absolutePath)) {
            $allRows = NactvetSpreadsheetXmlReader::rowsFromFile($absolutePath);
            if ($allRows === []) {
                return [
                    'module_code' => null,
                    'wide_format' => false,
                    'fixed_headers' => [],
                    'module_columns' => [],
                    'rows' => [],
                    'errors' => ['Could not read Excel file or sheet is empty.'],
                ];
            }

            return $this->parseRows($allRows);
        }

        $fh = new \SplFileObject($absolutePath, 'r');
        $fh->setFlags(\SplFileObject::READ_CSV | \SplFileObject::DROP_NEW_LINE);

        $allRows = [];
        while (! $fh->eof()) {
            $row = $fh->fgetcsv();
            if ($row === false) {
                continue;
            }
            if ($row === [null] || (count($row) === 1 && ($row[0] === null || $row[0] === ''))) {
                $allRows[] = [];

                continue;
            }
            if (isset($row[0]) && is_string($row[0]) && str_starts_with($row[0], "\xEF\xBB\xBF")) {
                $row[0] = substr($row[0], 3);
            }
            $allRows[] = $row;
        }

        return $this->parseRows($allRows);
    }

    /**
     * @param  list<list<string|null>>  $allRows
     * @return array{
     *     module_code: ?string,
     *     wide_format: bool,
     *     fixed_headers: array<int, string>,
     *     module_columns: array<int, array{code: string, field: string}>,
     *     rows: list<array<int, string|null>>,
     *     errors: list<string>
     * }
     */
    private function parseRows(array $allRows): array
    {

        $moduleCode = null;
        $headerIndex = null;
        $fixedHeaders = [];
        $moduleColumns = [];

        foreach ($allRows as $i => $row) {
            $code = NactvetExamResultsSheet::extractModuleCodeFromRow($row);
            if ($code !== null) {
                $moduleCode = $code;
            }
            if (NactvetExamResultsSheet::isDataHeaderRow($row)) {
                $headerIndex = $i;
                foreach ($row as $colIdx => $cell) {
                    $mod = NactvetExamResultsSheet::parseModuleColumnHeader((string) $cell);
                    if ($mod !== null) {
                        $moduleColumns[$colIdx] = $mod;
                    } else {
                        $key = NactvetExamResultsSheet::normalizeHeaderKey((string) $cell);
                        if ($key !== '') {
                            $fixedHeaders[$colIdx] = $key;
                        } elseif (NactvetExamResultsSheet::looksLikeModuleCode((string) $cell)) {
                            $moduleColumns[$colIdx] = [
                                'code' => NactvetExamResultsSheet::moduleColumnLabel((string) $cell),
                                'field' => 'ca',
                            ];
                        }
                    }
                }
                break;
            }
        }

        if ($headerIndex === null) {
            $legacy = $this->parseLegacyFlatCsv($allRows);

            return [
                'module_code' => $legacy['module_code'],
                'wide_format' => false,
                'fixed_headers' => $legacy['headers'],
                'module_columns' => [],
                'rows' => $legacy['rows'],
                'errors' => $legacy['errors'],
            ];
        }

        $wideFormat = count($moduleColumns) > 0;

        return [
            'module_code' => $moduleCode,
            'wide_format' => $wideFormat,
            'fixed_headers' => $fixedHeaders,
            'module_columns' => $moduleColumns,
            'rows' => array_slice($allRows, $headerIndex + 1),
            'errors' => [],
        ];
    }

    /**
     * @param  list<array<int, string|null>>  $allRows
     * @return array{module_code: ?string, headers: array<int, string>, rows: list<array<int, string|null>>, errors: list<string>}
     */
    private function parseLegacyFlatCsv(array $allRows): array
    {
        if ($allRows === []) {
            return ['module_code' => null, 'headers' => [], 'rows' => [], 'errors' => ['Empty file.']];
        }

        $headerRow = null;
        foreach ($allRows as $row) {
            if (count(array_filter($row, fn ($c) => $c !== null && $c !== '')) > 0) {
                $headerRow = $row;
                break;
            }
        }

        if ($headerRow === null) {
            return ['module_code' => null, 'headers' => [], 'rows' => [], 'errors' => ['Missing header row.']];
        }

        $headers = [];
        foreach ($headerRow as $i => $cell) {
            $key = $this->normalizeHeader((string) $cell);
            if ($key !== '' && $key !== 'sn') {
                $headers[$i] = $key;
            }
        }

        $dataStart = array_search($headerRow, $allRows, true);
        $dataRows = $dataStart === false ? [] : array_slice($allRows, (int) $dataStart + 1);

        return ['module_code' => null, 'headers' => $headers, 'rows' => $dataRows, 'errors' => []];
    }

    private function normalizeHeader(string $h): string
    {
        $sheet = NactvetExamResultsSheet::normalizeHeaderKey($h);
        if ($sheet !== '') {
            return $this->legacyNormalizeAlias($sheet);
        }

        return $this->legacyNormalizeAlias(
            strtolower(trim(preg_replace('/\s+/', '_', $h) ?? $h))
        );
    }

    private function legacyNormalizeAlias(string $h): string
    {
        return match ($h) {
            'reg_no', 'regno', 'registration_no', 'registration', 'student_reg', 'reg' => 'reg_no',
            'nactvet', 'nactvet_reg', 'nactvet_reg_no', 'nactvet_registration', 'nacte_registration_number' => 'nactvet_reg_no',
            'examination_number', 'exam_number' => 'reg_no',
            'course_code', 'code', 'module_code', 'subject_code' => 'course_code',
            'course_name', 'module_name', 'subject' => 'course_name',
            'ca', 'ca_marks', 'ca_mark', 'continuous', 'continuous_assessment', 'avca', 'avca_40' => 'ca',
            'th_comp', 'theory', 'theory_comp', 'theory_average', 'ca_theory' => 'theory',
            'ospe', 'osce', 'practical', 'clinical', 'ca_practical' => 'practical',
            'se', 'se_marks', 'exam', 'semester_exam', 'semester_examination', 'final_exam', 'aves', 'aves_60' => 'se',
            'fscore', 'final_score', 'final', 'total', 'final_mark' => 'fscore',
            'grade', 'letter_grade' => 'grade',
            'eligibility', 'eligible', 'ca_eligibility', 'ca_status', 'current_status' => 'current_status',
            'gpa', 'sgpa', 'semester_gpa' => 'gpa',
            'remarks', 'academic_remarks', 'status', 'decision', 'semester_remarks' => 'remarks',
            'candidate_name', 'sex', 'sit_status' => 'candidate_name',
            default => $h,
        };
    }

    /** @param array<string, string> $assoc */
    private function rowIsEmpty(array $assoc): bool
    {
        unset($assoc['candidate_name'], $assoc['sex'], $assoc['sit_status']);
        foreach ($assoc as $v) {
            if ($v !== '') {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, string> $assoc */
    private function importRow(array $assoc): bool
    {
        $nacte = $assoc['nactvet_reg_no'] ?? '';
        $examNo = $assoc['reg_no'] ?? '';

        $student = $this->resolveStudent($nacte, $examNo);
        if (! $student) {
            $this->errors[] = 'No student found for: '.($nacte ?: $examNo);

            return false;
        }

        $code = trim($assoc['course_code'] ?? '') ?: trim($this->fileModuleCode ?? '');
        if ($code === '') {
            $this->errors[] = "Row {$examNo}{$nacte}: missing module code.";

            return false;
        }

        $marks = [];
        if (isset($assoc['ca'])) {
            $marks['ca'] = $assoc['ca'];
        }
        if (isset($assoc['theory'])) {
            $marks['theory'] = $assoc['theory'];
        }
        if (isset($assoc['practical'])) {
            $marks['practical'] = $assoc['practical'];
        }
        if (isset($assoc['se'])) {
            $marks['se'] = $assoc['se'];
        }
        if (isset($assoc['fscore'])) {
            $marks['fscore'] = $assoc['fscore'];
        }
        if (isset($assoc['grade'])) {
            $marks['grade'] = $assoc['grade'];
        }

        return $this->saveResult($student, $code, $marks, $assoc);
    }
}
