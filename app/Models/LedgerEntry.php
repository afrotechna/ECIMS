<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    protected $fillable = [
        'student_id',
        'type',
        'amount',
        'description',
        'reference_type',
        'reference_id',
        'balance_after',
    ];

    protected $casts = [
        'amount' => 'decimal:0',
        'balance_after' => 'decimal:0',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
