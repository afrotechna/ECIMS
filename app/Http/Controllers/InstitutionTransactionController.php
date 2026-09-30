<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BudgetLine;
use App\Models\CashAccount;
use App\Models\Creditor;
use App\Models\Department;
use App\Models\InstitutionTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class InstitutionTransactionController extends Controller
{
    public function index(Request $request)
    {
        $cashAccountId = $request->integer('cash_account_id') ?: null;

        $transactions = InstitutionTransaction::with(['cashAccount', 'budgetLine', 'creditor', 'department', 'recordedBy'])
            ->when($cashAccountId, fn ($q) => $q->where('cash_account_id', $cashAccountId))
            ->orderByDesc('txn_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $accounts = CashAccount::orderBy('name')->get();

        return view('accountancy.institution-transactions.index', compact('transactions', 'accounts', 'cashAccountId'));
    }

    public function create()
    {
        $accounts = CashAccount::where('is_active', true)->orderBy('name')->get();
        $budgetLines = BudgetLine::with('department')->orderByDesc('financial_year')->get();
        $creditors = Creditor::orderBy('payee_name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('accountancy.institution-transactions.create', compact('accounts', 'budgetLines', 'creditors', 'departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cash_account_id' => ['required', 'exists:cash_accounts,id'],
            'direction' => ['required', Rule::in(['in', 'out'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'txn_date' => ['required', 'date'],
            'category' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'budget_line_id' => ['nullable', 'exists:budget_lines,id'],
            'creditor_id' => ['nullable', 'exists:creditors,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);
        $validated['recorded_by'] = auth()->id();

        $txn = InstitutionTransaction::create($validated);

        if (Schema::hasTable('activity_log')) {
            ActivityLog::log('institution-transaction.create', InstitutionTransaction::class, $txn->id, ucfirst($validated['direction']).': '.number_format((float) $validated['amount']).' TZS');
        }

        return redirect()->route('institution-transactions.index')->with('success', 'Transaction recorded.');
    }

    public function void(Request $request, InstitutionTransaction $institutionTransaction)
    {
        if ($institutionTransaction->voided) {
            return redirect()->route('institution-transactions.index')->with('info', 'Already voided.');
        }
        $validated = $request->validate([
            'void_reason' => ['nullable', 'string', 'max:200'],
        ]);

        $institutionTransaction->update([
            'voided' => true,
            'void_reason' => $validated['void_reason'] ?? null,
        ]);

        if (Schema::hasTable('activity_log')) {
            ActivityLog::log('institution-transaction.void', InstitutionTransaction::class, $institutionTransaction->id, $validated['void_reason'] ?? '');
        }

        return redirect()->route('institution-transactions.index')->with('success', 'Transaction voided.');
    }
}
