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
        'internal_exam',
        'registration',
        'games',
        'emergency_fund',
        'practicum_guide',
        'national_exam',
        'accommodation',
    ];

    protected $casts = [
        'tuition' => 'decimal:0',
        'nhif' => 'decimal:0',
        'nactvet_qa' => 'decimal:0',
        'tuition_repeat_transfer' => 'decimal:0',
        'internal_exam' => 'decimal:0',
        'registration' => 'decimal:0',
        'games' => 'decimal:0',
        'emergency_fund' => 'decimal:0',
        'practicum_guide' => 'decimal:0',
        'national_exam' => 'decimal:0',
        'accommodation' => 'decimal:0',
    ];

    /** Sum of the informational breakdown items for this semester row (excludes tuition/nhif/nactvet_qa, which are billed separately). */
    public function breakdownTotal(): float
    {
        return (float) $this->internal_exam + (float) $this->registration + (float) $this->games
            + (float) $this->emergency_fund + (float) $this->practicum_guide
            + (float) $this->national_exam + (float) $this->accommodation;
    }

    /** The remaining "base" tuition after subtracting the itemized breakdown from the billed tuition figure. */
    public function baseTuition(): float
    {
        return max(0.0, (float) $this->tuition - $this->breakdownTotal());
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }
}
