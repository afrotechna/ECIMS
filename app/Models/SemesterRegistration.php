<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SemesterRegistration extends Model
{
    protected $fillable = [
        'student_id',
        'semester_id',
        'status',
        'registered_at',
        'approved_at',
        'approved_by',
        'notes',
        'wizard_step',
        'wizard_payload',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'approved_at' => 'datetime',
        'wizard_step' => 'integer',
        'wizard_payload' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
