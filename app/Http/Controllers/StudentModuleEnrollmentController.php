<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use App\Services\StudentModuleEnrollmentService;
use Illuminate\Http\Request;

class StudentModuleEnrollmentController extends Controller
{
    public function edit(Request $request, StudentModuleEnrollmentService $service)
    {
        $student = $request->user()->student;
        if (! $student) {
            return redirect()->route('dashboard');
        }

        $context = $service->registrationContext(
            $student,
            $request->filled('semester_id') ? $request->integer('semester_id') : null
        );

        $enrolledIds = $context['enrolled_ids'] ?? [];
        $available = $context['available'] ?? collect();
        $editMode = $request->boolean('edit') || $enrolledIds === [];

        return view('students.my-module-registration', array_merge(
            ['student' => $student],
            $context,
            [
                'edit_mode' => $editMode,
                'enrolled_courses' => $available->whereIn('id', $enrolledIds)->values(),
                'unselected_courses' => $available->whereNotIn('id', $enrolledIds)->values(),
            ]
        ));
    }

    public function update(Request $request, StudentModuleEnrollmentService $service)
    {
        $student = $request->user()->student;
        if (! $student) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
            'carry_repeat_course_ids' => ['nullable', 'array'],
            'carry_repeat_course_ids.*' => ['integer', 'exists:courses,id'],
        ]);

        $semester = Semester::findOrFail($validated['semester_id']);
        $service->saveForSemester(
            $student,
            $semester,
            $validated['course_ids'],
            $validated['carry_repeat_course_ids'] ?? []
        );

        return redirect()
            ->route('my.module-registration', ['semester_id' => $semester->id])
            ->with('success', 'Your module selection for '.$semester->label.' has been saved. You can change it anytime using “Change selection”.');
    }
}
