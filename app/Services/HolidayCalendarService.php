<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HolidayCalendarService
{
    public function __construct(
        private HolidayCatalog $catalog
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function eventsBetween(string $start, string $end): array
    {
        $from = Carbon::parse($start)->startOfDay();
        $to = Carbon::parse($end)->endOfDay();

        $dbEvents = $this->collegeEvents($from, $to);
        $coveredDates = $this->coveredDatesFromDb($dbEvents);

        $events = collect()
            ->merge($this->catalogHolidays($from, $to, $coveredDates))
            ->merge($dbEvents)
            ->merge($this->semesterSpans($from, $to));

        return $this->deduplicateByDateAndTitle($events)->values()->all();
    }

    /**
     * Config holidays not overridden by an admin activation on the same date.
     *
     * @param  array<string, true>  $coveredDates
     * @return Collection<int, array<string, mixed>>
     */
    private function catalogHolidays(Carbon $from, Carbon $to, array $coveredDates): Collection
    {
        $out = collect();

        for ($year = (int) $from->year; $year <= (int) $to->year; $year++) {
            foreach ($this->catalog->forYear($year) as $item) {
                if ($this->catalog->isActivated($item['key'], $year)) {
                    continue;
                }

                if (! empty($item['requires_activation'])) {
                    continue;
                }

                $start = Carbon::parse($item['starts_on']);
                if (! $start->between($from, $to)) {
                    continue;
                }

                $dateKey = 'date:'.$item['starts_on'].':'.$item['type'];
                if (isset($coveredDates[$dateKey])) {
                    continue;
                }

                $color = CalendarEvent::colorForType($item['type']);

                $out->push($this->fcEvent(
                    $item['title'],
                    $item['starts_on'],
                    $item['ends_on'] ?? $item['starts_on'],
                    $color,
                    $item['type'],
                    true,
                    null,
                    $item['description'],
                    false,
                    $item['key']
                ));
            }
        }

        return $out;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $dbEvents
     * @return array<string, true>
     */
    private function coveredDatesFromDb(Collection $dbEvents): array
    {
        $covered = [];
        foreach ($dbEvents as $ev) {
            $props = $ev['extendedProps'] ?? [];
            $type = $props['type'] ?? '';
            $start = Carbon::parse($ev['start'])->format('Y-m-d');
            $covered['date:'.$start.':'.$type] = true;
        }

        return $covered;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $events
     * @return Collection<int, array<string, mixed>>
     */
    private function deduplicateByDateAndTitle(Collection $events): Collection
    {
        $seen = [];
        $result = collect();

        foreach ($events->sortByDesc(fn ($e) => ($e['extendedProps']['fromDatabase'] ?? false) ? 1 : 0) as $event) {
            $start = Carbon::parse($event['start'])->format('Y-m-d');
            $norm = HolidayCatalog::normalizeTitle($event['title']);
            $type = $event['extendedProps']['type'] ?? '';
            $fingerprint = $start.'|'.$type.'|'.$this->titleFingerprint($norm);

            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $result->push($event);
        }

        return $result;
    }

    private function titleFingerprint(string $normalized): string
    {
        foreach (['eidaladha', 'eidalfitr', 'eidalfit', 'goodfriday', 'eastermonday'] as $token) {
            if (str_contains($normalized, $token)) {
                return $token;
            }
        }

        return substr($normalized, 0, 24);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function semesterSpans(Carbon $from, Carbon $to): Collection
    {
        return Semester::query()
            ->whereNotNull('start_date')
            ->get()
            ->filter(function (Semester $s) use ($from, $to) {
                $start = $s->start_date;
                $end = $s->end_date ?? $s->start_date;

                return $start && $end && $end >= $from->toDateString() && $start <= $to->toDateString();
            })
            ->map(function (Semester $s) {
                $end = $s->end_date ?? $s->start_date;

                return $this->fcEvent(
                    'Semester: '.$s->label,
                    $s->start_date,
                    $end,
                    '#6366f1',
                    'semester',
                    true,
                    null,
                    'Academic semester period for MUSOMA COHAS.',
                    false,
                    null
                );
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function collegeEvents(Carbon $from, Carbon $to): Collection
    {
        return CalendarEvent::query()
            ->where('starts_on', '<=', $to->toDateString())
            ->where(function ($q) use ($from) {
                $q->whereNull('ends_on')->orWhere('ends_on', '>=', $from->toDateString());
            })
            ->orderBy('starts_on')
            ->get()
            ->map(function (CalendarEvent $e) {
                return $this->fcEvent(
                    $e->title,
                    $e->starts_on,
                    $e->ends_on ?? $e->starts_on,
                    CalendarEvent::colorForType($e->type, $e->color),
                    $e->type,
                    (bool) $e->all_day,
                    (string) $e->id,
                    $e->description,
                    true,
                    $e->catalog_key
                );
            });
    }

    private function fcEvent(
        string $title,
        $start,
        $end,
        string $color,
        string $type,
        bool $allDay = true,
        ?string $id = null,
        ?string $description = null,
        bool $fromDatabase = false,
        ?string $catalogKey = null
    ): array {
        $startC = $start instanceof Carbon ? $start : Carbon::parse($start);
        $endC = $end instanceof Carbon ? $end : Carbon::parse($end);

        return [
            'id' => $id ? 'db-'.$id : 'cat-'.($catalogKey ?? md5($title.$startC->format('Y-m-d'))),
            'title' => $title,
            'start' => $startC->toDateString(),
            'end' => $allDay ? $endC->copy()->addDay()->toDateString() : $endC->toDateString(),
            'allDay' => $allDay,
            'backgroundColor' => $color,
            'borderColor' => $color,
            'textColor' => '#ffffff',
            'classNames' => [$fromDatabase ? 'fc-ev-custom' : 'fc-ev-catalog'],
            'extendedProps' => [
                'type' => $type,
                'typeLabel' => CalendarEvent::TYPES[$type] ?? ucfirst(str_replace('_', ' ', $type)),
                'description' => $description,
                'fromDatabase' => $fromDatabase,
                'eventId' => $fromDatabase ? (int) $id : null,
                'catalogKey' => $catalogKey,
            ],
        ];
    }
}
