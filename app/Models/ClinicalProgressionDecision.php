<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalProgressionDecision extends Model
{
    public const DECISION_PROCEED = 'proceed';

    public const DECISION_REPEAT = 'repeat_module';

    public const DECISION_REMEDIATION = 'remediation_required';

    public const DECISION_NOT_READY = 'not_ready';

    protected $fillable = [
        'student_id',
        'semester_id',
        'decision',
        'notes',
        'decided_by',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * @return array<string, string>
     */
    public static function decisionOptions(): array
    {
        return [
            self::DECISION_PROCEED => 'Proceed — competent for progression',
            self::DECISION_REPEAT => 'Repeat module(s)',
            self::DECISION_REMEDIATION => 'Remediation required before progression',
            self::DECISION_NOT_READY => 'Not ready — decision deferred',
        ];
    }

    public static function decisionLabel(string $decision): string
    {
        return self::decisionOptions()[$decision] ?? $decision;
    }
}
