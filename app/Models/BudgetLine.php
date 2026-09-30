<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetLine extends Model
{
    protected $fillable = [
        'department_id',
        'financial_year',
        'item_description',
        'annual_budget',
    ];

    protected $casts = [
        'annual_budget' => 'decimal:2',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InstitutionTransaction::class);
    }

    /** ['budget','spent','balance','pct'] against non-voided outgoing transactions. */
    public function actuals(): array
    {
        $budget = (float) $this->annual_budget;
        $spent = (float) $this->transactions()->where('direction', 'out')->where('voided', false)->sum('amount');

        return [
            'budget' => $budget,
            'spent' => $spent,
            'balance' => round($budget - $spent, 2),
            'pct' => $budget > 0 ? round(min(100, $spent / $budget * 100), 1) : 0.0,
        ];
    }
}
