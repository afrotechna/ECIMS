<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    use BulkDestroysRecords;

    public function index()
    {
        $departments = Department::withCount(['budgetLines', 'creditors'])->orderBy('name')->paginate(15);

        return view('accountancy.departments.index', compact('departments'));
    }

    public function create()
    {
        return view('accountancy.departments.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:30', 'unique:departments,code'],
        ]);
        $validated['is_active'] = true;

        Department::create($validated);

        return redirect()->route('departments.index')->with('success', 'Department added.');
    }

    public function edit(Department $department)
    {
        return view('accountancy.departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:30', 'unique:departments,code,'.$department->id],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        $department->update($validated);

        return redirect()->route('departments.index')->with('success', 'Department updated.');
    }

    public function destroy(Department $department)
    {
        if ($department->budgetLines()->exists() || $department->creditors()->exists()) {
            return redirect()->route('departments.index')->with('error', 'Cannot delete — this department has budget lines or creditors recorded against it.');
        }
        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Department deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        return $this->bulkDestroyRecords(
            $request,
            Department::class,
            'departments.index',
            singularLabel: 'department',
            deleter: function (Department $department) {
                if ($department->budgetLines()->exists() || $department->creditors()->exists()) {
                    return false;
                }
                $department->delete();

                return true;
            },
        );
    }
}
