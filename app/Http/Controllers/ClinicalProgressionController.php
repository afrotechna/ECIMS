<?php

namespace App\Http\Controllers;

use App\Models\ClinicalProgressionDecision;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClinicalProgressionController extends Controller
{
    public function index(Request $request)
    {
        $semesterId = $request->integer('semester_id') ?: Semester::query()->where('is_active', true)->orderByDesc('academic_year')->orderByDesc('number')->value('id');

        $decisions = ClinicalProgressionDecision::query()
            ->with(['student.programme', 'semester', 'decider'])
            ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId))
            ->orderByDesc('decided_at')
            ->paginate(30)
            ->withQueryString();

        $semesters = Semester::query()->orderByDesc('academic_year')->orderByDesc('number')->limit(12)->get();

        return view('clinical.progression-index', compact('decisions', 'semesters', 'semesterId'));
    }

    public function store(Request $request, Student $student)
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'decision' => ['required', Rule::in(array_keys(ClinicalProgressionDecision::decisionOptions()))],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        ClinicalProgressionDecision::updateOrCreate(
            [
                'student_id' => $student->id,
                'semester_id' => $validated['semester_id'],
            ],
            [
                'decision' => $validated['decision'],
                'notes' => $validated['notes'] ?? null,
                'decided_by' => auth()->id(),
                'decided_at' => now(),
            ]
        );

        return back()->with('success', 'Progression decision recorded for '.$student->full_name.'.');
    }
}
