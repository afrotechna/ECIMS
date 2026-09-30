<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Models\ActivityLog;
use App\Models\CashAccount;
use App\Models\Creditor;
use App\Models\Department;
use App\Models\InstitutionTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CreditorController extends Controller
{
    use BulkDestroysRecords;

    public function index(Request $request)
    {
        $category = $request->get('category');
        $status = $request->get('verification_status');

        $creditors = Creditor::with('department')
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($status, fn ($q) => $q->where('verification_status', $status))
            ->orderByDesc('date_incurred')
            ->paginate(15)
            ->withQueryString();

        return view('accountancy.creditors.index', compact('creditors', 'category', 'status'));
    }

    public function create()
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('accountancy.creditors.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated['verification_status'] = 'not_yet_verified';

        Creditor::create($validated);

        return redirect()->route('creditors.index')->with('success', 'Creditor added.');
    }

    public function edit(Creditor $creditor)
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $accounts = CashAccount::where('is_active', true)->orderBy('name')->get();

        return view('accountancy.creditors.edit', compact('creditor', 'departments', 'accounts'));
    }

    public function update(Request $request, Creditor $creditor)
    {
        $validated = $this->validated($request);
        $status = $request->input('verification_status');
        $validated['verification_status'] = is_string($status) && array_key_exists($status, Creditor::VERIFICATION_STATUSES)
            ? $status
            : $creditor->verification_status;

        $creditor->update($validated);

        return redirect()->route('creditors.index')->with('success', 'Creditor updated.');
    }

    public function destroy(Creditor $creditor)
    {
        if ($creditor->transactions()->exists()) {
            return redirect()->route('creditors.index')->with('error', 'Cannot delete — payments are already recorded against this creditor.');
        }
        $creditor->delete();

        return redirect()->route('creditors.index')->with('success', 'Creditor deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        return $this->bulkDestroyRecords(
            $request,
            Creditor::class,
            'creditors.index',
            singularLabel: 'creditor',
            deleter: function (Creditor $creditor) {
                if ($creditor->transactions()->exists()) {
                    return false;
                }
                $creditor->delete();

                return true;
            },
        );
    }

    public function pay(Request $request, Creditor $creditor)
    {
        $validated = $request->validate([
            'cash_account_id' => ['required', 'exists:cash_accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'txn_date' => ['required', 'date'],
        ]);

        $txn = InstitutionTransaction::create([
            'cash_account_id' => $validated['cash_account_id'],
            'direction' => 'out',
            'amount' => $validated['amount'],
            'txn_date' => $validated['txn_date'],
            'category' => $creditor->categoryLabel(),
            'description' => 'Payment to '.$creditor->payee_name,
            'creditor_id' => $creditor->id,
            'department_id' => $creditor->department_id,
            'recorded_by' => auth()->id(),
        ]);

        if ($creditor->balance() <= 0.005) {
            $creditor->update(['verification_status' => 'paid_and_cleared']);
        }

        if (Schema::hasTable('activity_log')) {
            ActivityLog::log('creditor.pay', Creditor::class, $creditor->id, 'Amount: '.number_format((float) $validated['amount']).' TZS');
        }

        return redirect()->route('creditors.index')->with('success', 'Payment of '.number_format((float) $validated['amount']).' recorded against '.$creditor->payee_name.'.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'category' => ['required', Rule::in(array_keys(Creditor::CATEGORIES))],
            'payee_name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:500'],
            'amount_due' => ['required', 'numeric', 'min:0.01'],
            'date_incurred' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);
    }
}
