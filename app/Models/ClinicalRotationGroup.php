<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalRotationGroup extends Model
{
    protected $fillable = [
        'clinical_rotation_round_id',
        'slot_number',
        'name',
        'department_code',
        'hospital_code',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(ClinicalRotationRound::class, 'clinical_rotation_round_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'crt_group_students')
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function attendanceMarks(): HasMany
    {
        return $this->hasMany(ClinicalRotationAttendanceMark::class);
    }

    public function logbookEntries(): HasMany
    {
        return $this->hasMany(ClinicalLogbookEntry::class);
    }
}
