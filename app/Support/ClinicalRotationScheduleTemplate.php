<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * NTA-style clinical rotation grid: N groups, N department postings in sequence; each posting lasts 1 or 2 weeks.
 * N=5 → 5 or 10 calendar weeks for NTA 5–6; N=6 → 6 or 12 weeks for NTA 4.
 * Schedule documents list one row per Mon–Fri week so the full rotation is visible week-by-week.
 */
final class ClinicalRotationScheduleTemplate
{
    public static function groupSlotCount(int $ntaLevel): int
    {
        return match ($ntaLevel) {
            4 => 6,
            5, 6 => 5,
            default => throw new \InvalidArgumentException('Schedule template supports NTA levels 4, 5, and 6 only.'),
        };
    }

    public static function blockCount(int $ntaLevel): int
    {
        return self::groupSlotCount($ntaLevel);
    }

    /** Default weeks per department block when not set on the round (NTA 4 = one week per area). */
    public static function defaultWeeksPerBlockForNta(int $ntaLevel): int
    {
        return $ntaLevel === 4 ? 1 : 2;
    }

    /**
     * @return list<string>
     */
    public static function cycleAbbreviations(int $ntaLevel): array
    {
        return match ($ntaLevel) {
            4 => ['NUTR', 'CSKILLS', 'INTERNAL', 'OBGY', 'PAED', 'PCARE'],
            5 => ['RCH', 'SURGERY', 'OBGY', 'PAEDIATRIC', 'INTERNAL'],
            6 => ['OBGY', 'INTERNAL', 'PAEDIATRIC', 'SURGERY', 'EMD'],
            default => throw new \InvalidArgumentException('Schedule template supports NTA levels 4, 5, and 6 only.'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function legend(int $ntaLevel): array
    {
        return match ($ntaLevel) {
            4 => [
                'NUTR' => 'Clinical Nutrition',
                'CSKILLS' => 'Clinical Laboratory',
                'INTERNAL' => 'Internal Medicine',
                'OBGY' => 'Obstetrics and Gynaecology',
                'PAED' => 'Paediatric and Child Health',
                'PCARE' => 'Patient Care',
            ],
            5 => [
                'RCH' => 'Reproductive and Child Health / Community',
                'SURGERY' => 'Surgery',
                'OBGY' => 'Obstetrics and Gynaecology',
                'PAEDIATRIC' => 'Paediatric and Child Health',
                'INTERNAL' => 'Internal Medicine',
            ],
            6 => [
                'OBGY' => 'Obstetrics and Gynaecology',
                'INTERNAL' => 'Internal Medicine',
                'PAEDIATRIC' => 'Paediatric and Child Health',
                'SURGERY' => 'Surgery',
                'EMD' => 'Emergency Medicine',
            ],
            default => throw new \InvalidArgumentException('Schedule template supports NTA levels 4, 5, and 6 only.'),
        };
    }

    /**
     * One grid row per calendar week (Monday–Friday): every week from start until the rotation ends,
     * with each group's department for that week. Consecutive rows repeat the same posting when
     * {@see $weeksPerBlock} is 2 (e.g. NTA 5–6 → 5 groups × 2 weeks = 10 rows).
     *
     * @param  list<string>  $cycle
     * @return list<array{week_label: string, date_range: string, cells: list<string>}>
     */
    public static function rotationWeekRows(Carbon $scheduleStartMonday, array $cycle, int $weeksPerBlock): array
    {
        if (! in_array($weeksPerBlock, [1, 2], true)) {
            throw new \InvalidArgumentException('Weeks per block must be 1 or 2.');
        }

        $start = $scheduleStartMonday->copy()->startOfDay();
        if (! $start->isMonday()) {
            throw new \InvalidArgumentException('Schedule start must be a Monday.');
        }
        $n = count($cycle);
        if ($n < 2) {
            throw new \InvalidArgumentException('Cycle must contain at least two departments.');
        }

        $totalWeeks = $n * $weeksPerBlock;
        $rows = [];
        for ($gw = 0; $gw < $totalWeeks; $gw++) {
            $b = intdiv($gw, $weeksPerBlock);
            $weekMonday = $start->copy()->addWeeks($gw);
            $weekFriday = $weekMonday->copy()->addDays(4);
            $cells = [];
            for ($groupSlot = 1; $groupSlot <= $n; $groupSlot++) {
                $cells[] = $cycle[($groupSlot - 1 + $b) % $n];
            }
            $rows[] = [
                'week_label' => 'Week '.($gw + 1),
                'date_range' => $weekMonday->format('d M').' – '.$weekFriday->format('d M Y'),
                'cells' => $cells,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<string>  $cycle
     * @return array<int, string>
     */
    public static function groupRotationOrderLines(array $cycle): array
    {
        $n = count($cycle);
        $lines = [];
        for ($g = 1; $g <= $n; $g++) {
            $parts = [];
            for ($d = 0; $d < $n; $d++) {
                $parts[] = $cycle[($g - 1 + $d) % $n];
            }
            $lines[$g] = implode(' → ', $parts);
        }

        return $lines;
    }

    /**
     * @return array{
     *     nta_level: int,
     *     group_count: int,
     *     weeks_per_block: int,
     *     total_weeks: int,
     *     start_monday: Carbon,
     *     block_rows: list<array{week_label: string, date_range: string, cells: list<string>}>,
     *     group_order_lines: array<int, string>,
     *     legend: array<string, string>
     * }
     */
    public static function documentPayload(Carbon $startMonday, int $ntaLevel, int $weeksPerBlock): array
    {
        $cycle = self::cycleAbbreviations($ntaLevel);
        $n = count($cycle);

        return [
            'nta_level' => $ntaLevel,
            'group_count' => $n,
            'weeks_per_block' => $weeksPerBlock,
            'total_weeks' => $n * $weeksPerBlock,
            'start_monday' => $startMonday->copy()->startOfDay(),
            'block_rows' => self::rotationWeekRows($startMonday, $cycle, $weeksPerBlock),
            'group_order_lines' => self::groupRotationOrderLines($cycle),
            'legend' => self::legend($ntaLevel),
        ];
    }
}
