<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionItem extends Model
{
    protected $fillable = [
        'question_bank_id',
        'course_id',
        'question_material_id',
        'type',
        'difficulty',
        'stem',
        'options',
        'answer_key',
        'rubric',
        'marks',
        'is_ai_generated',
        'created_by',
    ];

    protected $casts = [
        'options' => 'array',
        'answer_key' => 'array',
        'rubric' => 'array',
        'is_ai_generated' => 'boolean',
        'marks' => 'decimal:2',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(QuestionMaterial::class, 'question_material_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function examItems(): HasMany
    {
        return $this->hasMany(ExamPaperItem::class);
    }
}
