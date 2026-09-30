<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Models\BudgetLine;
use App\Models\Department;
use Illuminate\Http\Request;

class BudgetLineController extends Controller
{
    use BulkDestroysRecords;

    public function index(Request $request)
    {
        $financialYear = $request->get('financial_year');
        $departmentId = $request->integer('department_id') ?: null;

        $lines = BudgetLine::with('department')
            ->when($financialYear, fn ($q) => $q->where('financial_year', $financialYear))
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->orderByDesc('financial_year')
            ->orderBy('department_id')
            ->paginate(15)
            ->withQueryString();

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('accountancy.budget-lines.index', compact('lines', 'departments', 'financialYear', 'departmentId'));
    }

    public function create()
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('accountancy.budget-lines.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'financial_year' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'item_description' => ['required', 'string', 'max:200'],
            'annual_budget' => ['required', 'numeric', 'min:0'],
        ]);

        BudgetLine::create($validated);

        return redirect()->route('budget-lines.index')->with('success', 'Budget line added.');
    }

    public function edit(BudgetLine $budgetLine)
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('accountancy.budget-lines.edit', compact('budgetLine', 'departments'));
    }

    public function update(Request $request, BudgetLine $budgetLine)
    {
        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'financial_year' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'item_description' => ['required', 'string', 'max:200'],
            'annual_budget' => ['required', 'numeric', 'min:0'],
        ]);

        $budgetLine->update($validated);

        return redirect()->route('budget-lines.index')->with('success', 'Budget line updated.');
    }

    public function destroy(BudgetLine $budgetLine)
    {
        if ($budgetLine->transactions()->exists()) {
            return redirect()->route('budget-lines.index')->with('error', 'Cannot delete — this budget line has transactions recorded against it.');
        }
        $budgetLine->delete();

        return redirect()->route('budget-lines.index')->with('success', 'Budget line deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        return $this->bulkDestroyRecords(
            $request,
            BudgetLine::class,
            'budget-lines.index',
            singularLabel: 'budget line',
            deleter: function (BudgetLine $line) {
                if ($line->transactions()->exists()) {
                    return false;
                }
                $line->delete();

                return true;
            },
        );
    }
}
