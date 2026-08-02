<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasSoftDeleteAudit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableSlot extends Model
{
    use SoftDeletes, HasSoftDeleteAudit;

    protected $fillable = ['semester_id', 'course_id', 'day_of_week', 'start_time', 'end_time', 'room', 'venue'];

    public const DAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    /** Standard teaching week: Monday–Friday, three sessions a day with break/lunch between them. */
    public const WEEK_DAYS = [1, 2, 3, 4, 5];

    public const DAILY_SESSIONS = [
        ['start' => '07:30', 'end' => '09:30', 'label' => '07:30 – 09:30'],
        ['start' => '10:00', 'end' => '12:30', 'label' => '10:00 – 12:30'],
        ['start' => '13:30', 'end' => '16:30', 'label' => '13:30 – 16:30'],
    ];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
