<?php

namespace App\Http\Controllers;

use App\Models\Programme;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileCompleteController extends Controller
{
    public function create()
    {
        $user = auth()->user();
        $student = $user->isStudent() ? $user->ensureLinkedToStudentRecord() : $user->student;
        $programmes = Programme::where('is_active', true)->orderBy('code')->get();

        return view('profile.complete', compact('user', 'student', 'programmes'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if ($user->isStudent()) {
            if (! $user->student) {
                return redirect()->route('dashboard')->with('error', 'Your account is not linked to a student record. Contact the admissions office.');
            }

            $validated = $request->validate([
                'first_name' => ['required', 'string', 'max:100'],
                'middle_name' => ['nullable', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'phone' => ['required', 'string', 'max:20'],
                'date_of_birth' => ['required', 'date', 'before:today'],
                'gender' => ['required', 'string', Rule::in(['M', 'F'])],
                'guardian_name' => ['required', 'string', 'max:150'],
                'guardian_phone' => ['required', 'string', 'max:20'],
                'guardian_relationship' => ['required', 'string', 'max:80'],
            ]);

            $user->student->update($validated);
            $user->update([
                'name' => trim($validated['first_name'].' '.($validated['middle_name'] ?? '').' '.$validated['last_name']),
                'phone' => $validated['phone'],
                'profile_completed_at' => now(),
            ]);

            return redirect()->route('dashboard')->with('success', 'Your personal and guardian details have been saved. Semester registration is completed by college staff after your fees are paid.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);
        $user->update(array_merge($validated, ['profile_completed_at' => now()]));

        return redirect()->route('dashboard')->with('success', 'Profile completed.');
    }
}
