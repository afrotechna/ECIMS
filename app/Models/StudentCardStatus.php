<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCardStatus extends Model
{
    protected $fillable = [
        'student_id',
        'document_type',
        'status',
        'printed_at',
        'activated_at',
        'updated_by',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    public const DOCUMENT_TYPES = [
        'student_id' => 'Student ID card',
        'nhif' => 'NHIF card',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'printed' => 'Printed',
        'active' => 'Active / ready for collection',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function documentTypeLabel(): string
    {
        return self::DOCUMENT_TYPES[$this->document_type] ?? $this->document_type;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
