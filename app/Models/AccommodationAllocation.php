<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccommodationAllocation extends Model
{
    protected $fillable = [
        'student_id',
        'active_student_key',
        'room_id',
        'from_date',
        'to_date',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $allocation) {
            $allocation->active_student_key = $allocation->status === 'active'
                ? $allocation->student_id
                : null;
        });
    }

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Allocations that represent a student resident in the room today
     * (status active and current date within from / to range).
     */
    public function scopeEffectiveNow(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('status', 'active')
            ->whereDate('from_date', '<=', $today)
            ->where(function (Builder $q) use ($today) {
                $q->whereNull('to_date')->orWhereDate('to_date', '>=', $today);
            });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
