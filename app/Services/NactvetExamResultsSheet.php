<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * TMTB / NACTE examination results sheet layout.
 */
class NactvetExamResultsSheet
{
    /** @var list<string> */
    public const FIXED_STUDENT_HEADERS = [
        'SN',
        'CANDIDATE NAME',
        'SEX (M/F)',
        'NACTE REGISTRATION NUMBER',
        'EXAMINATION NUMBER',
        'CURRENT STATUS',
        'SIT STATUS',
    ];

    /** Single-module sheet columns (legacy). */
    /** @var list<string> */
    public const SINGLE_MODULE_HEADERS = [
        'SN',
        'CANDIDATE NAME',
        'SEX (M/F)',
        'NACTE REGISTRATION NUMBER',
        'EXAMINATION NUMBER',
        'CURRENT STATUS',
        'SIT STATUS',
        'AVCA (40%)',
        'AVES (60%)',
        'FSCORE',
        'GRADE',
    ];

    /**
     * @return list<list<string>>
     */
    public static function headerBlock(
        string $qualificationLine,
        int $ntaLevel,
        string $semesterLabel,
        string $examTitle,
        string $institutionName,
        string $academicYearLabel
    ): array {
        return [
            ['THE UNITED REPUBLIC OF TANZANIA'],
            ['MINISTRY OF HEALTH'],
            [config('college.results_board', 'TANGANYIKA MEDICAL TRAINING BOARD')],
            [strtoupper($qualificationLine)],
            ['NTA LEVEL '.$ntaLevel],
            [strtoupper($semesterLabel.' '.$examTitle)],
            ['INSTITUTION NAME:', $institutionName],
            ['YEAR:', $academicYearLabel],
            [],
        ];
    }

    /**
     * @return list<list<string>>
     */
    public static function moduleBlock(string $credits, string $moduleCode, string $moduleName): array
    {
        return [
            ['MODULE CREDITS:', (string) $credits],
            ['MODULE CODE:', $moduleCode],
            ['MODULE NAME:', strtoupper($moduleName)],
            [],
        ];
    }

    /**
     * @param  Collection<int, \App\Models\Course>  $courses
     * @return list<string>
     */
    public static function wideDataHeaderRow(Collection $courses, string $mode = 'ca'): array
    {
        $headers = self::FIXED_STUDENT_HEADERS;

        foreach ($courses as $course) {
            $code = self::moduleColumnLabel($course->code);
            if ($mode === 'ca') {
                $headers[] = $code.' TH COMP';
                if (! empty($course->has_practical)) {
                    $headers[] = $code.' '.self::practicalHeaderLabel($course);
                }
                $headers[] = $code.' AVCA (40%)';
            } else {
                $headers[] = $code.' AVCA (40%)';
                $headers[] = $code.' AVES (60%)';
                $headers[] = $code.' FSCORE';
                $headers[] = $code.' GRADE';
            }
        }

        return $headers;
    }

    /** Header label for a module's practical/skills column (OSPE, OSCE, PRACTICAL, or CLINICAL). */
    public static function practicalHeaderLabel(\App\Models\Course $course): string
    {
        if (! empty($course->requires_clinical_rotation)) {
            return 'CLINICAL';
        }

        return strtoupper($course->practicalColumnLabel()) ?: 'PRACTICAL';
    }

    /** Module column header e.g. CMT04101 (no spaces). */
    public static function moduleColumnLabel(string $courseCode): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($courseCode)) ?? trim($courseCode));
    }

    public static function looksLikeModuleCode(string $header): bool
    {
        $h = self::moduleColumnLabel($header);
        if ($h === '' || strlen($h) < 5) {
            return false;
        }

        return (bool) preg_match('/^[A-Z]{2,8}\d{3,6}[A-Z0-9]*$/', $h);
    }

    /**
     * @return array{code: string, field: string}|null  field: ca|se|fscore|grade
     */
    public static function parseModuleColumnHeader(string $header): ?array
    {
        $h = trim($header);
        if ($h === '') {
            return null;
        }

        $suffixPatterns = [
            'ca' => '/^(.+?)\s+AVCA\b/i',
            'se' => '/^(.+?)\s+AVES\b/i',
            'fscore' => '/^(.+?)\s+FSCORE\b/i',
            'grade' => '/^(.+?)\s+GRADE\b/i',
            'theory' => '/^(.+?)\s+TH\s*COMP\b/i',
            'practical' => '/^(.+?)\s+(?:OSPE|OSCE|PRACTICAL|CLINICAL)\b/i',
        ];

        foreach ($suffixPatterns as $field => $pattern) {
            // Only accept the match if what precedes the suffix actually looks like a module
            // code (e.g. "CMT04102 CLINICAL") — otherwise ordinary text containing one of these
            // words (e.g. "ORDINARY DIPLOMA IN CLINICAL MEDICINE") gets misread as a header.
            if (preg_match($pattern, $h, $m) && self::looksLikeModuleCode(trim($m[1]))) {
                return ['code' => self::moduleColumnLabel($m[1]), 'field' => $field];
            }
        }

        if (self::looksLikeModuleCode($h)) {
            return ['code' => self::moduleColumnLabel($h), 'field' => 'ca'];
        }

        return null;
    }

    public static function normalizeHeaderKey(string $cell): string
    {
        if (self::parseModuleColumnHeader($cell) !== null) {
            return '';
        }

        $h = strtolower(trim($cell));
        $h = preg_replace('/\s+/', '_', $h) ?? $h;
        $h = preg_replace('/[^a-z0-9_%()]/', '', $h) ?? $h;

        return match (true) {
            $h === 'sn' || str_starts_with($h, 'sn') => 'sn',
            str_contains($h, 'candidate') && str_contains($h, 'name') => 'candidate_name',
            $h === 'sex' || str_contains($h, 'sex') => 'sex',
            str_contains($h, 'nacte') && str_contains($h, 'registration') => 'nactvet_reg_no',
            str_contains($h, 'examination') && str_contains($h, 'number') => 'reg_no',
            str_contains($h, 'current') && str_contains($h, 'status') => 'current_status',
            str_contains($h, 'sit') && str_contains($h, 'status') => 'sit_status',
            str_starts_with($h, 'avca') => 'ca',
            str_starts_with($h, 'aves') => 'se',
            $h === 'fscore' || str_contains($h, 'fscore') => 'fscore',
            $h === 'grade' => 'grade',
            default => $h,
        };
    }

    public static function extractModuleCodeFromRow(array $row): ?string
    {
        $joined = implode(' ', array_map(fn ($c) => trim((string) $c), $row));
        if (preg_match('/MODULE\s*CODE\s*[:：]\s*(.+)$/iu', $joined, $m)) {
            return trim($m[1]);
        }
        if (isset($row[0]) && stripos((string) $row[0], 'MODULE CODE') !== false && isset($row[1])) {
            return trim((string) $row[1]);
        }

        return null;
    }

    public static function isDataHeaderRow(array $row): bool
    {
        $cells = array_map(fn ($c) => strtolower(trim((string) $c)), $row);

        if (in_array('sn', $cells, true) || (isset($cells[0]) && $cells[0] === 'sn')) {
            return true;
        }

        foreach ($row as $cell) {
            if (self::parseModuleColumnHeader((string) $cell) !== null) {
                return true;
            }
            if (self::looksLikeModuleCode((string) $cell)) {
                return true;
            }
        }

        return count(array_filter($cells, fn ($c) => str_contains($c, 'nacte') && str_contains($c, 'registration'))) > 0;
    }
}
