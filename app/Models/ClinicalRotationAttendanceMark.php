<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalRotationAttendanceMark extends Model
{
    protected $table = 'crt_attendance_marks';

    protected $fillable = [
        'clinical_rotation_group_id',
        'student_id',
        'week_starting',
        'mon_present',
        'tue_present',
        'wed_present',
        'thu_present',
        'fri_present',
    ];

    protected $casts = [
        'week_starting' => 'date',
        'mon_present' => 'boolean',
        'tue_present' => 'boolean',
        'wed_present' => 'boolean',
        'thu_present' => 'boolean',
        'fri_present' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ClinicalRotationGroup::class, 'clinical_rotation_group_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
