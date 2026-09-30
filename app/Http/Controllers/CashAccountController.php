<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Models\CashAccount;
use Illuminate\Http\Request;

class CashAccountController extends Controller
{
    use BulkDestroysRecords;

    public function index()
    {
        $accounts = CashAccount::orderByDesc('is_active')->orderBy('name')->get();
        $grandTotal = CashAccount::grandTotal();

        return view('accountancy.cash-accounts.index', compact('accounts', 'grandTotal'));
    }

    public function create()
    {
        return view('accountancy.cash-accounts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'code' => ['nullable', 'string', 'max:20', 'unique:cash_accounts,code'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
        ]);
        $validated['is_active'] = true;

        CashAccount::create($validated);

        return redirect()->route('cash-accounts.index')->with('success', 'Cash account added.');
    }

    public function edit(CashAccount $cashAccount)
    {
        return view('accountancy.cash-accounts.edit', ['account' => $cashAccount]);
    }

    public function update(Request $request, CashAccount $cashAccount)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'code' => ['nullable', 'string', 'max:20', 'unique:cash_accounts,code,'.$cashAccount->id],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $cashAccount->update($validated);

        return redirect()->route('cash-accounts.index')->with('success', 'Cash account updated.');
    }

    public function destroy(CashAccount $cashAccount)
    {
        if ($cashAccount->transactions()->exists()) {
            return redirect()->route('cash-accounts.index')->with('error', 'Cannot delete — this account has transactions recorded against it.');
        }
        $cashAccount->delete();

        return redirect()->route('cash-accounts.index')->with('success', 'Cash account deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        return $this->bulkDestroyRecords(
            $request,
            CashAccount::class,
            'cash-accounts.index',
            singularLabel: 'cash account',
            deleter: function (CashAccount $account) {
                if ($account->transactions()->exists()) {
                    return false;
                }
                $account->delete();

                return true;
            },
        );
    }
}
