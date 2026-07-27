<?php

namespace App\Http\Controllers;

use App\Models\ClinicalProcedure;
use App\Models\Programme;
use App\Support\ClinicalRotationCatalog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClinicalProcedureController extends Controller
{
    public function index(Request $request)
    {
        $nta = $request->integer('nta_level') ?: null;

        $procedures = ClinicalProcedure::query()
            ->with('programme')
            ->when($nta, fn ($q) => $q->where('nta_level', $nta))
            ->orderBy('nta_level')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('clinical.procedures-index', compact('procedures', 'nta'));
    }

    public function create()
    {
        $programmes = Programme::query()->where('is_active', true)->orderBy('code')->get();

        return view('clinical.procedures-form', [
            'procedure' => new ClinicalProcedure(['nta_level' => 5, 'is_active' => true]),
            'programmes' => $programmes,
            'departmentLabels' => ClinicalRotationCatalog::departmentLabels(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateProcedure($request);
        ClinicalProcedure::create($validated);

        return redirect()->route('clinical-procedures.index')->with('success', 'Clinical procedure added.');
    }

    public function edit(ClinicalProcedure $clinical_procedure)
    {
        $programmes = Programme::query()->where('is_active', true)->orderBy('code')->get();
        $departmentLabels = ClinicalRotationCatalog::departmentLabelsForNtaLevel((int) $clinical_procedure->nta_level);

        return view('clinical.procedures-form', [
            'procedure' => $clinical_procedure,
            'programmes' => $programmes,
            'departmentLabels' => $departmentLabels,
        ]);
    }

    public function update(Request $request, ClinicalProcedure $clinical_procedure)
    {
        $clinical_procedure->update($this->validateProcedure($request));

        return redirect()->route('clinical-procedures.index')->with('success', 'Procedure updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProcedure(Request $request): array
    {
        $validated = $request->validate([
            'programme_id' => ['nullable', 'exists:programmes,id'],
            'nta_level' => ['required', Rule::in([4, 5, 6])],
            'department_code' => ['nullable', 'string', 'max:64'],
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'assessment_modes' => ['nullable', 'string', 'max:255'],
            'practicum_section' => ['nullable', 'string', 'max:120'],
            'min_required_count' => ['nullable', 'integer', 'min:0', 'max:999'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        return $validated;
    }
}
