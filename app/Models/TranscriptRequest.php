<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranscriptRequest extends Model
{
    protected $fillable = [
        'student_id',
        'purpose',
        'status',
        'staff_notes',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'ready' => 'Ready for collection',
        'rejected' => 'Rejected',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
