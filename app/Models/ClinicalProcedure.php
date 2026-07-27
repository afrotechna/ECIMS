<?php

namespace App\Models;

use App\Support\ClinicalRotationCatalog;
use App\Support\CmtPracticumCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalProcedure extends Model
{
    protected $fillable = [
        'programme_id',
        'nta_level',
        'department_code',
        'code',
        'parent_code',
        'name',
        'description',
        'assessment_modes',
        'practicum_section',
        'source_type',
        'min_required_count',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_required_count' => 'integer',
        'sort_order' => 'integer',
    ];

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function logbookEntries(): HasMany
    {
        return $this->hasMany(ClinicalLogbookEntry::class);
    }

    public function departmentLabel(): string
    {
        if (! $this->department_code) {
            return '—';
        }
        $code = ClinicalRotationCatalog::normalizeDepartmentCode($this->department_code) ?? $this->department_code;
        $labels = ClinicalRotationCatalog::departmentLabelsForNtaLevel((int) $this->nta_level)
            + ClinicalRotationCatalog::departmentLabels();

        return $labels[$code] ?? $code;
    }

    /** @return list<string> */
    public function assessmentModesList(): array
    {
        return CmtPracticumCatalog::assessmentModesList($this->assessment_modes);
    }
}
