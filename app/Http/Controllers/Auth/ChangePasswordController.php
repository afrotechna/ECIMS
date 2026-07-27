<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ChangePasswordController extends Controller
{
    public function create()
    {
        return view('auth.change-password');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        if (! $request->user()->profile_completed_at) {
            return redirect()->route('profile.complete')->with('success', 'Password changed. Please complete your profile.');
        }

        return redirect()->route('dashboard')->with('success', 'Password changed.');
    }
}
