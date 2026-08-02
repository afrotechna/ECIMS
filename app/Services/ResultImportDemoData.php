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

    /** Includes a couple of values below the pass mark (10.1) so the demo shows a theory fail too. */
    /** @var list<float> */
    private const THEORY_MARKS = [12.5, 11.2, 13.8, 9.6, 14.2, 12.0, 15.1, 11.6, 13.0, 8.8];

    /** Includes a couple of values below the pass mark (50) so the demo shows a practical fail too. */
    /** @var list<float> */
    private const PRACTICAL_MARKS = [72.0, 65.0, 80.0, 58.0, 90.0, 41.0, 55.0, 76.0, 62.0, 47.0];

    public static function caMark(int $rowIndex, int $courseIndex): float
    {
        return self::CA_MARKS[($rowIndex + $courseIndex) % count(self::CA_MARKS)];
    }

    /**
     * Sample theory/practical/CA(40%) triple for the CA demo template.
     *
     * @return array{theory: float, practical: ?float, ca: float}
     */
    public static function caComponents(int $rowIndex, int $courseIndex, bool $hasPractical): array
    {
        $theory = self::THEORY_MARKS[($rowIndex + $courseIndex) % count(self::THEORY_MARKS)];
        $theoryPass = $theory > (float) config('college.ca_theory_pass_mark', 10.1);

        if (! $hasPractical) {
            return ['theory' => $theory, 'practical' => null, 'ca' => $theoryPass ? round($theory, 1) : 0.0];
        }

        $practical = self::PRACTICAL_MARKS[($rowIndex + $courseIndex) % count(self::PRACTICAL_MARKS)];
        $practicalPass = $practical > (float) config('college.ca_practical_pass_mark', 50);
        $ca = ($theoryPass && $practicalPass) ? round($theory + ($practical * 0.2), 1) : 0.0;

        return ['theory' => $theory, 'practical' => $practical, 'ca' => $ca];
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
