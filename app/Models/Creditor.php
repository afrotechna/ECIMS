<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Creditor extends Model
{
    public const CATEGORIES = [
        'staff_claim' => 'Staff Claim',
        'supplier' => 'Supplier',
        'examination_body' => 'Examination Body',
        'other' => 'Other',
    ];

    public const VERIFICATION_STATUSES = [
        'not_yet_verified' => 'Not Yet Verified',
        'verified' => 'Verified',
        'partially_verified' => 'Partially Verified',
        'rejected' => 'Rejected',
        'paid_and_cleared' => 'Paid & Cleared',
    ];

    protected $fillable = [
        'category',
        'payee_name',
        'description',
        'amount_due',
        'date_incurred',
        'due_date',
        'verification_status',
        'department_id',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'date_incurred' => 'date',
        'due_date' => 'date',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InstitutionTransaction::class);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function statusLabel(): string
    {
        return self::VERIFICATION_STATUSES[$this->verification_status] ?? $this->verification_status;
    }

    public function paidTotal(): float
    {
        return (float) $this->transactions()->where('direction', 'out')->where('voided', false)->sum('amount');
    }

    public function balance(): float
    {
        return round((float) $this->amount_due - $this->paidTotal(), 2);
    }

    /** Ageing bucket for the due date, as of today — mirrors the plain-PHP sister app. */
    public function ageingBucket(): string
    {
        if (! $this->due_date) {
            return 'Not yet due';
        }
        $days = (int) floor((now()->startOfDay()->timestamp - $this->due_date->copy()->startOfDay()->timestamp) / 86400);
        if ($days <= 0) {
            return 'Not yet due';
        }
        if ($days <= 30) {
            return '1 - 30 days overdue';
        }
        if ($days <= 60) {
            return '31 - 60 days overdue';
        }
        if ($days <= 90) {
            return '61 - 90 days overdue';
        }

        return 'Over 90 days overdue';
    }
}
