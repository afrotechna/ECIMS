<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasSoftDeleteAudit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalRotationRound extends Model
{
    use SoftDeletes, HasSoftDeleteAudit;

    protected $fillable = [
        'semester_id',
        'programme_id',
        'nta_level',
        'rotation_week_monday',
        'rotation_week_friday',
        'schedule_weeks_per_block',
        'title',
        'capacity_per_group',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'capacity_per_group' => 'integer',
        'rotation_week_monday' => 'date',
        'rotation_week_friday' => 'date',
    ];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(ClinicalRotationGroup::class)->orderBy('slot_number');
    }
}
