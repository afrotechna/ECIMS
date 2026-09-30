<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionTransaction extends Model
{
    protected $fillable = [
        'cash_account_id',
        'direction',
        'amount',
        'txn_date',
        'category',
        'description',
        'budget_line_id',
        'creditor_id',
        'department_id',
        'voided',
        'void_reason',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'txn_date' => 'date',
        'voided' => 'boolean',
    ];

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class);
    }

    public function budgetLine(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class);
    }

    public function creditor(): BelongsTo
    {
        return $this->belongsTo(Creditor::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
