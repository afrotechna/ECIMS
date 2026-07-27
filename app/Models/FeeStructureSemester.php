<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructureSemester extends Model
{
    protected $fillable = [
        'fee_structure_id',
        'semester_number',
        'tuition',
        'nhif',
        'nactvet_qa',
        'tuition_repeat_transfer',
    ];

    protected $casts = [
        'tuition' => 'decimal:0',
        'nhif' => 'decimal:0',
        'nactvet_qa' => 'decimal:0',
        'tuition_repeat_transfer' => 'decimal:0',
    ];

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }
}
