<?php

namespace App\Models;

use App\Support\StudentSurname;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'reg_no',
        'official_registry_no',
        'admission_source',
        'nactvet_reg_no',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'email',
        'phone',
        'programme_id',
        'intake_year',
        'nta_level',
        'student_type',
        'transfer_date',
        'previous_institution',
        'previous_programme_id',
        'status',
        'graduated_at',
        'reporting_status',
        'reporting_date',
        'tuition_completion_pledge_date',
        'tuition_payment_ref',
        'nhif_payment_ref',
        'nhif_status',
        'nactvet_qa_status',
        'nactvet_qa_payment_ref',
        'joining_instructions_submitted',
        'academic_requirements',
        'submitted_certificates',
        'physical_supplies_ack',
        'class_property_received',
        'chair_number',
        'table_number',
        'guardian_name',
        'guardian_phone',
        'guardian_relationship',
        'academic_standing',
        'gender',
        'form_four_index',
        'class_group',
        'tuition_status_override',
        'has_personal_nhif',
        'semester_two_fee_band',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'transfer_date' => 'date',
        'reporting_date' => 'date',
        'graduated_at' => 'date',
        'tuition_completion_pledge_date' => 'date',
        'has_personal_nhif' => 'boolean',
        'physical_supplies_ack' => 'array',
        'submitted_certificates' => 'array',
    ];

    public const CERTIFICATE_OPTIONS = [
        'medical_examination_form' => 'Medical examination form',
        'form_iv_certificate' => 'Copy of Form IV certificate',
        'birth_certificate' => 'Birth certificate',
    ];

    /** JSON key: "{academic_year}_{semester_number}" e.g. 2025_1 for Semester I of 2025/2026. */
    public static function physicalSuppliesStorageKey(int $academicYear, int $semesterNumber): string
    {
        return $academicYear.'_'.$semesterNumber;
    }

    /**
     * @return array{gloves?: bool, ream?: bool}|null
     */
    public function physicalSuppliesForSemester(int $academicYear, int $semesterNumber): ?array
    {
        $key = self::physicalSuppliesStorageKey($academicYear, $semesterNumber);
        $all = $this->physical_supplies_ack ?? [];

        return isset($all[$key]) && is_array($all[$key]) ? $all[$key] : null;
    }

    /**
     * Merge nested supplies[year][period][gloves|ream] from forms; empty string removes that slot or item.
     *
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $input
     * @return array<string, array{gloves?: bool, ream?: bool}>
     */
    public static function mergePhysicalSuppliesFromInput(array $existing, array $input): array
    {
        foreach ($input as $yearKey => $periods) {
            if (! is_array($periods)) {
                continue;
            }
            $year = (int) $yearKey;
            if ($year < 2000 || $year > 2100) {
                continue;
            }
            foreach ($periods as $periodKey => $items) {
                if (! is_array($items)) {
                    continue;
                }
                $period = (int) $periodKey;
                if ($period !== Semester::PERIOD_FIRST && $period !== Semester::PERIOD_SECOND) {
                    continue;
                }
                $key = self::physicalSuppliesStorageKey($year, $period);
                $g = $items['gloves'] ?? '';
                $r = $items['ream'] ?? '';
                if ($g === '' && $r === '') {
                    unset($existing[$key]);

                    continue;
                }
                $row = $existing[$key] ?? [];
                if ($g !== '') {
                    $row['gloves'] = $g === '1' || $g === 1 || $g === true;
                }
                if ($r !== '') {
                    $row['ream'] = $r === '1' || $r === 1 || $r === true;
                }
                $existing[$key] = $row;
            }
        }

        return $existing;
    }

    public const SEMESTER_TWO_FEE_BANDS = [
        'continuous' => 'Continuous (Sem II lower tuition)',
        'repeat_transfer' => 'Repeat / transferred (Sem II full tuition)',
    ];

    public const NTA_LEVELS = [4 => 'NTA Level 4 (First Year)', 5 => 'NTA Level 5 (Second Year)', 6 => 'NTA Level 6 (Third Year)'];

    public const STUDENT_TYPES = ['regular' => 'Regular', 'transferred' => 'Transferred'];

    public const REPORTING_STATUSES = [
        'reported' => 'Reported',
        'not_reported' => 'Not reported',
        'postponed' => 'Postponed',
        'absconded' => 'Absconded',
    ];

    public const FEE_STATUSES = [
        'PAID' => 'PAID',
        'NOT PAID' => 'NOT PAID',
    ];

    public const ACADEMIC_STANDINGS = [
        'good_standing' => 'Good standing',
        'probation' => 'Probation',
        'repeat_year' => 'Repeat year',
        'excluded' => 'Excluded',
    ];

    /** Override for admission control sheet tuition column when set; otherwise derived from fee vs payments. */
    public const TUITION_STATUS_OVERRIDES = [
        'loan_beneficiary' => 'Loan beneficiary (shows as LOAN BENEFICIARY)',
        'completed' => 'Completed (COMPLETED)',
        'not_paid' => 'Not paid (NOT PAID)',
        'partial' => 'Partial (PARTIAL)',
        'paid' => 'Paid (PAID)',
    ];

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    /** Clinical rotation groups (NTA 5–6) this student is assigned to. */
    public function clinicalRotationGroups(): BelongsToMany
    {
        return $this->belongsToMany(ClinicalRotationGroup::class, 'crt_group_students')
            ->withPivot('position')
            ->withTimestamps();
    }

    public function previousProgramme(): BelongsTo
    {
        return $this->belongsTo(Programme::class, 'previous_programme_id');
    }

    public function accommodationAllocations(): HasMany
    {
        return $this->hasMany(AccommodationAllocation::class);
    }

    public function activeAccommodationAllocation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AccommodationAllocation::class)->where('status', 'active')->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function semesterRegistrations(): HasMany
    {
        return $this->hasMany(SemesterRegistration::class);
    }

    public function moduleEnrollments(): HasMany
    {
        return $this->hasMany(StudentModuleEnrollment::class);
    }

    public function clinicalLogbookEntries(): HasMany
    {
        return $this->hasMany(ClinicalLogbookEntry::class);
    }

    public function clinicalRemediationPlans(): HasMany
    {
        return $this->hasMany(ClinicalRemediationPlan::class);
    }

    public function clinicalProgressionDecisions(): HasMany
    {
        return $this->hasMany(ClinicalProgressionDecision::class);
    }

    /**
     * Student portal dashboard: semester registration status (not "registered" until staff finish after payment).
     *
     * @return array{text: string, class: string}
     */
    public function semesterRegistrationBadge(): array
    {
        $regs = $this->semesterRegistrations()->with('semester')->orderByDesc('updated_at')->get();

        $approvedComplete = $regs->first(
            fn (SemesterRegistration $r) => $r->status === 'approved' && $r->wizard_step === null
        );
        if ($approvedComplete) {
            $label = $approvedComplete->semester?->label ?? 'semester';

            return [
                'text' => 'Registered — '.$label,
                'class' => 'bg-success',
            ];
        }

        $inProgress = $regs->first(fn (SemesterRegistration $r) => $r->wizard_step !== null);
        if ($inProgress) {
            return [
                'text' => 'Registration in progress (college)',
                'class' => 'bg-info',
            ];
        }

        $pending = $regs->first(fn (SemesterRegistration $r) => $r->status === 'pending');
        if ($pending) {
            return [
                'text' => 'Registration pending approval',
                'class' => 'bg-warning text-dark',
            ];
        }

        if ($regs->contains(fn (SemesterRegistration $r) => $r->status === 'rejected')) {
            return [
                'text' => 'Registration not approved — contact office',
                'class' => 'bg-danger',
            ];
        }

        return [
            'text' => 'Not registered for semester yet',
            'class' => 'bg-secondary',
        ];
    }

    public function hasCompletedSemesterRegistration(): bool
    {
        return $this->semesterRegistrations()
            ->where('status', 'approved')
            ->whereNull('wizard_step')
            ->exists();
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /** Balance = amount owed (debits - credits). Positive = student owes. */
    public function getBalanceAttribute(): float
    {
        $debits = (float) $this->ledgerEntries()->where('type', 'debit')->sum('amount');
        $credits = (float) $this->ledgerEntries()->where('type', 'credit')->sum('amount');

        return round($debits - $credits, 0);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function conductRecords(): HasMany
    {
        return $this->hasMany(ConductRecord::class);
    }

    public function studentDocuments(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function paymentInstalments(): HasMany
    {
        return $this->hasMany(PaymentInstalment::class);
    }

    public function graduationClearances(): HasMany
    {
        return $this->hasMany(GraduationClearance::class);
    }

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->first_name, $this->middle_name, $this->last_name]);

        return implode(' ', $parts);
    }

    /** Single family name (last word only) for portal password. */
    public function singleSurname(): string
    {
        return StudentSurname::extract($this->last_name, $this->first_name, $this->full_name);
    }

    /** Initial portal password: one surname, lowercase; student changes it on first login. */
    public function portalPassword(): string
    {
        return StudentSurname::portalPassword($this->last_name, $this->first_name, $this->full_name);
    }

    /**
     * Sum recorded payments allocated to a fee component (tuition, nhif, nactvet_qa).
     */
    public function sumPaymentAllocation(string $component): float
    {
        $payments = $this->relationLoaded('payments')
            ? $this->payments
            : $this->payments()->get();

        $sum = 0.0;
        foreach ($payments as $payment) {
            $sum += $payment->allocatedAmount($component);
        }

        return round($sum, 0);
    }

    /**
     * Tuition status label for admission / fee control sheet.
     */
    /**
     * NACTVET and Form IV registration numbers are the same; prefer stored NACTVET, then legacy Form IV index.
     */
    public function registrationNumberDisplay(): string
    {
        return trim((string) ($this->nactvet_reg_no ?: $this->form_four_index)) ?: '';
    }

    /**
     * Effective semester II tuition band (explicit flag, else inferred from transfer/repeat year).
     */
    public function semesterTwoFeeBandEffective(): string
    {
        if ($this->semester_two_fee_band === 'repeat_transfer' || $this->semester_two_fee_band === 'continuous') {
            return $this->semester_two_fee_band;
        }
        if (($this->student_type ?? '') === 'transferred') {
            return 'repeat_transfer';
        }
        if (($this->academic_standing ?? '') === 'repeat_year') {
            return 'repeat_transfer';
        }

        return 'continuous';
    }

    /**
     * NHIF / NACTVET QA status for control sheet (manual override if set, else from payments vs fee schedule).
     */
    public function admissionFeeComponentStatusLabel(
        ?float $expected,
        float $paid,
        ?string $manualStatus = null,
        bool $exempt = false,
        ?string $exemptLabel = null,
        int $semesterNumber = 1
    ): string {
        if ($manualStatus !== null && trim($manualStatus) !== '') {
            return $manualStatus;
        }
        if ($exempt && $exemptLabel) {
            return $exemptLabel;
        }
        if ($semesterNumber !== 1 || ($expected ?? 0) <= 0) {
            return $paid > 0 ? 'PAID' : '—';
        }
        if ($paid >= $expected) {
            return 'PAID';
        }
        if ($paid > 0) {
            return 'PARTIAL';
        }

        return 'NOT PAID';
    }

    /**
     * Payment references for a fee line (NHIF / NACTVET), from split receipts when not stored on student.
     */
    public function paymentRefsForComponent(string $component, ?string $manualRef = null): string
    {
        if ($manualRef !== null && trim($manualRef) !== '') {
            return $manualRef;
        }

        $payments = $this->relationLoaded('payments')
            ? $this->payments
            : $this->payments()->get();

        $refs = $payments
            ->filter(fn (Payment $p) => $p->allocatedAmount($component) > 0)
            ->sortByDesc('paid_at')
            ->map(fn (Payment $p) => $p->componentReference($component))
            ->filter()
            ->unique()
            ->take(5)
            ->values();

        if ($refs->isNotEmpty()) {
            return $refs->implode('; ');
        }

        return $payments
            ->filter(fn (Payment $p) => $p->allocatedAmount($component) > 0)
            ->sortByDesc('paid_at')
            ->pluck('reference')
            ->filter()
            ->take(5)
            ->implode('; ');
    }

    /**
     * @param  list<string>|null  $keys
     */
    public function submittedCertificatesLabel(?array $keys = null): string
    {
        $keys ??= $this->submitted_certificates ?? [];
        if (! is_array($keys) || $keys === []) {
            return '';
        }

        $labels = [];
        foreach ($keys as $key) {
            if (isset(self::CERTIFICATE_OPTIONS[$key])) {
                $labels[] = self::CERTIFICATE_OPTIONS[$key];
            }
        }

        return implode('; ', $labels);
    }

    public function admissionCertificatesDisplay(): string
    {
        $certs = $this->submittedCertificatesLabel();
        $notes = trim((string) $this->academic_requirements);

        if ($certs !== '' && $notes !== '') {
            return $certs.' · '.$notes;
        }

        return $certs !== '' ? $certs : $notes;
    }

    /** @return array{label: string, amount: float, class: string} */
    public function balanceSummary(): array
    {
        $bal = $this->balance;
        if ($bal > 0) {
            return ['label' => 'Amount owed', 'amount' => $bal, 'class' => 'danger'];
        }
        if ($bal < 0) {
            return ['label' => 'Credit balance', 'amount' => abs($bal), 'class' => 'info'];
        }

        return ['label' => 'Balance', 'amount' => 0.0, 'class' => 'success'];
    }

    public function admissionTuitionStatusLabel(?float $expectedTuition, float $tuitionPaid): string
    {
        if ($this->tuition_status_override) {
            return match ($this->tuition_status_override) {
                'loan_beneficiary' => 'LOAN BENEFICIARY',
                'completed' => 'COMPLETED',
                'not_paid' => 'NOT PAID',
                'partial' => 'PARTIAL',
                'paid' => 'PAID',
                default => strtoupper(str_replace('_', ' ', (string) $this->tuition_status_override)),
            };
        }

        if (($expectedTuition ?? 0) > 0) {
            if ($tuitionPaid >= $expectedTuition) {
                return 'Paid';
            }
            if ($tuitionPaid > 0) {
                return 'Partial';
            }

            return 'Not Paid';
        }

        return $tuitionPaid > 0 ? 'Paid' : '—';
    }
}
