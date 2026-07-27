<?php

namespace App\Services;

use App\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Support\Str;

class HolidayCatalog
{
    /**
     * All predefined holidays for a calendar year (from config + Easter).
     *
     * @return list<array{key: string, title: string, type: string, starts_on: string, ends_on: string|null, description: string, requires_activation?: bool}>
     */
    public function forYear(int $year): array
    {
        $items = [];

        foreach (config('holidays.fixed_public', []) as $md => $title) {
            $key = 'fixed_'.str_replace('-', '_', $md);
            $date = "{$year}-{$md}";
            $items[] = $this->entry($key, $title, 'public_holiday', $date, $date, $key);
        }

        foreach ($this->easterForYear($year) as $key => $row) {
            $items[] = $this->entry($key, $row['title'], 'public_holiday', $row['date'], $row['date'], $key);
        }

        foreach (config('holidays.islamic_dates.'.$year, []) as $dateStr => $title) {
            $base = 'islamic_'.Str::slug(preg_replace('/\s*\(day\s*2\)/i', '', $title));
            $key = $base.(preg_match('/\(day\s*2\)/i', $title) ? '_day2' : '').'_'.str_replace('-', '', $dateStr);
            $row = $this->entry($key, $title, 'public_holiday', $dateStr, $dateStr, $base);
            $row['requires_activation'] = true;
            $items[] = $row;
        }

        foreach (config('holidays.international_observances', []) as $md => $title) {
            $key = 'intl_'.Str::slug($title);
            $date = "{$year}-{$md}";
            $items[] = $this->entry($key, $title, 'international', $date, $date, $key);
        }

        usort($items, fn ($a, $b) => strcmp($a['starts_on'], $b['starts_on']));

        return $items;
    }

    /**
     * Holidays for an academic session e.g. 2025 → Jul 2025–Jun 2026 (both calendar years).
     *
     * @return list<array{key: string, title: string, type: string, starts_on: string, ends_on: string|null, description: string, requires_activation?: bool}>
     */
    public function forAcademicYear(int $academicYearStart): array
    {
        $items = array_merge(
            $this->forYear($academicYearStart),
            $this->forYear($academicYearStart + 1)
        );

        usort($items, fn ($a, $b) => strcmp($a['starts_on'], $b['starts_on']));

        return $items;
    }

    public function find(string $catalogKey, int $year): ?array
    {
        foreach ($this->forYear($year) as $item) {
            if ($item['key'] === $catalogKey) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Keys already on the calendar (DB) for this year — config entries for these dates are hidden to avoid duplicates.
     *
     * @return array<string, true>
     */
    public function coveredDateKeys(int $year): array
    {
        $from = "{$year}-01-01";
        $to = "{$year}-12-31";
        $covered = [];

        $db = CalendarEvent::query()
            ->whereIn('type', ['public_holiday', 'international'])
            ->where('starts_on', '<=', $to)
            ->where(function ($q) use ($from) {
                $q->whereNull('ends_on')->orWhere('ends_on', '>=', $from);
            })
            ->get();

        foreach ($db as $event) {
            if ($event->catalog_key) {
                $covered[$event->catalog_key] = true;
            }
            $covered['date:'.$event->starts_on->format('Y-m-d').':'.$event->type] = true;
        }

        return $covered;
    }

    public function isActivated(string $catalogKey, int $year): bool
    {
        $item = $this->find($catalogKey, $year);
        if (! $item) {
            return false;
        }

        return CalendarEvent::query()
            ->where('catalog_key', $catalogKey)
            ->whereDate('starts_on', $item['starts_on'])
            ->exists();
    }

    public static function normalizeTitle(string $title): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', $title) ?? '');
    }

    /**
     * @return array{key: string, title: string, type: string, starts_on: string, ends_on: string|null, description: string}
     */
    private function entry(string $key, string $title, string $type, string $start, string $end, string $descKey): array
    {
        $descriptions = config('holidays.descriptions', []);

        return [
            'key' => $key,
            'title' => $title,
            'type' => $type,
            'starts_on' => $start,
            'ends_on' => $end !== $start ? $end : null,
            'description' => $descriptions[$descKey] ?? $descriptions[Str::slug($title)] ?? $this->defaultDescription($title, $type),
        ];
    }

    private function defaultDescription(string $title, string $type): string
    {
        if ($type === 'international') {
            return "{$title} — United Nations / international observance. College activities may reference this date.";
        }

        return "{$title} — Public holiday in Tanzania. No regular classes; college offices may be closed unless announced otherwise.";
    }

    /**
     * @return array<string, array{title: string, date: string}>
     */
    private function easterForYear(int $year): array
    {
        if (! function_exists('easter_date')) {
            return [];
        }
        $easter = Carbon::createFromTimestamp(easter_date($year))->startOfDay();

        return [
            'easter_good_friday' => ['title' => 'Good Friday', 'date' => $easter->copy()->subDays(2)->toDateString()],
            'easter_sunday' => ['title' => 'Easter Sunday', 'date' => $easter->toDateString()],
            'easter_monday' => ['title' => 'Easter Monday', 'date' => $easter->copy()->addDay()->toDateString()],
        ];
    }
}
