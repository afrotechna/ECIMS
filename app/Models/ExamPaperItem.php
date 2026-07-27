<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamPaperItem extends Model
{
    protected $fillable = [
        'exam_paper_id',
        'question_item_id',
        'section_label',
        'section_instruction',
        'question_order',
        'marks',
    ];

    protected $casts = [
        'marks' => 'decimal:2',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(ExamPaper::class, 'exam_paper_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionItem::class, 'question_item_id');
    }
}
