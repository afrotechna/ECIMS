<?php

namespace App\Http\Controllers;

use App\Models\GraduationClearance;
use App\Models\Student;
use Illuminate\Http\Request;

class GraduationClearanceController extends Controller
{
    public function index()
    {
        $clearances = GraduationClearance::with('student.programme')->orderBy('student_id')->paginate(20);
        return view('graduation-clearances.index', compact('clearances'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'library_cleared' => ['nullable', 'string', 'in:yes,no'],
            'finance_cleared' => ['nullable', 'string', 'in:yes,no'],
            'accommodation_cleared' => ['nullable', 'string', 'in:yes,no'],
            'academic_cleared' => ['nullable', 'string', 'in:yes,no'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $validated['library_cleared'] = $validated['library_cleared'] ?? 'no';
        $validated['finance_cleared'] = $validated['finance_cleared'] ?? 'no';
        $validated['accommodation_cleared'] = $validated['accommodation_cleared'] ?? 'no';
        $validated['academic_cleared'] = $validated['academic_cleared'] ?? 'no';
        GraduationClearance::updateOrCreate(
            ['student_id' => $validated['student_id']],
            $validated
        );
        return redirect()->route('graduation-clearances.index')->with('success', 'Clearance saved.');
    }

    public function edit(GraduationClearance $graduation_clearance)
    {
        $graduation_clearance->load('student');
        $students = Student::where('status', 'active')->orderBy('reg_no')->get();
        return view('graduation-clearances.edit', compact('graduation_clearance', 'students'));
    }

    public function update(Request $request, GraduationClearance $graduation_clearance)
    {
        $validated = $request->validate([
            'library_cleared' => ['nullable', 'string', 'in:yes,no'],
            'finance_cleared' => ['nullable', 'string', 'in:yes,no'],
            'accommodation_cleared' => ['nullable', 'string', 'in:yes,no'],
            'academic_cleared' => ['nullable', 'string', 'in:yes,no'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $graduation_clearance->update($validated);
        return redirect()->route('graduation-clearances.index')->with('success', 'Clearance updated.');
    }
}
