<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year',
        'amount',
        'payment_method',
        'reference',
        'paid_at',
        'received_by',
        'receipt_path',
        'allocation',
        'notes',
        'tuition_category',
        'covers_semester_two_only',
    ];

    protected $casts = [
        'academic_year' => 'integer',
        'amount' => 'decimal:0',
        'paid_at' => 'datetime',
        'allocation' => 'array',
        'covers_semester_two_only' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public static function methods(): array
    {
        return ['cash' => 'Cash', 'bank' => 'Bank Transfer', 'mobile' => 'Mobile Money', 'cheque' => 'Cheque'];
    }

    /**
     * Amount of this payment attributed to tuition, NHIF, or NACTVET QA (split receipts).
     */
    public function allocatedAmount(string $key): float
    {
        $a = $this->allocation;
        if (! is_array($a) || count($a) === 0) {
            return $key === 'tuition' ? (float) $this->amount : 0.0;
        }

        return (float) ($a[$key] ?? 0);
    }

    public function componentReference(string $component): ?string
    {
        $a = $this->allocation;
        if (! is_array($a) || ! isset($a['refs']) || ! is_array($a['refs'])) {
            return null;
        }

        $ref = trim((string) ($a['refs'][$component] ?? ''));

        return $ref !== '' ? $ref : null;
    }

    /**
     * Which semester(s) this payment's tuition covers, from the category chosen at
     * payment time — "new_student" is Semester I only, everything else covers both.
     */
    public function semesterLabel(): ?string
    {
        if ($this->covers_semester_two_only) {
            return 'Semester II';
        }

        return match ($this->tuition_category) {
            'new_student' => 'Semester I',
            'continue', 'repeat', 'transfer' => 'Semester I & II',
            default => null,
        };
    }
}
