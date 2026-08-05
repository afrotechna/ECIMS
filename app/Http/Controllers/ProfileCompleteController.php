<?php

namespace App\Http\Controllers;

use App\Models\Programme;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileCompleteController extends Controller
{
    public function create()
    {
        $user = auth()->user();
        $student = $user->isStudent() ? $user->ensureLinkedToStudentRecord() : $user->student;
        $programmes = Programme::where('is_active', true)->orderBy('code')->get();
        if ($user->isTutorStaff()) {
            $user->load('programmes');
        }
        $tanzaniaLocations = config('tanzania_locations');

        return view('profile.complete', compact('user', 'student', 'programmes', 'tanzaniaLocations'));
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
            'middle_name' => ['nullable', 'string', 'max:100'],
            'surname' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['required', 'string', 'max:20'],
            'sex' => ['required', 'string', Rule::in(array_keys(User::SEX_OPTIONS))],
            'nationality' => ['required', 'string', 'max:100'],
            'region' => ['required_if:nationality,Tanzanian', 'nullable', 'string', 'max:100'],
            'district' => ['required_if:nationality,Tanzanian', 'nullable', 'string', 'max:100'],
            'ward' => ['required_if:nationality,Tanzanian', 'nullable', 'string', 'max:100'],
            'street' => ['required_if:nationality,Tanzanian', 'nullable', 'string', 'max:150'],
            'qualification' => ['nullable', 'string', 'max:150'],
            'education_level' => ['nullable', 'string', 'max:100'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'license_board' => ['nullable', 'string', 'max:150'],
            'employment_type' => ['nullable', 'string', Rule::in(array_keys(User::EMPLOYMENT_TYPES))],
            'check_number' => [
                'required_if:employment_type,permanent', 'nullable', 'string', 'max:50',
                Rule::unique('users', 'check_number')->ignore($user->id),
            ],
            'programme_ids' => [$user->isTutorStaff() ? 'required' : 'nullable', 'array', 'min:1', 'max:3'],
            'programme_ids.*' => ['integer', 'exists:programmes,id'],
        ]);

        $programmeIds = $validated['programme_ids'] ?? [];
        unset($validated['programme_ids']);

        if ($validated['nationality'] !== 'Tanzanian') {
            $validated['region'] = null;
            $validated['district'] = null;
            $validated['ward'] = null;
            $validated['street'] = null;
        }

        if ($validated['employment_type'] !== 'permanent') {
            $validated['check_number'] = null;
        }

        $user->update(array_merge($validated, ['profile_completed_at' => now()]));

        if ($user->isTutorStaff()) {
            $user->programmes()->sync($programmeIds);
        }

        return redirect()->route('dashboard')->with('success', 'Profile completed.');
    }
}
