<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Models\FeeStructure;
use App\Models\FeeStructureSemester;
use App\Models\Programme;
use App\Support\AcademicSession;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeeStructureController extends Controller
{
    use BulkDestroysRecords;

    public function index()
    {
        $structures = FeeStructure::with(['programme', 'feeStructureSemesters'])
            ->orderByDesc('academic_year')
            ->orderBy('programme_id')
            ->paginate(10);

        return view('fee-structures.index', compact('structures'));
    }

    public function create()
    {
        $programmes = Programme::where('is_active', true)->orderBy('code')->get();
        $sessionYears = AcademicSession::yearOptions(2020, 2040);

        return view('fee-structures.create', compact('programmes', 'sessionYears'));
    }

    public function store(Request $request)
    {
        $yearOpts = AcademicSession::yearOptions(2020, 2040);
        $validated = $request->validate([
            'academic_year' => ['required', 'integer', Rule::in(array_keys($yearOpts))],
            'programme_id' => ['nullable', 'exists:programmes,id'],
            'sem1_tuition' => ['required', 'numeric', 'min:0'],
            'sem1_nhif' => ['nullable', 'numeric', 'min:0'],
            'sem1_nactvet_qa' => ['nullable', 'numeric', 'min:0'],
            'sem2_tuition_continuous' => ['required', 'numeric', 'min:0'],
            'sem2_tuition_repeat_transfer' => ['required', 'numeric', 'min:0'],
            'accommodation' => ['nullable', 'numeric', 'min:0'],
            'other_charges' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);
        $validated['programme_id'] = $validated['programme_id'] ?: null;
        $validated['sem1_nhif'] = $validated['sem1_nhif'] ?? 0;
        $validated['sem1_nactvet_qa'] = $validated['sem1_nactvet_qa'] ?? 0;
        $validated['accommodation'] = $validated['accommodation'] ?? 0;
        $validated['other_charges'] = $validated['other_charges'] ?? 0;

        $aggregate = [
            'academic_year' => $validated['academic_year'],
            'programme_id' => $validated['programme_id'],
            'tuition' => $validated['sem1_tuition'] + $validated['sem2_tuition_continuous'],
            'nhif' => $validated['sem1_nhif'],
            'nactvet_qa' => $validated['sem1_nactvet_qa'],
            'accommodation' => $validated['accommodation'],
            'other_charges' => $validated['other_charges'],
            'is_active' => $request->boolean('is_active', true),
        ];

        $structure = FeeStructure::create($aggregate);
        $this->syncFeeStructureSemesters($structure, $validated);

        return redirect()->route('fee-structures.index')->with('success', 'Schedule saved.');
    }

    public function edit(FeeStructure $fee_structure)
    {
        $programmes = Programme::where('is_active', true)->orderBy('code')->get();
        $fee_structure->load('feeStructureSemesters');
        $sessionYears = AcademicSession::yearOptions(2020, 2040);

        return view('fee-structures.edit', ['structure' => $fee_structure, 'programmes' => $programmes, 'sessionYears' => $sessionYears]);
    }

    public function update(Request $request, FeeStructure $fee_structure)
    {
        $yearOpts = AcademicSession::yearOptions(2020, 2040);
        $validated = $request->validate([
            'academic_year' => ['required', 'integer', Rule::in(array_keys($yearOpts))],
            'programme_id' => ['nullable', 'exists:programmes,id'],
            'sem1_tuition' => ['required', 'numeric', 'min:0'],
            'sem1_nhif' => ['nullable', 'numeric', 'min:0'],
            'sem1_nactvet_qa' => ['nullable', 'numeric', 'min:0'],
            'sem2_tuition_continuous' => ['required', 'numeric', 'min:0'],
            'sem2_tuition_repeat_transfer' => ['required', 'numeric', 'min:0'],
            'accommodation' => ['nullable', 'numeric', 'min:0'],
            'other_charges' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);
        $validated['programme_id'] = $validated['programme_id'] ?: null;
        $validated['sem1_nhif'] = $validated['sem1_nhif'] ?? 0;
        $validated['sem1_nactvet_qa'] = $validated['sem1_nactvet_qa'] ?? 0;
        $validated['accommodation'] = $validated['accommodation'] ?? 0;
        $validated['other_charges'] = $validated['other_charges'] ?? 0;

        $aggregate = [
            'academic_year' => $validated['academic_year'],
            'programme_id' => $validated['programme_id'],
            'tuition' => $validated['sem1_tuition'] + $validated['sem2_tuition_continuous'],
            'nhif' => $validated['sem1_nhif'],
            'nactvet_qa' => $validated['sem1_nactvet_qa'],
            'accommodation' => $validated['accommodation'],
            'other_charges' => $validated['other_charges'],
            'is_active' => $request->boolean('is_active'),
        ];

        $fee_structure->update($aggregate);
        $this->syncFeeStructureSemesters($fee_structure, $validated);

        return redirect()->route('fee-structures.index')->with('success', 'Schedule updated.');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncFeeStructureSemesters(FeeStructure $structure, array $validated): void
    {
        $structure->feeStructureSemesters()->delete();

        FeeStructureSemester::create([
            'fee_structure_id' => $structure->id,
            'semester_number' => 1,
            'tuition' => $validated['sem1_tuition'],
            'nhif' => $validated['sem1_nhif'] ?? 0,
            'nactvet_qa' => $validated['sem1_nactvet_qa'] ?? 0,
            'tuition_repeat_transfer' => null,
        ]);

        FeeStructureSemester::create([
            'fee_structure_id' => $structure->id,
            'semester_number' => 2,
            'tuition' => $validated['sem2_tuition_continuous'],
            'nhif' => 0,
            'nactvet_qa' => 0,
            'tuition_repeat_transfer' => $validated['sem2_tuition_repeat_transfer'],
        ]);
    }

    public function destroy(FeeStructure $fee_structure)
    {
        $fee_structure->delete();

        return redirect()->route('fee-structures.index')->with('success', 'Schedule removed.');
    }

    public function bulkDestroy(Request $request)
    {
        return $this->bulkDestroyRecords($request, FeeStructure::class, 'fee-structures.index', singularLabel: 'fee schedule');
    }
}
