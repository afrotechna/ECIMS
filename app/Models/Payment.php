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
     * A 14-digit, all-numeric receipt number: 2-digit college code + 2-digit year + 6-digit
     * payment id, followed by a 4-digit check block derived from those 10 digits via HMAC
     * (keyed on the app's own secret key). The id makes it unique per payment; the check
     * digits make it infeasible to fabricate a number that passes verifyReceiptNumber()
     * without knowing the app key, since any made-up digits won't produce a matching HMAC.
     */
    public function receiptNumber(): string
    {
        $collegeCode = str_pad(substr(preg_replace('/\D/', '', (string) config('college.receipt_college_code', '00')), 0, 2), 2, '0', STR_PAD_LEFT);
        $year = ($this->paid_at ?? $this->created_at ?? now())->format('y');
        $seq = str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
        $base = $collegeCode.$year.$seq;

        return $base.static::receiptCheckDigits($base);
    }

    public static function receiptCheckDigits(string $base): string
    {
        $hash = hash_hmac('sha256', $base, (string) config('app.key'));

        return str_pad((string) (hexdec(substr($hash, 0, 8)) % 10000), 4, '0', STR_PAD_LEFT);
    }

    public static function verifyReceiptNumber(string $receiptNumber): bool
    {
        if (! preg_match('/^\d{14}$/', $receiptNumber)) {
            return false;
        }

        return static::receiptCheckDigits(substr($receiptNumber, 0, 10)) === substr($receiptNumber, 10, 4);
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
     * Which semester this payment's tuition covers. Fees are billed one semester at a time,
     * so a payment covers Semester I unless it was recorded during Semester II registration.
     */
    public function semesterLabel(): ?string
    {
        if ($this->covers_semester_two_only) {
            return 'Semester II';
        }

        return $this->tuition_category !== null ? 'Semester I' : null;
    }
}
