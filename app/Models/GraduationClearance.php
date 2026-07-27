<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GraduationClearance extends Model
{
    protected $fillable = ['student_id', 'library_cleared', 'finance_cleared', 'accommodation_cleared', 'academic_cleared', 'notes'];

    public const CLEARED_VALUES = ['yes' => 'Yes', 'no' => 'No'];

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function isFullyCleared(): bool
    {
        return $this->library_cleared === 'yes' && $this->finance_cleared === 'yes' && $this->accommodation_cleared === 'yes' && $this->academic_cleared === 'yes';
    }
}
