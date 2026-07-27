<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GuardianAccessController extends Controller
{
    public function store(Request $request, Student $student)
    {
        $validated = $request->validate([
            'guardian_email' => ['required', 'email', 'max:255'],
        ]);

        if (! $student->guardian_phone && ! $student->guardian_name) {
            return back()->with('error', 'Add guardian name and phone on the student profile first.');
        }

        $existing = User::where('linked_student_id', $student->id)->where('role', 'guardian')->first();
        if ($existing) {
            $existing->update(['email' => $validated['guardian_email']]);

            return back()->with('success', 'Guardian login email updated.');
        }

        $tempPassword = Str::password(10);
        $name = $student->guardian_name ?: 'Guardian';

        User::create([
            'name' => $name,
            'surname' => $student->last_name,
            'email' => $validated['guardian_email'],
            'password' => Hash::make($tempPassword),
            'role' => 'guardian',
            'linked_student_id' => $student->id,
            'must_change_password' => true,
            'profile_completed_at' => now(),
        ]);

        return back()->with('success', "Guardian portal enabled. Email: {$validated['guardian_email']} — temporary password: {$tempPassword} (share securely; user must change on first login).");
    }

    public function destroy(Student $student)
    {
        User::where('linked_student_id', $student->id)->where('role', 'guardian')->delete();

        return back()->with('success', 'Guardian portal access removed.');
    }
}
