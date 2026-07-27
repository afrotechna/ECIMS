<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'programme_id',
        'code',
        'name',
        'credits',
        'ca_weight',
        'exam_weight',
        'has_practical',
        'practical_assessment_type',
        'year_of_study',
        'nta_level',
        'is_active',
        'requires_clinical_rotation',
    ];

    protected $casts = [
        'credits' => 'decimal:2',
        'is_active' => 'boolean',
        'has_practical' => 'boolean',
        'requires_clinical_rotation' => 'boolean',
    ];

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function semesters(): BelongsToMany
    {
        return $this->belongsToMany(Semester::class, 'course_semester')->withTimestamps();
    }

    /** Label for the practical/skills column in results (Practical, OSPE, or OSCE). */
    public function practicalColumnLabel(): string
    {
        if (! $this->has_practical) {
            return '';
        }

        return match ($this->practical_assessment_type) {
            'ospe' => 'OSPE',
            'osce' => 'OSCE',
            default => 'Practical',
        };
    }

    public function getCaExamLabelAttribute(): string
    {
        return 'CA ' . $this->ca_weight . '% / SE ' . $this->exam_weight . '%';
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    /** NTA Level 4–6 (explicit column, with fallback from year of study). */
    public function resolvedNtaLevel(): int
    {
        if ($this->nta_level !== null) {
            return (int) $this->nta_level;
        }
        $y = (int) $this->year_of_study;

        return max(4, min(6, $y + 3));
    }

    /** Unique semester term numbers (1 or 2) linked to this module. */
    public function semesterTermNumbers(): \Illuminate\Support\Collection
    {
        return $this->semesters->pluck('number')->unique()->sort()->values();
    }

    /** Short label for which semester terms this module is offered (based on linked semesters). */
    public function semesterTermsLabel(): string
    {
        $nums = $this->semesterTermNumbers();
        if ($nums->isEmpty()) {
            return '—';
        }

        return $nums->map(function ($n) {
            $n = (int) $n;

            return match ($n) {
                1 => 'Sem I',
                2 => 'Sem II',
                default => 'Sem '.$n,
            };
        })->implode(' · ');
    }
}
