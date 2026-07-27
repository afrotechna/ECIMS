<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Support\StudentSurname;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GuardianAccountController extends Controller
{
    public function store(Request $request, Student $student)
    {
        $validated = $request->validate([
            'guardian_email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        $email = $validated['guardian_email'];
        $existing = User::where('guardian_of_student_id', $student->id)->where('role', 'guardian')->first();
        if ($existing) {
            return back()->withErrors(['guardian_email' => 'This student already has a guardian login ('.$existing->email.').']);
        }

        $name = $student->guardian_name ?: 'Guardian';
        $password = Str::random(10);
        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'guardian',
            'guardian_of_student_id' => $student->id,
            'must_change_password' => true,
            'profile_completed_at' => now(),
        ]);

        return back()->with('success', 'Guardian login created. Email: '.$email.' Temporary password: '.$password);
    }
}
