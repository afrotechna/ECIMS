<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultSemesterSummary extends Model
{
    protected $table = 'result_semester_summaries';

    protected $fillable = [
        'student_id',
        'semester_id',
        'gpa',
        'academic_remarks',
    ];

    protected $casts = [
        'gpa' => 'decimal:4',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
}
