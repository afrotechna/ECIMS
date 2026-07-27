<?php

namespace App\Support;

use App\Models\ClinicalProcedure;

/**
 * @deprecated Use CmtPracticumCatalog::forLevel(4) instead.
 */
final class CmtNta4PracticumCatalog
{
    private static function catalog(): CmtPracticumCatalog
    {
        return CmtPracticumCatalog::forLevel(4);
    }

    public static function ntaLevel(): int
    {
        return self::catalog()->ntaLevel();
    }

    public static function sourceLabel(): string
    {
        return self::catalog()->sourceLabel();
    }

    public static function rotationAreas(): array
    {
        return self::catalog()->rotationAreas();
    }

    public static function semesterModules(): array
    {
        return self::catalog()->semesterModules();
    }

    public static function moduleTitles(): array
    {
        return self::catalog()->moduleTitles();
    }

    public static function moduleCodeForChecklistNumber(int $checklistNumber): string
    {
        return self::catalog()->moduleCodeForChecklistNumber($checklistNumber);
    }

    public static function moduleCodeForProcedure(ClinicalProcedure $procedure): string
    {
        return self::catalog()->moduleCodeForProcedure($procedure);
    }

    public static function moduleGroupLabel(string $moduleCode): string
    {
        return self::catalog()->moduleGroupLabel($moduleCode);
    }

    public static function moduleSortOrder(): array
    {
        return self::catalog()->moduleSortOrder();
    }

    public static function compareModuleCodes(string $a, string $b): int
    {
        return self::catalog()->compareModuleCodes($a, $b);
    }

    public static function assessmentMethods(): array
    {
        return self::catalog()->assessmentMethods();
    }

    public static function procedures(): array
    {
        return self::catalog()->procedures();
    }

    public static function assessmentModesList(?string $modes): array
    {
        return CmtPracticumCatalog::assessmentModesList($modes);
    }

    public static function proceduresByDepartment(): array
    {
        return self::catalog()->proceduresByDepartment();
    }
}
