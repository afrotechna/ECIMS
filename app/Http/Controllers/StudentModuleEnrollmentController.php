<?php

namespace App\Http\Controllers;

use App\Services\StudentModuleEnrollmentService;
use Illuminate\Http\Request;

/**
 * View-only for students: modules are registered on their behalf by staff
 * (module catalogue assignment), not self-selected.
 */
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

        return view('students.my-module-registration', array_merge(
            ['student' => $student],
            $context,
            [
                'enrolled_courses' => $available->whereIn('id', $enrolledIds)->values(),
            ]
        ));
    }
}
