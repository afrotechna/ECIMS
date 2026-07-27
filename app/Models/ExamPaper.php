<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamPaper extends Model
{
    protected $fillable = [
        'question_bank_id',
        'course_id',
        'created_by',
        'assessment_type',
        'exam_type',
        'module_code',
        'module_name',
        'nactvet_reg_number',
        'examination_number',
        'title',
        'instructions',
        'duration_minutes',
        'total_marks',
    ];

    protected $casts = [
        'total_marks' => 'decimal:2',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExamPaperItem::class)->orderBy('question_order');
    }
}
