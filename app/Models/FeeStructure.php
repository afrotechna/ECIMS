<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasSoftDeleteAudit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeStructure extends Model
{
    use SoftDeletes, HasSoftDeleteAudit;

    protected $fillable = [
        'academic_year',
        'programme_id',
        'tuition',
        'nhif',
        'nactvet_qa',
        'accommodation',
        'other_charges',
        'is_active',
    ];

    protected $casts = [
        'tuition' => 'decimal:0',
        'nhif' => 'decimal:0',
        'nactvet_qa' => 'decimal:0',
        'accommodation' => 'decimal:0',
        'other_charges' => 'decimal:0',
        'is_active' => 'boolean',
    ];

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function feeStructureSemesters(): HasMany
    {
        return $this->hasMany(FeeStructureSemester::class)->orderBy('semester_number');
    }

    /**
     * Expected tuition for a semester row (uses repeat/transfer rate for semester II when applicable).
     */
    public function expectedTuitionForSemester(int $semesterNumber, Student $student): float
    {
        $rows = $this->relationLoaded('feeStructureSemesters')
            ? $this->feeStructureSemesters
            : $this->feeStructureSemesters()->get();
        $row = $rows->firstWhere('semester_number', $semesterNumber);

        if ($row) {
            if ($semesterNumber === 2) {
                $band = $student->semesterTwoFeeBandEffective();

                return (float) ($band === 'repeat_transfer'
                    ? ($row->tuition_repeat_transfer ?? $row->tuition)
                    : $row->tuition);
            }

            return (float) $row->tuition;
        }

        $total = (float) $this->tuition;
        if ($total <= 0) {
            return 0.0;
        }
        if ($semesterNumber === 1) {
            return (float) round($total * 595 / 920);
        }
        if ($semesterNumber === 2) {
            $continuous = (float) round($total * 325 / 920);
            $repeat = (float) round($total * 595 / 920);

            return $student->semesterTwoFeeBandEffective() === 'repeat_transfer' ? $repeat : $continuous;
        }

        return 0.0;
    }

    /**
     * College NHIF amount shown on control sheet for semester I (0 if exempt).
     */
    public function expectedNhifForSemester(int $semesterNumber, Student $student): float
    {
        if ($semesterNumber !== 1 || $student->has_personal_nhif) {
            return 0.0;
        }
        $rows = $this->relationLoaded('feeStructureSemesters')
            ? $this->feeStructureSemesters
            : $this->feeStructureSemesters()->get();
        $row = $rows->firstWhere('semester_number', 1);

        return (float) ($row ? $row->nhif : $this->nhif);
    }

    /**
     * NACTVET QA expected for the selected semester (typically semester I only).
     */
    public function expectedNactvetQaForSemester(int $semesterNumber): float
    {
        if ($semesterNumber !== 1) {
            return 0.0;
        }
        $rows = $this->relationLoaded('feeStructureSemesters')
            ? $this->feeStructureSemesters
            : $this->feeStructureSemesters()->get();
        $row = $rows->firstWhere('semester_number', 1);

        return (float) ($row ? $row->nactvet_qa : $this->nactvet_qa);
    }

    public function getTotalAttribute(): float
    {
        return $this->annualTotal();
    }

    /**
     * Per-semester amounts and annual total (continuous Semester II tuition for annual sum).
     *
     * @return array{
     *     semester_one: array{tuition: float, nhif: float, nactvet_qa: float, subtotal: float},
     *     semester_two: array{tuition_continuous: float, tuition_repeat_transfer: float, subtotal: float},
     *     accommodation: float,
     *     other_charges: float,
     *     annual_total: float
     * }
     */
    public function semesterTotals(): array
    {
        $slots = $this->scheduledFeeSlots();

        $semesterOne = [
            'tuition' => (float) $slots['sem1_tuition'],
            'nhif' => (float) $slots['sem1_nhif'],
            'nactvet_qa' => (float) $slots['sem1_nactvet_qa'],
        ];
        $semesterOne['subtotal'] = $semesterOne['tuition'] + $semesterOne['nhif'] + $semesterOne['nactvet_qa'];

        $semesterTwo = [
            'tuition_continuous' => (float) $slots['sem2_continuous'],
            'tuition_repeat_transfer' => (float) $slots['sem2_repeat'],
        ];
        $semesterTwo['subtotal'] = $semesterTwo['tuition_continuous'];

        $accommodation = (float) $this->accommodation;
        $otherCharges = (float) $this->other_charges;

        return [
            'semester_one' => $semesterOne,
            'semester_two' => $semesterTwo,
            'accommodation' => $accommodation,
            'other_charges' => $otherCharges,
            'annual_total' => $semesterOne['subtotal'] + $semesterTwo['subtotal'] + $accommodation + $otherCharges,
        ];
    }

    public function annualTotal(): float
    {
        return $this->semesterTotals()['annual_total'];
    }

    public function getLabelAttribute(): string
    {
        $p = $this->programme ? $this->programme->code : 'All';

        return $this->academic_year.' — '.$p;
    }

    /**
     * Resolve active fee schedule for a programme and session start year (e.g. 2025 for 2025/2026).
     */
    public static function forProgrammeAndYear(int $programmeId, int $academicYearStart): ?self
    {
        $specific = static::query()
            ->with('feeStructureSemesters')
            ->where('academic_year', $academicYearStart)
            ->where('is_active', true)
            ->where('programme_id', $programmeId)
            ->first();
        if ($specific) {
            return $specific;
        }

        return static::query()
            ->with('feeStructureSemesters')
            ->where('academic_year', $academicYearStart)
            ->where('is_active', true)
            ->whereNull('programme_id')
            ->first();
    }

    /**
     * Scheduled TZS amounts per fee line (for payment entry: choose 0 or full scheduled amount per line).
     *
     * @return array{sem1_tuition: int, sem1_nhif: int, sem1_nactvet_qa: int, sem2_continuous: int, sem2_repeat: int}
     */
    public function scheduledFeeSlots(): array
    {
        $rows = $this->relationLoaded('feeStructureSemesters')
            ? $this->feeStructureSemesters
            : $this->feeStructureSemesters()->get();
        $s1 = $rows->firstWhere('semester_number', 1);
        $s2 = $rows->firstWhere('semester_number', 2);

        $t = 0;
        $h = 0;
        $q = 0;
        if ($s1) {
            $t = (int) $s1->tuition;
            $h = (int) $s1->nhif;
            $q = (int) $s1->nactvet_qa;
        } elseif ((float) $this->tuition > 0 || (float) $this->nhif > 0 || (float) $this->nactvet_qa > 0) {
            $total = (float) $this->tuition;
            $t = $total > 0 ? (int) round($total * 595 / 920) : 0;
            $h = (int) $this->nhif;
            $q = (int) $this->nactvet_qa;
        }

        $c = 0;
        $r = 0;
        if ($s2) {
            $c = (int) $s2->tuition;
            $r = (int) ($s2->tuition_repeat_transfer ?? $s2->tuition);
        } elseif ((float) $this->tuition > 0) {
            $total = (float) $this->tuition;
            $c = (int) round($total * 325 / 920);
            $r = (int) round($total * 595 / 920);
        }

        return [
            'sem1_tuition' => $t,
            'sem1_nhif' => $h,
            'sem1_nactvet_qa' => $q,
            'sem2_continuous' => $c,
            'sem2_repeat' => $r,
        ];
    }

    /**
     * Pick active fee schedule for a student. Tries the semester session start year first, then ±1 year
     * so minor mismatches between Semesters and Fee structure still resolve when schedules exist.
     */
    public static function resolveForStudent(Student $student, int $academicYearStart, $feeStructures = null): ?self
    {
        $yearsToTry = array_values(array_unique([
            $academicYearStart,
            $academicYearStart - 1,
            $academicYearStart + 1,
        ]));

        foreach ($yearsToTry as $year) {
            $resolved = static::resolveForStudentExactYear($student, $year, $feeStructures);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, FeeStructure>|null  $feeStructures
     */
    private static function resolveForStudentExactYear(Student $student, int $academicYearStart, $feeStructures): ?self
    {
        $programmeId = (int) $student->programme_id;
        if ($feeStructures !== null) {
            $candidates = $feeStructures
                ->where('academic_year', $academicYearStart)
                ->where('is_active', true)
                ->filter(fn ($fs) => (int) $fs->programme_id === $programmeId || $fs->programme_id === null);
            $specific = $candidates->firstWhere('programme_id', $programmeId);

            return $specific ?? $candidates->firstWhere('programme_id', null);
        }

        return static::forProgrammeAndYear($programmeId, $academicYearStart);
    }
}
