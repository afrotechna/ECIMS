<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Student;
use App\Support\AcademicSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $student = null;
        if ($request->filled('student_id')) {
            $student = Student::with('programme')->find($request->integer('student_id'));
        }

        if ($student) {
            $currentYear = AcademicSession::defaultStartYear();
            $allPayments = Payment::query()->where('student_id', $student->id)->orderByDesc('paid_at')->get();
            $studentTotal = (float) $allPayments->sum('amount');
            $studentYearTotal = (float) $allPayments->where('academic_year', $currentYear)->sum('amount');
            $studentReceiptCount = $allPayments->count();

            $yearGroups = $allPayments
                ->groupBy('academic_year')
                ->map(fn ($group, $year) => $this->buildSessionBreakdown((int) $year, $group))
                ->sortByDesc('lastActivity')
                ->values();

            return view('payments.index', compact('student', 'studentTotal', 'studentYearTotal', 'studentReceiptCount', 'currentYear', 'yearGroups'));
        }

        $query = Payment::query()
            ->with(['student.programme', 'receiver'])
            ->whereHas('student')
            ->orderByDesc('paid_at');
        $payments = $query->paginate(15)->withQueryString();

        $todayTotal = (float) Payment::query()->whereDate('paid_at', today())->whereHas('student')->sum('amount');
        $monthTotal = (float) Payment::query()
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfDay()])
            ->whereHas('student')
            ->sum('amount');
        $allTimeCount = (int) Payment::query()->whereHas('student')->count();

        return view('payments.index', compact('payments', 'todayTotal', 'monthTotal', 'allTimeCount'));
    }

    public function show(Payment $payment)
    {
        $payment->load(['student.programme', 'receiver']);
        if (! $payment->student) {
            abort(404, 'This payment record is orphaned (student no longer exists).');
        }

        $sessionPayments = Payment::where('student_id', $payment->student_id)
            ->where('academic_year', $payment->academic_year)
            ->orderBy('paid_at')
            ->get();
        $session = $this->buildSessionBreakdown((int) $payment->academic_year, $sessionPayments);

        return view('payments.show', compact('payment', 'session'));
    }

    public function receipt(Payment $payment)
    {
        if (auth()->user()->isStudent()) {
            if (! auth()->user()->student || $payment->student_id !== auth()->user()->student->id) {
                abort(403, 'You can only view your own payment receipts.');
            }
        } elseif (auth()->user()->isGuardian()) {
            if ($payment->student_id !== auth()->user()->linked_student_id) {
                abort(403);
            }
        } elseif (! auth()->user()->canAccessFinance()) {
            abort(403, 'Payment receipts are for finance staff or administrator only.');
        }
        $payment->load('student');
        if (! $payment->student) {
            abort(404, 'This payment record is orphaned (student no longer exists).');
        }

        $sessionPayments = Payment::where('student_id', $payment->student_id)
            ->where('academic_year', $payment->academic_year)
            ->orderBy('paid_at')
            ->get();
        $session = $this->buildSessionBreakdown((int) $payment->academic_year, $sessionPayments);
        $grandTotal = Payment::where('student_id', $payment->student_id)->sum('amount');

        return view('payments.receipt', compact('payment', 'session', 'grandTotal'));
    }

    /**
     * Group a student's payments for one academic year into Semester I / Semester II
     * breakdowns — shared by the payment-history cards and the printed receipt, since
     * both need to present a session's Sem I + Sem II payments as one consolidated view.
     */
    private function buildSessionBreakdown(int $year, \Illuminate\Support\Collection $group): array
    {
        $semOne = $group->where('covers_semester_two_only', false)->sortByDesc('paid_at')->values();
        $semTwo = $group->where('covers_semester_two_only', true)->sortByDesc('paid_at')->values();
        $label = match (true) {
            $semOne->isNotEmpty() && $semTwo->isNotEmpty() => 'Semester I + II',
            $semOne->isNotEmpty() => 'Semester I',
            $semTwo->isNotEmpty() => 'Semester II',
            default => null,
        };

        return [
            'year' => $year,
            'label' => $label,
            'semOne' => $semOne,
            'semTwo' => $semTwo,
            'lastActivity' => $group->max('paid_at'),
            'total' => (float) $group->sum('amount'),
            'breakdown' => [
                'sem1_tuition' => $semOne->sum(fn ($p) => $p->allocatedAmount('tuition')),
                'sem1_nhif' => $semOne->sum(fn ($p) => $p->allocatedAmount('nhif')),
                'sem1_nactvet_qa' => $semOne->sum(fn ($p) => $p->allocatedAmount('nactvet_qa')),
                'sem2_tuition' => $semTwo->sum(fn ($p) => $p->allocatedAmount('tuition')),
            ],
        ];
    }

    public function reverseForm(Payment $payment)
    {
        $payment->load('student.programme');
        if (! $payment->student) {
            abort(404, 'This payment record is orphaned (student no longer exists).');
        }

        return view('payments.reverse', compact('payment'));
    }

    public function reverse(Request $request, Payment $payment)
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $payment->load('student');
        if (! $payment->student) {
            abort(404, 'This payment record is orphaned (student no longer exists).');
        }
        $amount = (int) round($payment->amount);
        $student = $payment->student;
        $debits = (float) $student->ledgerEntries()->where('type', 'debit')->sum('amount');
        $credits = (float) $student->ledgerEntries()->where('type', 'credit')->sum('amount');
        $balanceAfter = (int) round($debits - $credits + $amount);

        LedgerEntry::create([
            'student_id' => $student->id,
            'type' => 'debit',
            'amount' => $amount,
            'description' => 'Payment reversal - '.($request->input('reason') ?: 'ref payment #'.$payment->id),
            'reference_type' => 'payment_reversal',
            'reference_id' => $payment->id,
            'balance_after' => $balanceAfter,
        ]);

        if (Schema::hasTable('activity_log')) {
            ActivityLog::log('payment.reversed', Payment::class, $payment->id, 'Amount: '.number_format($amount).' TZS, reason: '.($request->input('reason') ?: 'none'));
        }

        return redirect()->route('payments.index')->with('success', 'Payment reversed. Ledger updated.');
    }
}
