<?php

namespace App\Support;

use App\Models\Semester;
use Carbon\Carbon;
use DateTimeInterface;

/**
 * Academic sessions stored as the opening calendar year (e.g. 2025 for 2025/2026).
 */
final class AcademicSession
{
    /**
     * @return array<int, string> start_year => "2025/2026"
     */
    public static function yearOptions(int $from = 2020, ?int $to = null): array
    {
        $to = $to ?? (self::currentStartYear() + 2);
        $options = [];
        for ($y = $from; $y <= $to; $y++) {
            $options[$y] = self::label($y);
        }

        return $options;
    }

    public static function label(int $startYear): string
    {
        return $startYear.'/'.($startYear + 1);
    }

    public static function parseStartYear(?string $session): ?int
    {
        if ($session === null || $session === '') {
            return null;
        }
        if (preg_match('/^(\d{4})\/\d{4}$/', trim($session), $m)) {
            return (int) $m[1];
        }
        if (preg_match('/^(\d{4})$/', trim($session), $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * Current academic year start from the calendar (e.g. May 2026 → 2025 for 2025/2026).
     */
    public static function currentStartYear(?DateTimeInterface $at = null): int
    {
        $at = $at ? Carbon::parse($at) : now();
        $startMonth = self::startMonth();
        $calendarYear = (int) $at->format('Y');
        $month = (int) $at->format('n');

        return $month >= $startMonth ? $calendarYear : $calendarYear - 1;
    }

    /**
     * Default year for forms and dashboards: active semester spanning today, else calendar rule.
     */
    public static function defaultStartYear(?DateTimeInterface $at = null): int
    {
        $at = $at ? Carbon::parse($at) : now();
        $today = $at->toDateString();

        $fromActiveSemester = Semester::query()
            ->where('is_active', true)
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderByDesc('academic_year')
            ->value('academic_year');

        if ($fromActiveSemester) {
            return (int) $fromActiveSemester;
        }

        $calendarStart = self::currentStartYear($at);

        $hasActiveForCalendarYear = Semester::query()
            ->where('is_active', true)
            ->where('academic_year', $calendarStart)
            ->exists();

        if ($hasActiveForCalendarYear) {
            return $calendarStart;
        }

        $latestActive = Semester::query()
            ->where('is_active', true)
            ->orderByDesc('academic_year')
            ->value('academic_year');

        if ($latestActive && (int) $latestActive <= $calendarStart) {
            return (int) $latestActive;
        }

        return $calendarStart;
    }

    public static function resolveStartYear(?int $requested, ?DateTimeInterface $at = null): int
    {
        if ($requested !== null && $requested > 0) {
            return $requested;
        }

        return self::defaultStartYear($at);
    }

    public static function startMonth(): int
    {
        $month = (int) config('college.academic_year_start_month', 7);

        return max(1, min(12, $month));
    }
}
