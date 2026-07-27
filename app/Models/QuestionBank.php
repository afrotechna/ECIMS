<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionBank extends Model
{
    protected $fillable = [
        'course_id',
        'created_by',
        'name',
        'description',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(QuestionMaterial::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuestionItem::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(ExamPaper::class);
    }
}
