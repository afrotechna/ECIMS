<?php

namespace App\Http\Controllers;

use App\Models\FeeStructure;
use App\Models\Semester;
use App\Models\SemesterRegistration;
use App\Models\Student;
use App\Services\OfficialRegistryNumberService;
use App\Services\RecordPaymentService;
use App\Support\AcademicSession;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrationWizardController extends Controller
{
    public function startForm(): View|RedirectResponse
    {
        $students = Student::with('programme')->where('status', 'active')->orderBy('reg_no')->get();

        $academicYearOptions = Semester::academicYearOptionsForForms();
        $selectedAcademicYear = (int) request('academic_year', \App\Support\AcademicSession::defaultStartYear());
        $semesters = Semester::forAcademicYear($selectedAcademicYear, true);

        if ($semesters->isEmpty() && ! request()->has('academic_year')) {
            $fallbackYear = Semester::query()
                ->where('is_active', true)
                ->orderByDesc('academic_year')
                ->value('academic_year');
            if ($fallbackYear !== null) {
                $selectedAcademicYear = (int) $fallbackYear;
                $semesters = Semester::forAcademicYear($selectedAcademicYear, true);
            }
        }

        $inactiveSameYear = Semester::query()
            ->where('academic_year', $selectedAcademicYear)
            ->where('is_active', false)
            ->count();
        $totalSameYear = Semester::where('academic_year', $selectedAcademicYear)->count();

        $inProgressList = SemesterRegistration::query()
            ->whereNotNull('wizard_step')
            ->with(['semester', 'student'])
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        return view('registration-wizard.start', compact(
            'students',
            'semesters',
            'academicYearOptions',
            'selectedAcademicYear',
            'inactiveSameYear',
            'totalSameYear',
            'inProgressList',
        ));
    }

    public function start(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
        ]);

        $studentId = (int) $validated['student_id'];
        $semesterId = (int) $validated['semester_id'];

        $existing = SemesterRegistration::query()
            ->where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->first();

        if ($existing) {
            return $this->redirectForExistingRegistration($existing);
        }

        try {
            $reg = SemesterRegistration::create([
                'student_id' => $studentId,
                'semester_id' => $semesterId,
                'status' => 'pending',
                'registered_at' => now(),
                'wizard_step' => 1,
                'wizard_payload' => [],
            ]);
        } catch (UniqueConstraintViolationException) {
            $reg = SemesterRegistration::query()
                ->where('student_id', $studentId)
                ->where('semester_id', $semesterId)
                ->firstOrFail();

            return $this->redirectForExistingRegistration($reg);
        }

        return redirect()->route('registration-wizard.step', [$reg, 1]);
    }

    private function redirectForExistingRegistration(SemesterRegistration $reg): RedirectResponse
    {
        if ($reg->status === 'approved' && $reg->wizard_step === null) {
            return redirect()->back()->with(
                'error',
                'This student is already registered for this semester. Open their record under Student registration to view it.'
            );
        }

        if ($reg->wizard_step !== null && $reg->status !== 'rejected') {
            $step = max(1, (int) $reg->wizard_step);

            return redirect()->route('registration-wizard.step', [$reg, $step])
                ->with('info', 'Continuing registration where you left off (step '.$step.').');
        }

        $reg->update([
            'wizard_step' => 1,
            'wizard_payload' => [],
            'status' => 'pending',
            'registered_at' => $reg->registered_at ?? now(),
        ]);

        return redirect()->route('registration-wizard.step', [$reg, 1])
            ->with('info', 'An existing registration was found for this semester — opening step 1 of the wizard.');
    }

    public function step(SemesterRegistration $semester_registration, int $step): View|RedirectResponse
    {
        $this->authorizeRegistrationAccess($semester_registration);

        $semester_registration->load(['student.programme', 'semester']);
        $semester = $semester_registration->semester;
        $total = $this->totalStepsForSemester($semester);
        $wizardStep = (int) $semester_registration->wizard_step;

        if ($semester_registration->wizard_step === null) {
            return $this->redirectAfterCompletedRegistration();
        }

        if ($step < 1 || $step > $total) {
            return redirect()->route('registration-wizard.step', [$semester_registration, max(1, min($wizardStep, $total))]);
        }

        if ($step > $wizardStep) {
            return redirect()
                ->route('registration-wizard.step', [$semester_registration, $wizardStep])
                ->with('warning', 'Continue from step '.$wizardStep.' — complete each step in order.');
        }

        $student = $semester_registration->student->fresh();
        $percent = (int) round(100 * $step / $total);

        $feeSlotsByKey = [];
        $paymentAcademicYear = (int) $semester->academic_year;
        $feeStructureResolved = false;
        $isPaymentStep = ($this->isFirstSemester($semester) && $step === 3)
            || (! $this->isFirstSemester($semester) && $step === 1);

        if ($isPaymentStep) {
            $feeStructures = FeeStructure::with('feeStructureSemesters')->where('is_active', true)->get();
            $fs = FeeStructure::resolveForStudent($student, $paymentAcademicYear, $feeStructures);
            if ($fs) {
                $feeStructureResolved = true;
                $slots = $fs->scheduledFeeSlots();
                $pk = ($student->programme_id ?? 'all').'_'.$paymentAcademicYear;
                $feeSlotsByKey[$pk] = $slots;
                $feeSlotsByKey['all_'.$paymentAcademicYear] = $slots;
            }
        }

        return view('registration-wizard.step', [
            'semester_registration' => $semester_registration,
            'student' => $student,
            'semester' => $semester,
            'step' => $step,
            'total' => $total,
            'percent' => $percent,
            'wizardStep' => $wizardStep,
            'feeSlotsByKey' => $feeSlotsByKey,
            'paymentAcademicYear' => $paymentAcademicYear,
            'feeStructureResolved' => $feeStructureResolved,
            'paymentMethods' => \App\Models\Payment::methods(),
            'isPaymentStep' => $isPaymentStep,
            'chargesNhifQa' => $this->chargesNhifQa($semester, $student),
        ]);
    }

    public function saveStep(
        Request $request,
        SemesterRegistration $semester_registration,
        int $step,
        OfficialRegistryNumberService $registry,
        RecordPaymentService $paymentService,
    ): RedirectResponse {
        $this->authorizeRegistrationAccess($semester_registration);

        $semester_registration->load(['student', 'semester']);
        $semester = $semester_registration->semester;
        $total = $this->totalStepsForSemester($semester);
        $student = $semester_registration->student;
        $isFirstSem = $this->isFirstSemester($semester);
        $ws = (int) $semester_registration->wizard_step;

        if ($semester_registration->wizard_step === null) {
            return $this->redirectAfterCompletedRegistration();
        }

        if ($step > $ws) {
            return redirect()
                ->route('registration-wizard.step', [$semester_registration, $ws])
                ->with('error', 'You cannot skip ahead. Finish step '.$ws.' first.');
        }

        if ($step < 1 || $step > $total) {
            return redirect()->route('registration-wizard.step', [$semester_registration, $ws]);
        }

        $payload = $semester_registration->wizard_payload ?? [];

        if ($isFirstSem) {
            if ($step === 1) {
                $data = $request->validate([
                    'first_name' => ['required', 'string', 'max:100'],
                    'middle_name' => ['nullable', 'string', 'max:100'],
                    'last_name' => ['required', 'string', 'max:100'],
                    'gender' => ['nullable', 'in:M,F'],
                    'date_of_birth' => ['nullable', 'date'],
                    'email' => ['nullable', 'email', 'max:255'],
                    'phone' => ['nullable', 'string', 'max:20'],
                    'nta_level' => ['nullable', 'integer', 'in:4,5,6'],
                    'class_group' => ['nullable', 'string', 'max:80'],
                ]);
                $student->update($data);
            } elseif ($step === 2) {
                $data = $request->validate([
                    'guardian_name' => ['nullable', 'string', 'max:150'],
                    'guardian_phone' => ['nullable', 'string', 'max:30'],
                    'guardian_relationship' => ['nullable', 'string', 'max:50'],
                ]);
                $student->update($data);
            } elseif ($step === 3) {
                $yearOpts = AcademicSession::yearOptions(2020, 2040);
                $paymentData = $request->validate([
                    'academic_year' => ['required', 'integer', Rule::in(array_keys($yearOpts))],
                    'tuition_category' => ['required', 'string', Rule::in(RecordPaymentService::TUITION_CATEGORIES)],
                    'slot_sem1_nhif' => ['required', 'integer', 'min:0'],
                    'slot_sem1_nactvet_qa' => ['required', 'integer', 'min:0'],
                    'payment_method' => ['required', 'string', 'in:cash,bank,mobile,cheque'],
                    'reference_tuition' => ['nullable', 'string', 'max:100'],
                    'reference_nactvet_qa' => ['nullable', 'string', 'max:100'],
                    'paid_at' => ['required', 'date'],
                    'notes' => ['nullable', 'string', 'max:500'],
                ]);
                if ((int) $paymentData['academic_year'] !== (int) $semester->academic_year) {
                    return redirect()->back()->withInput()->withErrors([
                        'academic_year' => 'Payment session must match the semester you are registering for ('.$semester->academic_year.'/'.($semester->academic_year + 1).').',
                    ]);
                }
                if (! $this->chargesNhifQa($semester, $student)) {
                    $paymentData['slot_sem1_nhif'] = 0;
                    $paymentData['slot_sem1_nactvet_qa'] = 0;
                }
                $paymentData['student_id'] = $student->id;
                $paymentService->record($paymentData, auth()->id());
                $payload['fees_confirmed_at'] = now()->toIso8601String();
            } elseif ($step === 4) {
                $data = $request->validate([
                    'joining_instructions_submitted' => ['nullable', 'string', 'max:10'],
                    'submitted_certificates' => ['nullable', 'array'],
                    'submitted_certificates.*' => ['string', Rule::in(array_keys(Student::CERTIFICATE_OPTIONS))],
                    'academic_requirements' => ['nullable', 'string', 'max:255'],
                    'supply_gloves_submitted' => ['required', 'in:0,1'],
                    'supply_ream_submitted' => ['required', 'in:0,1'],
                    'class_property_received' => ['nullable', 'string', 'max:10'],
                    'chair_number' => ['nullable', 'string', 'max:30'],
                    'table_number' => ['nullable', 'string', 'max:30'],
                ]);
                $ack = $student->physical_supplies_ack ?? [];
                $key = Student::physicalSuppliesStorageKey((int) $semester->academic_year, (int) $semester->number);
                $ack[$key] = [
                    'gloves' => (bool) (int) $data['supply_gloves_submitted'],
                    'ream' => (bool) (int) $data['supply_ream_submitted'],
                ];
                unset($data['supply_gloves_submitted'], $data['supply_ream_submitted']);
                $data['physical_supplies_ack'] = $ack;
                $data['submitted_certificates'] = array_values($request->input('submitted_certificates', []));
                $student->update($data);
            }
        } else {
            if ($step === 1) {
                $yearOpts = AcademicSession::yearOptions(2020, 2040);
                $paymentData = $request->validate([
                    'academic_year' => ['required', 'integer', Rule::in(array_keys($yearOpts))],
                    'tuition_category' => ['required', 'string', Rule::in(RecordPaymentService::TUITION_CATEGORIES)],
                    'slot_sem1_nhif' => ['required', 'integer', 'min:0'],
                    'slot_sem1_nactvet_qa' => ['required', 'integer', 'min:0'],
                    'payment_method' => ['required', 'string', 'in:cash,bank,mobile,cheque'],
                    'reference_tuition' => ['nullable', 'string', 'max:100'],
                    'reference_nactvet_qa' => ['nullable', 'string', 'max:100'],
                    'paid_at' => ['required', 'date'],
                    'notes' => ['nullable', 'string', 'max:500'],
                ]);
                if ((int) $paymentData['academic_year'] !== (int) $semester->academic_year) {
                    return redirect()->back()->withInput()->withErrors([
                        'academic_year' => 'Payment session must match the semester you are registering for ('.$semester->academic_year.'/'.($semester->academic_year + 1).').',
                    ]);
                }
                if (! $this->chargesNhifQa($semester, $student)) {
                    $paymentData['slot_sem1_nhif'] = 0;
                    $paymentData['slot_sem1_nactvet_qa'] = 0;
                }
                $paymentData['student_id'] = $student->id;
                $paymentData['semester_two_only'] = true;
                $paymentService->record($paymentData, auth()->id());
                $payload['fees_confirmed_at'] = now()->toIso8601String();
            } elseif ($step === 2) {
                $data = $request->validate([
                    'supply_gloves_submitted' => ['required', 'in:0,1'],
                    'supply_ream_submitted' => ['required', 'in:0,1'],
                ]);
                $ack = $student->physical_supplies_ack ?? [];
                $key = Student::physicalSuppliesStorageKey((int) $semester->academic_year, (int) $semester->number);
                $ack[$key] = [
                    'gloves' => (bool) (int) $data['supply_gloves_submitted'],
                    'ream' => (bool) (int) $data['supply_ream_submitted'],
                ];
                $student->update(['physical_supplies_ack' => $ack]);
            }
        }

        $isFinalStep = ($isFirstSem && $step === 4) || (! $isFirstSem && $step === 2);

        if ($isFinalStep && $step === $ws) {
            $semester_registration->update([
                'wizard_step' => null,
                'wizard_payload' => null,
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'notes' => $semester_registration->notes,
            ]);

            if ($isFirstSem) {
                $student->update([
                    'reporting_status' => 'reported',
                    'reporting_date' => now()->toDateString(),
                ]);
                $registry->assignIfMissing($student->fresh());

                return $this->redirectRegistrationComplete($student, true);
            }

            return $this->redirectRegistrationComplete($student, false);
        }

        $newWizardStep = $step === $ws ? min($ws + 1, $total) : $ws;

        $semester_registration->update([
            'wizard_step' => $newWizardStep,
            'wizard_payload' => $payload,
        ]);

        return redirect()->route('registration-wizard.step', [$semester_registration, $step + 1]);
    }

    private function authorizeRegistrationAccess(SemesterRegistration $semester_registration): void
    {
        if (auth()->user()->isStudent()) {
            abort(403, 'Semester registration is completed by college staff after payment. View status under My registrations.');
        }
    }

    private function redirectAfterCompletedRegistration(): RedirectResponse
    {
        if (auth()->user()->isStudent()) {
            return redirect()->route('my.registrations')->with('info', 'This registration is already completed.');
        }

        return redirect()->route('semester-registrations.index')->with('info', 'This registration has already been completed.');
    }

    private function redirectRegistrationComplete(Student $student, bool $firstSemester): RedirectResponse
    {
        if (auth()->user()->isStudent()) {
            $msg = $firstSemester
                ? 'Registration completed. Official registry no.: '.($student->fresh()->official_registry_no ?? $student->reg_no)
                : 'Semester II registration completed.';

            return redirect()->route('my.registrations')->with('success', $msg);
        }

        if ($firstSemester) {
            return redirect()
                ->route('reports.admission-control-sheet.student', ['student' => $student->id])
                ->with('success', 'Registration completed. Official registry no.: '.($student->fresh()->official_registry_no ?? $student->reg_no).'. Print the control sheet below.');
        }

        return redirect()
            ->route('reports.admission-control-sheet.student', ['student' => $student->id])
            ->with('success', 'Semester II registration completed. No new official registry number (one per programme).');
    }

    private function isFirstSemester(Semester $semester): bool
    {
        return (int) $semester->number === Semester::PERIOD_FIRST;
    }

    private function totalStepsForSemester(Semester $semester): int
    {
        return $this->isFirstSemester($semester) ? 4 : 2;
    }

    /**
     * NHIF and NACTVET QA are one-time-per-academic-year charges collected in Semester I.
     * Semester II only charges them again for a transfer or repeat student.
     */
    private function chargesNhifQa(Semester $semester, Student $student): bool
    {
        return $this->isFirstSemester($semester)
            || $student->student_type === 'transferred'
            || $student->academic_standing === 'repeat_year';
    }
}
