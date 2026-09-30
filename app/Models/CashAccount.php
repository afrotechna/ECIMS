<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashAccount extends Model
{
    protected $fillable = [
        'name',
        'code',
        'opening_balance',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(InstitutionTransaction::class);
    }

    /** opening_balance + SUM(in) - SUM(out) over non-voided transactions. */
    public function balance(): float
    {
        $in = (float) $this->transactions()->where('direction', 'in')->where('voided', false)->sum('amount');
        $out = (float) $this->transactions()->where('direction', 'out')->where('voided', false)->sum('amount');

        return (float) $this->opening_balance + $in - $out;
    }

    public static function grandTotal(): float
    {
        return static::where('is_active', true)->get()->sum(fn (self $a) => $a->balance());
    }
}
