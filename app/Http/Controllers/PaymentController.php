<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LedgerEntry;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::query()
            ->with(['student.programme', 'receiver'])
            ->whereHas('student')
            ->orderByDesc('paid_at');

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

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

        return view('payments.show', compact('payment'));
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

        return view('payments.receipt', compact('payment'));
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
