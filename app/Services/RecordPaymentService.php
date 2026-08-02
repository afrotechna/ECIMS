<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\FeeStructure;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Shared payment recording for the student registration payment step.
 * Tuition is driven by {@see self::TUITION_CATEGORIES}; NHIF and NACTVET remain per-line choices.
 */
class RecordPaymentService
{
    public const TUITION_CATEGORIES = ['new_student', 'continue', 'repeat', 'transfer'];

    /**
     * @param  array<string, mixed>  $validated  Keys: student_id, academic_year, tuition_category, slot_sem1_nhif, slot_sem1_nactvet_qa, payment_method, reference_tuition, reference_nactvet_qa, paid_at, notes, semester_two_only
     */
    public function record(array $validated, ?int $receivedByUserId = null): Payment
    {
        $student = Student::findOrFail($validated['student_id']);
        $academicYear = (int) $validated['academic_year'];
        $feeStructures = FeeStructure::with('feeStructureSemesters')->where('is_active', true)->get();
        $fs = FeeStructure::resolveForStudent($student, $academicYear, $feeStructures);
        if (! $fs) {
            throw ValidationException::withMessages([
                'academic_year' => 'No fee schedule for this session. Add it under Finance → Fees.',
            ]);
        }

        $slots = $fs->scheduledFeeSlots();
        $category = $validated['tuition_category'] ?? null;
        if (! is_string($category) || ! in_array($category, self::TUITION_CATEGORIES, true)) {
            throw ValidationException::withMessages([
                'tuition_category' => 'Select how tuition applies: new student, continuing, repeat, or transferred.',
            ]);
        }

        $semesterTwoOnly = (bool) ($validated['semester_two_only'] ?? false);
        [$sem1T, $sem2C, $sem2R] = $this->tuitionAmountsForCategory($category, $slots, $semesterTwoOnly);

        $this->assertPaymentSlot($sem1T, $slots['sem1_tuition'], 'tuition_category');
        $this->assertPaymentSlot($sem2C, $slots['sem2_continuous'], 'tuition_category');
        $this->assertPaymentSlot($sem2R, $slots['sem2_repeat'], 'tuition_category');

        $sem1H = (int) ($validated['slot_sem1_nhif'] ?? 0);
        $sem1Q = (int) ($validated['slot_sem1_nactvet_qa'] ?? 0);

        $this->assertPaymentSlot($sem1H, $slots['sem1_nhif'], 'slot_sem1_nhif');
        $this->assertPaymentSlot($sem1Q, $slots['sem1_nactvet_qa'], 'slot_sem1_nactvet_qa');

        if ($sem2C > 0 && $sem2R > 0) {
            throw ValidationException::withMessages([
                'tuition_category' => 'Invalid tuition configuration for this fee structure.',
            ]);
        }

        $tuitionPart = $sem1T + $sem2C + $sem2R;
        $amount = (float) ($tuitionPart + $sem1H + $sem1Q);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'tuition_category' => 'Select a tuition category and/or at least one other fee line so the total is greater than zero.',
            ]);
        }

        $refs = [];
        if ($tuitionPart > 0) {
            $ref = trim((string) ($validated['reference_tuition'] ?? $validated['reference'] ?? ''));
            if ($ref === '') {
                throw ValidationException::withMessages([
                    'reference_tuition' => 'Enter the tuition fee control number.',
                ]);
            }
            $refs['tuition'] = $ref;
        }
        if ($sem1Q > 0) {
            $ref = trim((string) ($validated['reference_nactvet_qa'] ?? ''));
            if ($ref === '') {
                throw ValidationException::withMessages([
                    'reference_nactvet_qa' => 'Enter the NACTVET QA control number.',
                ]);
            }
            $refs['nactvet_qa'] = $ref;
        }

        $allocation = array_filter([
            'tuition' => $tuitionPart > 0 ? (float) $tuitionPart : null,
            'nhif' => $sem1H > 0 ? (float) $sem1H : null,
            'nactvet_qa' => $sem1Q > 0 ? (float) $sem1Q : null,
        ], fn ($v) => $v !== null && (float) $v > 0);
        if ($allocation === []) {
            $allocation = ['tuition' => (float) $amount];
        }
        if ($refs !== []) {
            $allocation['refs'] = $refs;
        }

        $combinedReference = implode('; ', array_values($refs));

        $payment = Payment::create([
            'student_id' => $validated['student_id'],
            'academic_year' => $academicYear,
            'amount' => $amount,
            'payment_method' => $validated['payment_method'],
            'reference' => $combinedReference !== '' ? $combinedReference : ($validated['reference'] ?? ''),
            'paid_at' => $validated['paid_at'],
            'received_by' => $receivedByUserId,
            'allocation' => $allocation,
            'notes' => $validated['notes'] ?? null,
            'tuition_category' => $category,
            'covers_semester_two_only' => $semesterTwoOnly,
        ]);

        $methodLabel = Payment::methods()[$validated['payment_method']] ?? $validated['payment_method'];
        $parts = [];
        foreach ($allocation as $k => $v) {
            $parts[] = $k.': '.number_format((float) $v);
        }
        $ledgerDescription = 'Payment — '.$methodLabel.' ('.implode('; ', $parts).')';

        app(StudentLedgerService::class)->postFeeDebitForPayment(
            $student,
            $amount,
            'Fees charged — '.$methodLabel.' ('.implode('; ', $parts).')'
        );

        $debits = (float) $student->ledgerEntries()->where('type', 'debit')->sum('amount');
        $credits = (float) $student->ledgerEntries()->where('type', 'credit')->sum('amount');
        $balanceAfter = (int) round($debits - $credits - $amount);
        LedgerEntry::create([
            'student_id' => $student->id,
            'type' => 'credit',
            'amount' => (int) round($amount),
            'description' => 'Payment received — '.$methodLabel.' ('.implode('; ', $parts).')',
            'reference_type' => 'payment',
            'reference_id' => $payment->id,
            'balance_after' => (int) round($balanceAfter),
        ]);

        if (Schema::hasTable('activity_log')) {
            ActivityLog::log('payment.created', Payment::class, $payment->id, 'Amount: '.number_format($amount).' TZS, student_id: '.$payment->student_id);
        }

        $payment->loadMissing('student');
        User::query()
            ->whereIn('role', ['accountant', 'vice_principal_afp', 'administrator'])
            ->get()
            ->each
            ->notify(new PaymentReceivedNotification($payment));

        $studentUpdates = [];
        if ($tuitionPart > 0 && isset($refs['tuition'])) {
            $studentUpdates['tuition_payment_ref'] = $refs['tuition'];
        }
        if ($sem1H > 0 && isset($refs['nhif'])) {
            $studentUpdates['nhif_payment_ref'] = $refs['nhif'];
        }
        if ($sem1Q > 0 && isset($refs['nactvet_qa'])) {
            $studentUpdates['nactvet_qa_payment_ref'] = $refs['nactvet_qa'];
        }
        if ($studentUpdates !== []) {
            $student->update($studentUpdates);
        }

        return $payment;
    }

    /**
     * Fees are billed per semester, one installment at a time — Semester I tuition when the
     * student registers for Semester I, then the remaining Semester II tuition (continuous or
     * repeat rate, depending on category) when they register for Semester II. A category never
     * charges both installments in the same transaction.
     *
     * @param  array{sem1_tuition: int, sem2_continuous: int, sem2_repeat: int, ...}  $slots
     * @param  bool  $semesterTwoOnly  Semester II registration — charge the remaining Semester II
     *                                 portion only; Semester I was already billed separately.
     * @return array{0: int, 1: int, 2: int} Sem I tuition, Sem II continuous, Sem II repeat (each 0 or full scheduled)
     */
    public function tuitionAmountsForCategory(string $category, array $slots, bool $semesterTwoOnly = false): array
    {
        $s1 = (int) ($slots['sem1_tuition'] ?? 0);
        $s2c = (int) ($slots['sem2_continuous'] ?? 0);
        $s2r = (int) ($slots['sem2_repeat'] ?? 0);

        if ($semesterTwoOnly) {
            return match ($category) {
                'repeat', 'transfer' => [0, 0, $s2r],
                default => [0, $s2c, 0],
            };
        }

        return [$s1, 0, 0];
    }

    private function assertPaymentSlot(int $submitted, int $scheduled, string $field): void
    {
        $allowed = $scheduled <= 0 ? [0] : [0, $scheduled];
        if (! in_array($submitted, $allowed, true)) {
            throw ValidationException::withMessages([
                $field => 'Choose either not paying this line (0) or the full scheduled amount from the fee structure.',
            ]);
        }
    }
}
