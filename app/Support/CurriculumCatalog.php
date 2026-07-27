<?php

namespace App\Support;

use App\Models\Programme;

class CurriculumCatalog
{
    /**
     * @return array{1?: array<int, array<string, mixed>>, 2?: array<int, array<string, mixed>>}|null
     */
    public static function tablesForProgramme(Programme $programme, int $ntaLevel): ?array
    {
        $code = strtoupper((string) $programme->code);

        return config("curriculum_modules.programmes.{$code}.{$ntaLevel}");
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findModule(Programme $programme, int $ntaLevel, int $semesterTerm, string $moduleCode): ?array
    {
        $tables = self::tablesForProgramme($programme, $ntaLevel);
        if ($tables === null || ! isset($tables[$semesterTerm])) {
            return null;
        }
        $needle = strtoupper(trim($moduleCode));
        foreach ($tables[$semesterTerm] as $row) {
            if (strtoupper((string) ($row['code'] ?? '')) === $needle) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return array{ca_weight: int, exam_weight: int}
     */
    public static function defaultWeights(): array
    {
        $d = config('curriculum_modules.defaults', []);

        return [
            'ca_weight' => (int) ($d['ca_weight'] ?? 40),
            'exam_weight' => (int) ($d['exam_weight'] ?? 60),
        ];
    }
}
