<?php

namespace App\Services;

use App\Models\Result;

/**
 * Sample marks for demo result import templates (CA and final).
 */
class ResultImportDemoData
{
    /** @var list<float> */
    private const CA_MARKS = [30.0, 22.5, 31.5, 28.0, 25.0, 30.2, 18.0, 35.0, 27.5, 32.0, 24.0, 29.5];

    /** @var list<float> */
    private const SE_MARKS = [48.0, 52.0, 45.0, 55.0, 50.0, 47.0, 53.0, 49.0, 51.0, 46.0];

    public static function caMark(int $rowIndex, int $courseIndex): float
    {
        return self::CA_MARKS[($rowIndex + $courseIndex) % count(self::CA_MARKS)];
    }

    /**
     * @return array{ca: float, se: float, fscore: float, grade: string}
     */
    public static function finalMarks(int $rowIndex, int $courseIndex): array
    {
        $ca = self::caMark($rowIndex, $courseIndex);
        $se = self::SE_MARKS[($rowIndex + $courseIndex) % count(self::SE_MARKS)];
        $fscore = round(($ca * 0.4) + ($se * 0.6), 1);

        return [
            'ca' => $ca,
            'se' => $se,
            'fscore' => $fscore,
            'grade' => Result::markToGrade($fscore),
        ];
    }
}
