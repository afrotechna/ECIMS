<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasSoftDeleteAudit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    use SoftDeletes, HasSoftDeleteAudit;

    public const TYPES = [
        'public_holiday' => 'Public holiday',
        'international' => 'International observance',
        'college' => 'College event',
        'exam' => 'Examination',
        'registration' => 'Registration',
        'other' => 'Other',
    ];

    /** Colours shown on the shared college calendar for everyone. */
    public const TYPE_COLORS = [
        'public_holiday' => '#dc2626',
        'international' => '#7c3aed',
        'college' => '#059669',
        'exam' => '#ea580c',
        'registration' => '#2563eb',
        'other' => '#64748b',
    ];

    public static function colorForType(string $type, ?string $override = null): string
    {
        if ($override) {
            return $override;
        }

        return self::TYPE_COLORS[$type] ?? self::TYPE_COLORS['other'];
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function isHolidayType(): bool
    {
        return in_array($this->type, ['public_holiday', 'international'], true);
    }

    protected $fillable = [
        'title',
        'description',
        'starts_on',
        'ends_on',
        'all_day',
        'type',
        'catalog_key',
        'color',
        'created_by',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'all_day' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
