<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasSoftDeleteAudit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableSlot extends Model
{
    use SoftDeletes, HasSoftDeleteAudit;

    protected $fillable = ['semester_id', 'course_id', 'lecturer', 'day_of_week', 'start_time', 'end_time', 'room', 'venue'];

    public const DAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    /** Standard teaching week: Monday–Friday, four sessions a day with tea/lunch breaks between them. */
    public const WEEK_DAYS = [1, 2, 3, 4, 5];

    public const DAILY_SESSIONS = [
        ['start' => '07:30', 'end' => '09:30', 'label' => '07:30 – 09:30'],
        ['start' => '10:00', 'end' => '12:00', 'label' => '10:00 – 12:00'],
        ['start' => '13:00', 'end' => '15:00', 'label' => '13:00 – 15:00'],
        ['start' => '15:00', 'end' => '16:30', 'label' => '15:00 – 16:30'],
    ];

    /** Break rows shown in the grid, keyed by the session index they follow (0-based). */
    public const BREAKS = [
        0 => ['start' => '09:30', 'end' => '10:00', 'label' => 'TEA-BREAK'],
        1 => ['start' => '12:00', 'end' => '13:00', 'label' => 'LUNCH-BREAK'],
    ];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Arrange a flat collection of slots into the standard day × session grid (day => session
     * start time => slot|null), matching WEEK_DAYS/DAILY_SESSIONS. Shared by every place that
     * renders the weekly timetable (admin grid, Word/print export, student "My Timetable").
     *
     * @param  \Illuminate\Support\Collection<int, self>  $slots
     */
    public static function buildWeekGrid($slots): array
    {
        $grid = [];
        foreach (self::WEEK_DAYS as $day) {
            foreach (self::DAILY_SESSIONS as $session) {
                $grid[$day][$session['start']] = $slots->first(fn ($s) => (int) $s->day_of_week === $day
                    && substr((string) $s->start_time, 0, 5) === $session['start']
                    && substr((string) $s->end_time, 0, 5) === $session['end']);
            }
        }

        return $grid;
    }
}
