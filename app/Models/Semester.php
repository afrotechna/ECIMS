<?php

namespace App\Models;

use App\Support\AcademicSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasSoftDeleteAudit;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class Semester extends Model
{
    use SoftDeletes, HasSoftDeleteAudit;

    /** First teaching period of the academic year (often roughly Oct–Feb). */
    public const PERIOD_FIRST = 1;

    /** Second teaching period of the academic year (often roughly Mar–Jul). */
    public const PERIOD_SECOND = 2;

    protected $fillable = [
        'name',
        'academic_year',
        'number',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_semester')->withTimestamps();
    }

    public function semesterRegistrations(): HasMany
    {
        return $this->hasMany(SemesterRegistration::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function getLabelAttribute(): string
    {
        return $this->academicYearRange().' · '.$this->periodName();
    }

    /** Display form e.g. "2025/2026" (July–June style year starting in calendar year). */
    public function academicYearRange(): string
    {
        return $this->academic_year.'/'.($this->academic_year + 1);
    }

    /** Human-readable period: Semester I or II (number is the period slot in that academic year). */
    public function periodName(): string
    {
        return match ((int) $this->number) {
            self::PERIOD_FIRST => 'Semester I',
            self::PERIOD_SECOND => 'Semester II',
            default => 'Semester '.$this->number,
        };
    }

    /**
     * Semester name stored in DB (only Semester I / Semester II).
     */
    public static function nameForPeriod(int $number): string
    {
        return match ($number) {
            self::PERIOD_FIRST => 'Semester I',
            self::PERIOD_SECOND => 'Semester II',
            default => 'Semester '.$number,
        };
    }

    /**
     * Options for semester select (value = period number).
     *
     * @return array<int, string>
     */
    public static function periodOptions(): array
    {
        return [
            self::PERIOD_FIRST => 'Semester I',
            self::PERIOD_SECOND => 'Semester II',
        ];
    }

    /**
     * Options for academic year dropdowns: start year => "YYYY/(YY+1)".
     *
     * @return array<int, string>
     */
    public static function academicYearOptionsForForms(): array
    {
        $current = AcademicSession::currentStartYear();
        $min = (int) (static::query()->min('academic_year') ?? $current);
        $max = (int) (static::query()->max('academic_year') ?? $current);
        $from = min($min, $current - 2);
        $to = max($max, $current + 4);

        $opts = [];
        for ($y = $from; $y <= $to; $y++) {
            $opts[$y] = $y.'/'.($y + 1);
        }

        return $opts;
    }

    /**
     * @return EloquentCollection<int, Semester>
     */
    public static function forAcademicYear(?int $startYear, bool $activeOnly = true): EloquentCollection
    {
        $q = static::query()->orderByDesc('academic_year')->orderBy('number');
        if ($startYear !== null) {
            $q->where('academic_year', $startYear);
        }
        if ($activeOnly) {
            $q->where('is_active', true);
        }

        return $q->get();
    }
}
