<?php

namespace App\Models;

use App\Support\GradingScale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    protected $fillable = [
        'student_id',
        'course_id',
        'semester_id',
        'ca_mark',
        'ca_test1',
        'ca_test2',
        'ca_assignment1',
        'ca_assignment2',
        'ca_practical',
        'exam_mark',
        'total_mark',
        'grade',
        'ca_eligibility',
        'grade_source',
        'is_locked',
        'status',
        'approved_by',
        'approved_at',
        'review_notes',
    ];

    protected $casts = [
        'ca_mark' => 'decimal:2',
        'ca_test1' => 'decimal:2',
        'ca_test2' => 'decimal:2',
        'ca_assignment1' => 'decimal:2',
        'ca_assignment2' => 'decimal:2',
        'ca_practical' => 'decimal:2',
        'exam_mark' => 'decimal:2',
        'total_mark' => 'decimal:2',
        'is_locked' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending_approval' => 'Pending approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /** Compute CA mark from components (two tests, two assignments, optional practical). */
    public function computeCaFromComponents(): void
    {
        $parts = [];
        foreach (['ca_test1', 'ca_test2', 'ca_assignment1', 'ca_assignment2'] as $attr) {
            if ($this->{$attr} !== null && $this->{$attr} !== '') {
                $parts[] = (float) $this->{$attr};
            }
        }
        $course = $this->course ?? $this->course()->first();
        if ($course && ! empty($course->has_practical) && $this->ca_practical !== null && $this->ca_practical !== '') {
            $parts[] = (float) $this->ca_practical;
        }
        if (count($parts) > 0) {
            $this->ca_mark = round(array_sum($parts) / count($parts), 2);
        }
    }

    /** Compute total from CA and exam using course weights, and set grade (manual entry only). */
    public function computeTotalAndGrade(): void
    {
        if ($this->grade_source === 'nactvet') {
            return;
        }
        $this->computeCaFromComponents();
        $ca = (float) $this->ca_mark;
        $exam = (float) $this->exam_mark;
        $course = $this->course ?? $this->course()->first();
        $total = ($ca * ($course->ca_weight / 100)) + ($exam * ($course->exam_weight / 100));
        $this->total_mark = round($total, 2);
        $this->grade = self::markToGrade((float) $this->total_mark);
    }

    public static function markToGrade(float $mark): string
    {
        if ($mark >= 75) {
            return 'A';
        }
        if ($mark >= 70) {
            return 'B+';
        }
        if ($mark >= 65) {
            return 'B';
        }
        if ($mark >= 60) {
            return 'C';
        }
        if ($mark >= 50) {
            return 'D';
        }
        if ($mark >= 40) {
            return 'E';
        }

        return 'F';
    }

    /**
     * PASS / FAIL for the module from CA (AVCA). FAIL = not allowed to sit end-of-semester exam for this module.
     */
    public function caModuleRemark(): ?string
    {
        $stored = strtoupper(trim((string) ($this->ca_eligibility ?? '')));
        if ($stored === 'PASS' || $stored === 'FAIL') {
            return $stored;
        }

        return self::caRemarkFromMark($this->ca_mark);
    }

    public static function caRemarkFromMark(mixed $caMark): ?string
    {
        if ($caMark === null || $caMark === '') {
            return null;
        }

        $mark = (float) $caMark;
        $max = max(1.0, (float) config('college.ca_max_mark', 40));
        $minPercent = (float) config('college.ca_pass_percent', 40);
        $percent = ($mark / $max) * 100;

        return $percent >= $minPercent ? 'PASS' : 'FAIL';
    }

    public function caFailedModule(): bool
    {
        return $this->caModuleRemark() === 'FAIL';
    }

    /** Student-facing remark label (Pass / Failed / Incomplete). */
    public function caDisplayRemark(): string
    {
        return match ($this->caModuleRemark()) {
            'PASS' => 'Pass',
            'FAIL' => 'Failed',
            default => 'Incomplete',
        };
    }

    /** CSS class suffix for remarks cell styling on the student CA table. */
    public function caRemarkCellClass(): string
    {
        return match ($this->caModuleRemark()) {
            'PASS' => 'pass',
            'FAIL' => 'failed',
            default => 'incomplete',
        };
    }

    public function maySitSemesterExam(): bool
    {
        $remark = $this->caModuleRemark();

        return $remark === null || $remark === 'PASS';
    }

    /** End-of-semester module outcome (Pass / Failed / Incomplete). */
    public function finalDisplayRemark(): string
    {
        return match ($this->finalOutcome()) {
            'pass' => 'Pass',
            'fail' => 'Failed',
            default => 'Incomplete',
        };
    }

    public function finalRemarkCellClass(): string
    {
        return match ($this->finalOutcome()) {
            'pass' => 'pass',
            'fail' => 'failed',
            default => 'incomplete',
        };
    }

    /** @return 'pass'|'fail'|'incomplete' */
    public function finalOutcome(): string
    {
        $grade = strtoupper(trim((string) ($this->grade ?? '')));
        if ($grade === 'F') {
            return 'fail';
        }
        if ($grade === GradingScale::GRADE_INCOMPLETE) {
            return 'incomplete';
        }
        if ($grade !== '') {
            return 'pass';
        }
        if ($this->total_mark !== null) {
            $derived = self::markToGrade((float) $this->total_mark);

            return $derived === 'F' ? 'fail' : 'pass';
        }
        if ($this->exam_mark !== null || $this->ca_mark !== null) {
            return 'incomplete';
        }

        return 'incomplete';
    }

    public static function gradeToPoints(?string $grade): ?float
    {
        return GradingScale::gradeToPoints($grade);
    }

    public function gradePointValue(): ?float
    {
        return self::gradeToPoints($this->grade);
    }

    public function pointsTimesCredits(): ?float
    {
        $p = $this->gradePointValue();
        $n = (float) ($this->course->credits ?? 0);
        if ($p === null || $n <= 0) {
            return null;
        }

        return round($p * $n, 2);
    }

    public function gradePointsEarned(): ?float
    {
        $credits = (float) ($this->course->credits ?? 0);
        if ($credits <= 0) {
            return null;
        }
        $points = self::gradeToPoints($this->grade);
        if ($points === null) {
            return null;
        }

        return round($credits * $points, 2);
    }
}
