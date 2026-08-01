<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasSoftDeleteAudit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSlot extends Model
{
    use SoftDeletes, HasSoftDeleteAudit;

    /** Continuous Assessment I examination slot. */
    public const ASSESSMENT_CAT1 = 'cat1';

    /** Continuous Assessment II examination slot. */
    public const ASSESSMENT_CAT2 = 'cat2';

    public const FORMAT_THEORY = 'theory';

    public const FORMAT_PRACTICAL = 'practical';

    public const FORMAT_OSCE = 'osce';

    public const FORMAT_OSPE = 'ospe';

    protected $fillable = [
        'semester_id',
        'course_id',
        'exam_date',
        'start_time',
        'end_time',
        'room',
        'notes',
        'assessment_type',
        'exam_format',
    ];

    /**
     * @return array<string, string>
     */
    public static function assessmentOptions(): array
    {
        return [
            self::ASSESSMENT_CAT1 => 'Continuous Assessment I (CAT I)',
            self::ASSESSMENT_CAT2 => 'Continuous Assessment II (CAT II)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function formatOptions(): array
    {
        return [
            self::FORMAT_THEORY => 'Theory',
            self::FORMAT_PRACTICAL => 'Practical',
            self::FORMAT_OSCE => 'OSCE',
            self::FORMAT_OSPE => 'OSPE',
        ];
    }

    public static function normalizeFormat(?string $format): string
    {
        $format = strtolower(trim((string) $format));

        return array_key_exists($format, self::formatOptions())
            ? $format
            : self::FORMAT_THEORY;
    }

    public static function formatSortOrder(?string $format): int
    {
        return match (self::normalizeFormat($format)) {
            self::FORMAT_THEORY => 0,
            self::FORMAT_PRACTICAL => 1,
            self::FORMAT_OSCE => 2,
            self::FORMAT_OSPE => 3,
            default => 0,
        };
    }

    /** Display label under module name, e.g. (THEORY). */
    public function formatDisplayLabel(): string
    {
        return match (self::normalizeFormat($this->exam_format)) {
            self::FORMAT_PRACTICAL => '(PRACTICAL)',
            self::FORMAT_OSCE => '(OSCE)',
            self::FORMAT_OSPE => '(OSPE)',
            default => '(THEORY)',
        };
    }

    protected $casts = ['exam_date' => 'date'];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
