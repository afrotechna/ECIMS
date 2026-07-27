<?php

namespace App\Support;

/**
 * Clinical rotation: group slots and departments (Semester II), and hospital sites.
 * NTA 4 uses six clinical areas; NTA 5–6 use five department blocks.
 */
final class ClinicalRotationCatalog
{
    /** @deprecated Use groupSlotCountForNta() */
    public const GROUP_SLOT_COUNT = 5;

    public static function groupSlotCountForNta(int $ntaLevel): int
    {
        return match ($ntaLevel) {
            4 => 6,
            5, 6 => 5,
            default => 5,
        };
    }

    /**
     * Department keys allowed for a rotation round at this NTA level.
     *
     * @return array<string, string>
     */
    public static function departmentLabelsForNtaLevel(int $ntaLevel): array
    {
        if ($ntaLevel === 4) {
            return [
                'clinical_nutrition' => 'Clinical Nutrition',
                'clinical_laboratory' => 'Clinical Laboratory',
                'internal_medicine' => 'Internal Medicine',
                'obstetrics_gynaecology' => 'Obstetrics and Gynaecology',
                'paediatrics' => 'Paediatric and Child Health',
                'patient_care' => 'Patient Care',
                'semester_one' => 'Semester I practicum modules',
            ];
        }

        return self::departmentLabels();
    }

    /**
     * Ordered department keys for default Group 1 → Group 5 mapping (editable per group).
     *
     * @return list<string>
     */
    public static function defaultDepartmentSlotKeys(): array
    {
        return [
            'surgery',
            'obstetrics_gynaecology',
            'paediatrics',
            'internal_medicine',
            'community_health',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function departmentLabels(): array
    {
        return [
            'surgery' => 'Surgery',
            'obstetrics_gynaecology' => 'Obstetrics and Gynaecology',
            'paediatrics' => 'Paediatric and Child Health',
            'internal_medicine' => 'Internal Medicine',
            'community_health' => 'Community Health / Primary Care',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function hospitalLabels(): array
    {
        return [
            'nyasho' => 'Nyasho Health Center',
            'musoma_municipal' => 'Musoma Municipal Hospital',
            'nyerere_kwangwa' => 'Mwalimu Nyerere Hospital (Kwangwa)',
        ];
    }

    public static function departmentLabel(string $code): string
    {
        $code = self::normalizeDepartmentCode($code) ?? $code;
        $merged = self::departmentLabelsForNtaLevel(4) + self::departmentLabels();

        return $merged[$code] ?? $code;
    }

    /** @deprecated Renamed to clinical_laboratory (NTA 4). */
    public static function normalizeDepartmentCode(?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        return match ($code) {
            'clinical_skills' => 'clinical_laboratory',
            default => $code,
        };
    }

    public static function hospitalLabel(?string $code): string
    {
        if ($code === null || $code === '') {
            return '—';
        }

        $normalized = self::normalizeHospitalCode($code);

        return self::hospitalLabels()[$normalized] ?? $code;
    }

    /**
     * Legacy rows may still store nyerere or kwangwa; treat as one site with Nyerere/Kwangwa.
     */
    public static function normalizeHospitalCode(?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        return match ($code) {
            'nyerere', 'kwangwa' => 'nyerere_kwangwa',
            default => $code,
        };
    }

    public static function defaultDepartmentForSlot(int $slotNumber): string
    {
        $keys = self::defaultDepartmentSlotKeys();
        $idx = max(0, min(count($keys) - 1, $slotNumber - 1));

        return $keys[$idx];
    }
}
