<?php

namespace App\Support;

use App\Models\Result;
use Illuminate\Support\Collection;

/**
 * MuCOHAS / NACTVET grading: marks out of 100%, GPA = Σ(P×N) / ΣN (max 4.0).
 */
class GradingScale
{
    /** @var list<array{min: float, max: float, grade: string, points: float, label: string}> */
    public const BANDS = [
        ['min' => 80, 'max' => 100, 'grade' => 'A', 'points' => 4.0, 'label' => 'Excellent'],
        ['min' => 65, 'max' => 79.99, 'grade' => 'B', 'points' => 3.0, 'label' => 'Good'],
        ['min' => 50, 'max' => 64.99, 'grade' => 'C', 'points' => 2.0, 'label' => 'Satisfactory'],
        ['min' => 40, 'max' => 49.99, 'grade' => 'D', 'points' => 1.0, 'label' => 'Poor'],
        ['min' => 0, 'max' => 39.99, 'grade' => 'F', 'points' => 0.0, 'label' => 'Failure'],
    ];

    public const GRADE_INCOMPLETE = 'Q';

    public const MAX_GPA = 4.0;

    public static function markToGrade(float $mark): string
    {
        foreach (self::BANDS as $band) {
            if ($mark >= $band['min'] && $mark <= $band['max']) {
                return $band['grade'];
            }
        }

        return 'F';
    }

    public static function gradeToPoints(?string $grade): ?float
    {
        $g = strtoupper(trim((string) $grade));
        if ($g === self::GRADE_INCOMPLETE || $g === '') {
            return null;
        }
        foreach (self::BANDS as $band) {
            if ($band['grade'] === $g) {
                return $band['points'];
            }
        }

        return null;
    }

    public static function gradeLabel(?string $grade): ?string
    {
        $g = strtoupper(trim((string) $grade));
        foreach (self::BANDS as $band) {
            if ($band['grade'] === $g) {
                return $band['label'];
            }
        }

        return $g === self::GRADE_INCOMPLETE ? 'Incomplete / Disqualification' : null;
    }

    /** Modules that count toward award (A, B, or C only). */
    public static function gradeEligibleForAward(?string $grade): bool
    {
        return in_array(strtoupper(trim((string) $grade)), ['A', 'B', 'C'], true);
    }

    /** @param  Collection<int, Result>|iterable<Result>  $results */
    public static function computeGpa(iterable $results): ?float
    {
        $totals = self::creditTotals($results);

        if ($totals['credits'] <= 0) {
            return null;
        }

        return round($totals['grade_points'] / $totals['credits'], 4);
    }

    /**
     * @param  Collection<int, Result>|iterable<Result>  $results
     * @return array{total_credits: ?float, total_grade_points: ?float, gpa: ?float, award_class: ?string, remarks: ?string}
     */
    public static function summarize(iterable $results): array
    {
        $totals = self::creditTotals($results);
        $gpa = $totals['credits'] > 0
            ? round($totals['grade_points'] / $totals['credits'], 4)
            : null;

        return [
            'total_credits' => $totals['credits'] > 0 ? round($totals['credits'], 1) : null,
            'total_grade_points' => $totals['has_points'] ? round($totals['grade_points'], 2) : null,
            'gpa' => $gpa,
            'award_class' => $gpa !== null ? self::awardClassification($gpa) : null,
            'remarks' => $gpa !== null ? self::awardClassification($gpa) : null,
        ];
    }

    public static function awardClassification(float $gpa): string
    {
        if ($gpa >= 3.5) {
            return 'First Class';
        }
        if ($gpa >= 3.0) {
            return 'Second Class';
        }
        if ($gpa >= 2.0) {
            return 'Pass';
        }

        return 'Below pass threshold';
    }

    /**
     * @param  Collection<int, Result>|iterable<Result>  $results
     * @return array{credits: float, grade_points: float, has_points: bool}
     */
    private static function creditTotals(iterable $results): array
    {
        $totalCredits = 0.0;
        $totalGradePoints = 0.0;
        $hasPoints = false;

        foreach ($results as $result) {
            if (! $result instanceof Result) {
                continue;
            }
            $result->loadMissing('course');
            $credits = (float) ($result->course->credits ?? 0);
            if ($credits <= 0) {
                continue;
            }
            $points = self::gradeToPoints($result->grade);
            if ($points === null) {
                continue;
            }
            $hasPoints = true;
            $totalCredits += $credits;
            $totalGradePoints += $points * $credits;
        }

        return [
            'credits' => $totalCredits,
            'grade_points' => $totalGradePoints,
            'has_points' => $hasPoints,
        ];
    }
}
