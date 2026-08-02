<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConductRecord extends Model
{
    protected $fillable = ['student_id', 'date', 'type', 'sanction', 'description', 'recorded_by', 'effective_until'];

    protected $casts = ['date' => 'date', 'effective_until' => 'date'];

    public const TYPES = [
        'warning' => 'Warning',
        'reprimand' => 'Reprimand',
        'suspension' => 'Suspension',
        'fine' => 'Fine',
        'medical_permit' => 'Medical Permit (Sick)',
        'emergency_permit' => 'Emergency Permission (Home)',
        'other' => 'Other',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
