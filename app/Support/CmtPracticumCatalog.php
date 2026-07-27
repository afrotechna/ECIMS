<?php

namespace App\Support;

use App\Models\ClinicalProcedure;

final class CmtPracticumCatalog
{
    /** @var list<int> */
    public const SUPPORTED_LEVELS = [4, 5, 6];

    public function __construct(
        private readonly int $ntaLevel,
    ) {}

    public static function forLevel(int $ntaLevel): self
    {
        return new self($ntaLevel);
    }

    public static function isSupportedLevel(int $ntaLevel): bool
    {
        return in_array($ntaLevel, self::SUPPORTED_LEVELS, true);
    }

    public function ntaLevel(): int
    {
        return $this->ntaLevel;
    }

    /**
     * @return array<string, mixed>
     */
    public function config(): array
    {
        $key = "cmt_nta{$this->ntaLevel}_practicum";

        return config($key, $this->ntaLevel === 4 ? config('cmt_nta4_practicum', []) : []);
    }

    public function sourceLabel(): string
    {
        return (string) ($this->config()['source'] ?? "CMT NTA {$this->ntaLevel} Practicum Guide");
    }

    /**
     * @return array<string, string>
     */
    public function rotationAreas(): array
    {
        return $this->config()['rotation_areas'] ?? [];
    }

    /**
     * @return array<string, string>
     */
    public function semesterModules(): array
    {
        return $this->config()['semester_modules'] ?? [];
    }

    /**
     * @return array<string, string>
     */
    public function moduleTitles(): array
    {
        return $this->config()['module_titles'] ?? $this->semesterModules();
    }

    public function moduleCodeForChecklistNumber(int $checklistNumber): string
    {
        if ($this->ntaLevel === 4) {
            if ($checklistNumber <= 20) {
                return 'SEMESTER_I';
            }
            if ($checklistNumber <= 23) {
                return 'CMT04208';
            }
            if ($checklistNumber <= 32) {
                return 'CMT04209';
            }
            if ($checklistNumber <= 48) {
                return 'CMT04211';
            }

            return 'CMT04212';
        }

        return 'CHK'.str_pad((string) $checklistNumber, 2, '0', STR_PAD_LEFT);
    }

    public function moduleCodeForProcedure(ClinicalProcedure $procedure): string
    {
        if ($procedure->source_type === 'module_competency') {
            $section = strtoupper(preg_replace('/\s+/', '', (string) $procedure->practicum_section));
            if (preg_match('/^CMT\d+/', $section, $m)) {
                return $m[0];
            }
            if (preg_match('/^(CMT\d+)/i', (string) $procedure->code, $m)) {
                return strtoupper($m[1]);
            }
        }

        $chkSource = $procedure->parent_code ?? $procedure->code;
        if (preg_match('/CHK(\d+)/i', (string) $chkSource, $m)) {
            return $this->moduleCodeForChecklistNumber((int) $m[1]);
        }

        if (preg_match('/^(CMT\d+)/i', (string) $procedure->code, $m)) {
            return strtoupper($m[1]);
        }

        $dept = (string) ($procedure->department_code ?? 'OTHER');

        return strtoupper($dept);
    }

    public function moduleGroupLabel(string $moduleCode): string
    {
        $titles = $this->moduleTitles();
        $title = $titles[$moduleCode] ?? null;

        if ($title === null && preg_match('/^CHK(\d+)$/i', $moduleCode, $m)) {
            return 'Checklist '.$m[1];
        }

        if ($title === null) {
            $labels = ClinicalRotationCatalog::departmentLabelsForNtaLevel($this->ntaLevel)
                + ClinicalRotationCatalog::departmentLabels();

            return $labels[strtolower($moduleCode)] ?? str_replace('_', ' ', ucfirst($moduleCode));
        }

        if (in_array($moduleCode, ['SEMESTER_I', 'OTHER'], true)) {
            return $title;
        }

        return "{$moduleCode} — {$title}";
    }

    /**
     * @return list<string>
     */
    public function moduleSortOrder(): array
    {
        $order = $this->config()['module_sort_order'] ?? null;
        if (is_array($order) && $order !== []) {
            return $order;
        }

        return array_keys($this->moduleTitles());
    }

    public function compareModuleCodes(string $a, string $b): int
    {
        $order = array_flip($this->moduleSortOrder());
        $ia = $order[$a] ?? 999;
        $ib = $order[$b] ?? 999;
        if ($ia !== $ib) {
            return $ia <=> $ib;
        }

        return strcmp($a, $b);
    }

    /**
     * @return list<string>
     */
    public function assessmentMethods(): array
    {
        return $this->config()['assessment_methods'] ?? [
            'Practical (supervised ward/lab)',
            'OSCE',
            'Checklist',
            'Logbook / procedure record (instructor sign-off)',
        ];
    }

    /**
     * @return list<array{department_code: string, code: string, name: string, description: string, min_required_count: int, assessment_modes: ?string, practicum_section: ?string}>
     */
    public function procedures(): array
    {
        $rows = [];
        foreach ($this->config()['procedures'] ?? [] as $row) {
            if (! is_array($row) || count($row) < 5) {
                continue;
            }
            [$department, $code, $name, $description, $min] = $row;
            $rows[] = [
                'department_code' => (string) $department,
                'code' => (string) $code,
                'name' => (string) $name,
                'description' => (string) $description,
                'min_required_count' => max(1, (int) $min),
                'assessment_modes' => isset($row[5]) ? (string) $row[5] : null,
                'practicum_section' => isset($row[6]) ? (string) $row[6] : null,
            ];
        }

        return $rows;
    }

    /** @return list<string> */
    public static function assessmentModesList(?string $modes): array
    {
        if ($modes === null || trim($modes) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $modes))));
    }

    /**
     * @return array<string, list<array{department_code: string, code: string, name: string, description: string, min_required_count: int}>>
     */
    public function proceduresByDepartment(): array
    {
        $grouped = [];
        foreach ($this->procedures() as $procedure) {
            $grouped[$procedure['department_code']][] = $procedure;
        }

        return $grouped;
    }
}
