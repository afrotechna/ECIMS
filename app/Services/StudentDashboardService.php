<?php

namespace App\Services;

use App\Models\FeeStructure;
use App\Models\GraduationClearance;
use App\Models\LeaveApplication;
use App\Models\PaymentInstalment;
use App\Models\Result;
use App\Models\ResultSemesterSummary;
use App\Models\Semester;
use App\Models\SemesterRegistration;
use App\Models\Student;
use App\Support\GradingScale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StudentDashboardService
{
    public function __construct(
        private readonly StudentModuleEnrollmentService $moduleEnrollmentService,
    ) {}

    public function build(Student $student): array
    {
        $student->loadMissing('programme');

        $academicYearStart = \App\Support\AcademicSession::defaultStartYear();
        $academicYearLabel = $academicYearStart.'/'.($academicYearStart + 1);

        $semesters = Semester::forAcademicYear($academicYearStart, false)->sortBy('number')->values();

        $registrations = SemesterRegistration::query()
            ->where('student_id', $student->id)
            ->with('semester')
            ->get()
            ->keyBy('semester_id');

        $registrationRows = collect([Semester::PERIOD_FIRST, Semester::PERIOD_SECOND])
            ->map(fn (int $period) => $this->registrationRow(
                $period,
                $semesters->firstWhere('number', $period),
                $registrations
            ))
            ->all();

        $moduleStats = $this->moduleRegistrationSummary($student, $semesters);

        $allResults = Result::query()->where('student_id', $student->id)->get();
        $passedModules = $allResults->filter(fn (Result $r) => $this->isPassed($r))->unique('course_id')->count();
        $modulesWithMarks = $allResults->filter(fn (Result $r) => $r->total_mark !== null || $r->grade !== null)->unique('course_id')->count();

        $registeredSemesters = $registrations->filter(
            fn (SemesterRegistration $r) => $r->status === 'approved' && $r->wizard_step === null
        )->count();

        $student->loadMissing(['programme', 'payments', 'accommodationAllocations.room.hostel']);

        return [
            'academic_year_label' => $academicYearLabel,
            'academic_year_start' => $academicYearStart,
            'year_of_study' => $this->yearOfStudyLabel($student),
            'standing_badge' => $this->standingBadge($student),
            'overall_gpa' => $this->overallGpa($student),
            'total_modules_registered' => $moduleStats['enrolled'],
            'total_modules_available' => $moduleStats['available'],
            'registered_semester_count' => $registeredSemesters,
            'modules_completed' => $passedModules,
            'modules_with_marks' => max($modulesWithMarks, 1),
            'registration_rows' => $registrationRows,
            'quick_actions' => $this->quickActions($student),
            'student_info' => $this->studentInformation($student),
            'payment_groups' => $this->paymentDetailGroups($student, $academicYearStart),
            'loan_details' => $this->loanDetails($student),
            'permission_requests' => $this->permissionRequests($student),
        ];
    }

    /**
     * @return array{
     *     period: int,
     *     title: string,
     *     dates: ?string,
     *     status_key: string,
     *     status_label: string,
     *     status_class: string,
     *     status_icon: string,
     *     action_label: string,
     *     action_url: ?string,
     *     action_enabled: bool,
     * }
     */
    private function registrationRow(int $period, ?Semester $semester, Collection $registrations): array
    {
        $title = $period === Semester::PERIOD_FIRST ? 'Semester I' : 'Semester II';
        $reg = $semester ? $registrations->get($semester->id) : null;

        $dates = null;
        if ($semester?->start_date && $semester?->end_date) {
            $dates = $semester->start_date->format('M j, Y').' – '.$semester->end_date->format('M j, Y');
        }

        if (! $semester) {
            return $this->rowMeta($period, $title, $dates, 'unavailable', 'Not Available', 'secondary', 'bi-exclamation-circle', 'No Actions', null, false);
        }

        if ($reg && $reg->status === 'approved' && $reg->wizard_step === null) {
            return $this->rowMeta($period, $title, $dates, 'registered', 'Registered', 'success', 'bi-check-circle-fill', 'View registration', route('my.registrations'), true);
        }

        if ($reg && $reg->status === 'pending') {
            return $this->rowMeta($period, $title, $dates, 'pending', 'Pending approval', 'warning', 'bi-hourglass-split', 'View status', route('my.registrations'), true);
        }

        if ($reg && $reg->wizard_step !== null) {
            return $this->rowMeta($period, $title, $dates, 'in_progress', 'Registration in progress', 'info', 'bi-arrow-repeat', 'View status', route('my.registrations'), true);
        }

        if ($reg && $reg->status === 'rejected') {
            return $this->rowMeta($period, $title, $dates, 'rejected', 'Not approved', 'danger', 'bi-x-circle', 'Contact office', route('my.registrations'), true);
        }

        $windowOpen = $this->registrationWindowOpen($semester);

        if ($windowOpen) {
            return $this->rowMeta($period, $title, $dates, 'open', 'Registration Open', 'open', 'bi-clock', 'Register now', route('my.registrations'), true);
        }

        if (! $semester->is_active) {
            return $this->rowMeta($period, $title, $dates, 'unavailable', 'Not Available', 'secondary', 'bi-exclamation-circle', 'No Actions', null, false);
        }

        return $this->rowMeta($period, $title, $dates, 'closed', 'Registration closed', 'secondary', 'bi-lock', 'No Actions', null, false);
    }

    /**
     * @return array<string, mixed>
     */
    private function rowMeta(
        int $period,
        string $title,
        ?string $dates,
        string $statusKey,
        string $statusLabel,
        string $statusClass,
        string $statusIcon,
        string $actionLabel,
        ?string $actionUrl,
        bool $actionEnabled
    ): array {
        return [
            'period' => $period,
            'title' => $title,
            'dates' => $dates,
            'status_key' => $statusKey,
            'status_label' => $statusLabel,
            'status_class' => $statusClass,
            'status_icon' => $statusIcon,
            'action_label' => $actionLabel,
            'action_url' => $actionUrl,
            'action_enabled' => $actionEnabled,
        ];
    }

    private function registrationWindowOpen(Semester $semester): bool
    {
        if (! $semester->is_active) {
            return false;
        }

        $now = now()->startOfDay();

        if ($semester->start_date && $semester->end_date) {
            return $now->between($semester->start_date->startOfDay(), $semester->end_date->endOfDay());
        }

        return true;
    }

    /**
     * Modules the student is enrolled in for the current active semester, out of the
     * total offered for their programme/NTA level — this reflects modules assigned by
     * staff (bulk assignment or self-service selection), not anything a student "does".
     *
     * @return array{enrolled: int, available: int}
     */
    private function moduleRegistrationSummary(Student $student, Collection $semesters): array
    {
        $currentSemester = $semesters->firstWhere('is_active', true) ?? $semesters->first();

        if (! $currentSemester || ! $student->programme_id) {
            return ['enrolled' => 0, 'available' => 0];
        }

        $enrolled = $student->moduleEnrollments()->where('semester_id', $currentSemester->id)->count();
        $available = $this->moduleEnrollmentService->availableCoursesForSemester($student, $currentSemester)->count();

        return ['enrolled' => $enrolled, 'available' => max($available, $enrolled)];
    }

    private function yearOfStudyLabel(Student $student): string
    {
        return match ((int) $student->nta_level) {
            4 => '1st Year of Study',
            5 => '2nd Year of Study',
            6 => '3rd Year of Study',
            default => Student::NTA_LEVELS[(int) $student->nta_level] ?? 'Student',
        };
    }

    /**
     * @return array{text: string, class: string}
     */
    private function standingBadge(Student $student): array
    {
        if ($student->status === 'graduated') {
            return ['text' => 'Graduated', 'class' => 'sd-badge-graduate'];
        }

        $clearance = GraduationClearance::query()->where('student_id', $student->id)->first();
        if ($clearance?->isFullyCleared()) {
            return ['text' => 'Cleared for graduation', 'class' => 'sd-badge-graduate'];
        }

        if ((int) $student->nta_level === 6) {
            return ['text' => 'Final year student', 'class' => 'sd-badge-graduate'];
        }

        $standing = Student::ACADEMIC_STANDINGS[$student->academic_standing ?? ''] ?? null;
        if ($standing && $student->academic_standing !== 'good_standing') {
            return ['text' => $standing, 'class' => 'sd-badge-warning'];
        }

        if ($student->hasCompletedSemesterRegistration()) {
            return ['text' => 'Active student', 'class' => 'sd-badge-active'];
        }

        return ['text' => 'Not registered this semester', 'class' => 'sd-badge-muted'];
    }

    private function overallGpa(Student $student): float
    {
        $results = Result::query()
            ->where('student_id', $student->id)
            ->with('course')
            ->get();

        $gpa = GradingScale::computeGpa($results);
        if ($gpa !== null) {
            return round($gpa, 2);
        }

        $summaries = ResultSemesterSummary::query()
            ->where('student_id', $student->id)
            ->whereNotNull('gpa')
            ->pluck('gpa');

        if ($summaries->isNotEmpty()) {
            return round((float) $summaries->avg(), 2);
        }

        return 0.0;
    }

    private function isPassed(Result $result): bool
    {
        $grade = strtoupper(trim((string) ($result->grade ?? '')));
        if ($grade === 'F' || $grade === GradingScale::GRADE_INCOMPLETE) {
            return false;
        }
        if ($grade !== '') {
            return true;
        }
        if ($result->total_mark !== null) {
            return Result::markToGrade((float) $result->total_mark) !== 'F';
        }

        return false;
    }

    /**
     * @return list<array{title: string, subtitle: string, url: string, icon: string, percent: ?int}>
     */
    private function quickActions(Student $student): array
    {
        $actions = [
            [
                'title' => 'Verify your information',
                'subtitle' => 'Confirm your profile details are correct',
                'url' => route('students.show', $student),
                'icon' => 'bi-person-check',
                'percent' => $this->profileCompletePercent($student),
            ],
        ];

        $clearance = GraduationClearance::query()->where('student_id', $student->id)->first();
        if ($clearance || (int) $student->nta_level >= 5) {
            $actions[] = [
                'title' => 'Graduation clearance',
                'subtitle' => 'Library, finance, accommodation & academic',
                'url' => route('students.show', $student).'#clearance',
                'icon' => 'bi-mortarboard',
                'percent' => $this->clearancePercent($clearance),
            ];
        }

        $feeBal = $student->balanceSummary();
        $actions[] = [
            'title' => 'Fees & payments',
            'subtitle' => $feeBal['label'].': '.number_format($feeBal['amount'], 0).' TZS',
            'url' => route('students.ledger', $student),
            'icon' => 'bi-wallet2',
            'percent' => null,
        ];

        return $actions;
    }

    private function profileCompletePercent(Student $student): int
    {
        $fields = [
            $student->phone,
            $student->email,
            $student->date_of_birth,
            $student->guardian_name,
            $student->guardian_phone,
            $student->registrationNumberDisplay(),
        ];
        $filled = collect($fields)->filter(fn ($v) => trim((string) $v) !== '')->count();

        return (int) round(($filled / max(count($fields), 1)) * 100);
    }

    private function clearancePercent(?GraduationClearance $clearance): int
    {
        if (! $clearance) {
            return 0;
        }

        $keys = ['library_cleared', 'finance_cleared', 'accommodation_cleared', 'academic_cleared'];
        $yes = collect($keys)->filter(fn ($k) => $clearance->{$k} === 'yes')->count();

        return (int) round(($yes / count($keys)) * 100);
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function studentInformation(Student $student): array
    {
        return [
            ['label' => 'Full name', 'value' => $student->full_name],
            ['label' => 'Registration number', 'value' => $student->registrationNumberDisplay() ?: $student->reg_no],
            ['label' => 'College reg. no.', 'value' => $student->reg_no],
            ['label' => 'Programme', 'value' => $student->programme?->name ?? '—'],
            ['label' => 'NTA level', 'value' => Student::NTA_LEVELS[(int) $student->nta_level] ?? '—'],
            ['label' => 'Year of study', 'value' => $this->yearOfStudyLabel($student)],
            ['label' => 'Email', 'value' => $student->email ?: '—'],
            ['label' => 'Phone', 'value' => $student->phone ?: '—'],
            ['label' => 'Gender', 'value' => ucfirst((string) ($student->gender ?? '—'))],
            ['label' => 'Date of birth', 'value' => $student->date_of_birth?->format('d M Y') ?? '—'],
            ['label' => 'Class group', 'value' => $student->class_group ?: '—'],
            ['label' => 'Reporting status', 'value' => Student::REPORTING_STATUSES[$student->reporting_status ?? ''] ?? ($student->reporting_status ?: '—')],
            ['label' => 'Guardian', 'value' => trim($student->guardian_name.' · '.$student->guardian_phone) ?: '—'],
        ];
    }

    /**
     * @return list<array{title: string, subtitle: string, items: list<array<string, mixed>>}>
     */
    private function paymentDetailGroups(Student $student, int $academicYearStart): array
    {
        $fs = FeeStructure::resolveForStudent($student, $academicYearStart);
        if (! $fs) {
            return [];
        }

        $expiry = Carbon::create($academicYearStart + 1, 10, 17, 23, 59, 59);
        $index = 0;
        $isNewStudent = (int) $student->intake_year === $academicYearStart;

        $tuitionPaid = $student->sumPaymentAllocation('tuition');
        $sem1Expected = (float) $fs->expectedTuitionForSemester(Semester::PERIOD_FIRST, $student);
        $sem2Expected = (float) $fs->expectedTuitionForSemester(Semester::PERIOD_SECOND, $student);
        $sem1Paid = min($tuitionPaid, $sem1Expected);
        $sem2Paid = max(0.0, $tuitionPaid - $sem1Paid);

        $sem1Items = [];
        if ($sem1Expected > 0) {
            $sem1Items[] = $this->feePaymentItem(
                $this->paymentLine(++$index, 'Tuition fee', 'tuition_sem1', $student, $sem1Expected, $sem1Paid, $expiry)
            );
        }

        $nhifExpected = (float) $fs->expectedNhifForSemester(Semester::PERIOD_FIRST, $student);
        if ($nhifExpected > 0) {
            $nhifPaid = $student->sumPaymentAllocation('nhif');
            $sem1Items[] = $this->feePaymentItem(
                $this->paymentLine(++$index, 'NHIF', 'nhif', $student, $nhifExpected, $nhifPaid, $expiry)
            );
        }

        $qaExpected = (float) $fs->expectedNactvetQaForSemester(Semester::PERIOD_FIRST);
        if ($qaExpected > 0) {
            $qaPaid = $student->sumPaymentAllocation('nactvet_qa');
            $sem1Items[] = $this->feePaymentItem(
                $this->paymentLine(++$index, 'NACTVET QA fee', 'nactvet_qa', $student, $qaExpected, $qaPaid, $expiry)
            );
        }

        if ($isNewStudent) {
            $certDone = strtolower(trim((string) $student->joining_instructions_submitted)) === 'yes'
                || $this->certificatesSubmitted($student);
            $sem1Items[] = $this->requirementItem(
                'Submit certificates',
                'Form IV certificate, birth certificate, and other joining documents (new students).',
                $certDone
            );
        }

        $sem1Items[] = $this->requirementItem(
            'Gloves',
            'Bring clinical gloves (required every semester — not a bank fee).',
            $this->physicalSupplySubmitted($student, $academicYearStart, Semester::PERIOD_FIRST, 'gloves')
        );
        $sem1Items[] = $this->requirementItem(
            'Ream (A4 paper)',
            'Bring one ream of A4 paper (required every semester — not a bank fee).',
            $this->physicalSupplySubmitted($student, $academicYearStart, Semester::PERIOD_FIRST, 'ream')
        );

        $sem2Items = [];
        if ($sem2Expected > 0) {
            $sem2Items[] = $this->feePaymentItem(
                $this->paymentLine(
                    ++$index,
                    'Tuition fee (complete remaining balance)',
                    'tuition_sem2',
                    $student,
                    $sem2Expected,
                    $sem2Paid,
                    $expiry
                )
            );
        }

        $sem2Items[] = $this->requirementItem(
            'Gloves',
            'Bring clinical gloves (required every semester — not a bank fee).',
            $this->physicalSupplySubmitted($student, $academicYearStart, Semester::PERIOD_SECOND, 'gloves')
        );
        $sem2Items[] = $this->requirementItem(
            'Ream (A4 paper)',
            'Bring one ream of A4 paper (required every semester — not a bank fee).',
            $this->physicalSupplySubmitted($student, $academicYearStart, Semester::PERIOD_SECOND, 'ream')
        );

        $groups = [];
        if ($sem1Items !== []) {
            $groups[] = [
                'title' => 'Semester One',
                'subtitle' => $isNewStudent
                    ? 'Pay tuition, NHIF, and NACTVET QA. Submit certificates if you are a new student.'
                    : 'Pay tuition, NHIF, and NACTVET QA before Semester One classes.',
                'items' => $sem1Items,
            ];
        }

        if ($sem2Items !== []) {
            $groups[] = [
                'title' => 'Semester Two',
                'subtitle' => 'Finish remaining tuition for the year. Bring gloves and ream again for Semester Two.',
                'items' => $sem2Items,
            ];
        }

        $accommodationExpected = (float) $fs->accommodation;
        $hasHostel = $student->accommodationAllocations->contains(fn ($a) => $a->status === 'active');
        if ($accommodationExpected > 0 || $hasHostel) {
            $accommodationPaid = $this->accommodationPaidEstimate($student, $accommodationExpected);
            $groups[] = [
                'title' => 'Accommodation',
                'subtitle' => 'Hostel / accommodation charges when allocated.',
                'items' => [
                    $this->feePaymentItem(
                        $this->paymentLine(++$index, 'Accommodation fees', 'accommodation', $student, $accommodationExpected, $accommodationPaid, $expiry)
                    ),
                ],
            ];
        }

        return $groups;
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    private function feePaymentItem(array $line): array
    {
        return array_merge($line, ['kind' => 'fee']);
    }

    /**
     * @param  list<string>  $keywords
     */
    private function requirementItem(string $label, string $detail, bool $done): array
    {
        return [
            'kind' => 'requirement',
            'fee_label' => $label,
            'detail' => $detail,
            'status_done' => $done,
            'status_label' => $done ? 'Submitted' : 'Required',
            'status_class' => $done ? 'success' : 'warning',
        ];
    }

    private function certificatesSubmitted(Student $student): bool
    {
        $keys = $student->submitted_certificates ?? [];
        if (is_array($keys) && $keys !== []) {
            return true;
        }

        return $this->academicRequirementMet($student->academic_requirements, [
            'medical', 'form iv', 'birth cert', 'certificate',
        ]);
    }

    /**
     * Gloves / ream per semester: explicit selections in physical_supplies_ack, else legacy academic_requirements text.
     */
    private function physicalSupplySubmitted(Student $student, int $academicYear, int $semesterNumber, string $item): bool
    {
        $slot = $student->physicalSuppliesForSemester($academicYear, $semesterNumber);
        if ($slot !== null && array_key_exists($item, $slot)) {
            return (bool) ($slot[$item] ?? false);
        }
        $keywords = $item === 'gloves' ? ['gloves'] : ['ream', 'a4'];

        return $this->academicRequirementMet($student->academic_requirements, $keywords);
    }

    /**
     * @param  list<string>  $keywords
     */
    private function academicRequirementMet(?string $text, array $keywords): bool
    {
        $haystack = strtolower($text ?? '');
        if ($haystack === '') {
            return false;
        }

        foreach ($keywords as $keyword) {
            if (! str_contains($haystack, strtolower($keyword))) {
                continue;
            }
            if (preg_match('/\b(yes|submitted|received|brought|done)\b/i', $haystack)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{index: int, fee_label: string, component_key: string, control_number: string, billed: float, paid: float, balance: float, expiry_at: string}
     */
    private function paymentLine(
        int $index,
        string $feeLabel,
        string $componentKey,
        Student $student,
        float $billed,
        float $paid,
        Carbon $expiry
    ): array {
        $paid = min($paid, $billed);
        $balance = max(0.0, $billed - $paid);

        return [
            'index' => $index,
            'fee_label' => $feeLabel,
            'component_key' => $componentKey,
            'control_number' => $this->controlNumberFor($student, $componentKey),
            'billed' => $billed,
            'paid' => $paid,
            'balance' => $balance,
            'expiry_at' => $expiry->format('Y-m-d H:i:s'),
            'expiry_display' => 'Expiry Date: '.$expiry->format('Y-m-d').' | '.$expiry->format('H:i:s'),
        ];
    }

    private function controlNumberFor(Student $student, string $componentKey): string
    {
        $allocationKey = str_starts_with($componentKey, 'tuition') ? 'tuition' : $componentKey;
        if (in_array($allocationKey, ['tuition', 'nhif', 'nactvet_qa'], true)) {
            $ref = trim($student->paymentRefsForComponent($allocationKey, null));
            if ($ref !== '') {
                $first = trim(explode(';', $ref)[0]);

                return preg_replace('/\D/', '', $first) ?: $first;
            }
        }

        $digits = preg_replace('/\D/', '', $student->registrationNumberDisplay() ?: $student->reg_no) ?: (string) $student->id;
        $suffix = match ($componentKey) {
            'tuition_sem1' => '01',
            'tuition_sem2' => '02',
            'accommodation' => '03',
            'nhif' => '04',
            'nactvet_qa' => '05',
            default => '99',
        };

        return str_pad(substr($digits, -9).$suffix, 12, '9', STR_PAD_LEFT);
    }

    private function accommodationPaidEstimate(Student $student, float $expected): float
    {
        if ($expected <= 0) {
            return 0.0;
        }

        $tuitionPaid = $student->sumPaymentAllocation('tuition');
        $nhifPaid = $student->sumPaymentAllocation('nhif');
        $qaPaid = $student->sumPaymentAllocation('nactvet_qa');
        $totalPaid = (float) $student->payments->sum('amount');
        $otherPaid = $tuitionPaid + $nhifPaid + $qaPaid;
        $remainder = max(0.0, $totalPaid - $otherPaid);

        return min($expected, $remainder);
    }

    /**
     * @return array{is_loan_beneficiary: bool, items: list<array{label: string, value: string}>}
     */
    private function loanDetails(Student $student): array
    {
        $isLoan = ($student->tuition_status_override ?? '') === 'loan_beneficiary';
        $items = [];

        if ($isLoan) {
            $items[] = ['label' => 'Tuition status', 'value' => 'Loan beneficiary'];
            $items[] = ['label' => 'Note', 'value' => 'Your tuition is covered under a higher-education loan arrangement. Confirm details with the accounts office.'];
        }

        $instalments = PaymentInstalment::query()
            ->where('student_id', $student->id)
            ->orderBy('due_date')
            ->get();

        foreach ($instalments as $inst) {
            $items[] = [
                'label' => $inst->label ?: 'Instalment due '.$inst->due_date?->format('d/m/Y'),
                'value' => number_format((float) $inst->paid_amount, 2).' / '.number_format((float) $inst->amount, 2).' TZS — '.(PaymentInstalment::STATUSES[$inst->status] ?? $inst->status),
            ];
        }

        return [
            'is_loan_beneficiary' => $isLoan,
            'items' => $items,
        ];
    }

    /**
     * @return list<array{from: string, to: string, reason: string, status: string, status_class: string}>
     */
    private function permissionRequests(Student $student): array
    {
        return LeaveApplication::query()
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function (LeaveApplication $leave) {
                $statusClass = match ($leave->status) {
                    'approved' => 'success',
                    'rejected' => 'danger',
                    default => 'warning',
                };

                return [
                    'from' => $leave->from_date?->format('d M Y') ?? '—',
                    'to' => $leave->to_date?->format('d M Y') ?? '—',
                    'reason' => $leave->reason ?: '—',
                    'status' => LeaveApplication::STATUSES[$leave->status] ?? $leave->status,
                    'status_class' => $statusClass,
                ];
            })
            ->all();
    }
}
