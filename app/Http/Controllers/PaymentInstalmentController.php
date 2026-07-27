<?php

namespace App\Http\Controllers;

use App\Models\PaymentInstalment;
use App\Models\Student;
use Illuminate\Http\Request;

class PaymentInstalmentController extends Controller
{
    public function index(Request $request)
    {
        $studentId = $request->get('student_id');
        $query = PaymentInstalment::with('student.programme');
        if ($studentId) {
            $query->where('student_id', $studentId);
        }
        $instalments = $query
            ->orderBy('due_date')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $this->refreshOverdueStatuses($instalments->getCollection());

        $students = Student::where('status', 'active')->orderBy('reg_no')->get();

        return view('payment-instalments.index', compact('instalments', 'students', 'studentId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'due_date' => ['required', 'date'],
            'label' => ['nullable', 'string', 'max:100'],
        ]);

        PaymentInstalment::create([
            ...$validated,
            'paid_amount' => 0,
            'status' => 'pending',
        ]);

        return redirect()
            ->route('payment-instalments.index', ['student_id' => $validated['student_id']])
            ->with('success', 'Instalment added.');
    }

    public function update(Request $request, PaymentInstalment $payment_instalment)
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'due_date' => ['required', 'date'],
            'paid_amount' => ['required', 'integer', 'min:0'],
            'label' => ['nullable', 'string', 'max:100'],
        ]);

        $paid = (int) $validated['paid_amount'];
        $amount = (int) $validated['amount'];
        $status = $this->resolveStatus($amount, $paid, $validated['due_date']);

        $payment_instalment->update([
            'amount' => $amount,
            'due_date' => $validated['due_date'],
            'paid_amount' => min($paid, $amount),
            'label' => $validated['label'] ?? null,
            'status' => $status,
        ]);

        return redirect()
            ->route('payment-instalments.index', ['student_id' => $payment_instalment->student_id])
            ->with('success', 'Instalment updated.');
    }

    public function destroy(PaymentInstalment $payment_instalment)
    {
        $studentId = $payment_instalment->student_id;
        $payment_instalment->delete();

        return redirect()
            ->route('payment-instalments.index', ['student_id' => $studentId])
            ->with('success', 'Instalment removed.');
    }

    private function resolveStatus(int $amount, int $paid, string $dueDate): string
    {
        if ($paid >= $amount) {
            return 'paid';
        }
        if ($paid > 0) {
            return 'partial';
        }
        if (strtotime($dueDate) < strtotime('today')) {
            return 'overdue';
        }

        return 'pending';
    }

    private function refreshOverdueStatuses($instalments): void
    {
        foreach ($instalments as $inst) {
            if (in_array($inst->status, ['paid'], true)) {
                continue;
            }
            $newStatus = $this->resolveStatus((int) $inst->amount, (int) $inst->paid_amount, $inst->due_date->format('Y-m-d'));
            if ($newStatus !== $inst->status) {
                $inst->update(['status' => $newStatus]);
            }
        }
    }
}
