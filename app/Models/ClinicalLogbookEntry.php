<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalLogbookEntry extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REMEDIATION = 'remediation';

    protected $fillable = [
        'student_id',
        'semester_id',
        'clinical_rotation_group_id',
        'clinical_procedure_id',
        'performed_on',
        'department_code',
        'hospital_code',
        'case_reference',
        'case_summary',
        'skills_notes',
        'status',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'reviewer_feedback',
    ];

    protected $casts = [
        'performed_on' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function rotationGroup(): BelongsTo
    {
        return $this->belongsTo(ClinicalRotationGroup::class, 'clinical_rotation_group_id');
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(ClinicalProcedure::class, 'clinical_procedure_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isEditableByStudent(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_SUBMITTED => 'Awaiting instructor',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Returned for revision',
            self::STATUS_REMEDIATION => 'Remediation',
            default => 'Draft',
        };
    }

    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_SUBMITTED => 'bg-warning text-dark',
            self::STATUS_APPROVED => 'bg-success',
            self::STATUS_REJECTED, self::STATUS_REMEDIATION => 'bg-danger',
            default => 'bg-secondary',
        };
    }
}
