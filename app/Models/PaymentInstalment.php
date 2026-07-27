<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentInstalment extends Model
{
    protected $fillable = [
        'student_id',
        'amount',
        'due_date',
        'paid_amount',
        'status',
        'label',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'partial' => 'Partial',
        'paid' => 'Paid',
        'overdue' => 'Overdue',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function getBalanceAttribute(): int
    {
        return max(0, (int) $this->amount - (int) $this->paid_amount);
    }
}
