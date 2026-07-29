<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($request->input('login'));
        $user = null;

        if (str_contains($login, '@')) {
            $user = User::where('email', $login)->first();
            if (! $user || (! $user->isAdmin() && ! $user->isGuardian())) {
                $user = null;
            }
        } else {
            $user = User::where('nactvet_reg_no', $login)
                ->orWhere('check_number', $login)
                ->orWhere('staff_id', $login)
                ->first();
        }

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($user->isStudent()) {
            $user->ensureLinkedToStudentRecord();
        }

        if ($user->isGuardian() && ! $user->linked_student_id) {
            throw ValidationException::withMessages([
                'login' => 'Guardian account is not linked to a student. Contact the college.',
            ]);
        }

        if ($user->must_change_password) {
            return redirect()->route('password.change')->with('info', 'You must change your password before continuing.');
        }
        if (! $user->profile_completed_at) {
            return redirect()->route('profile.complete')->with('info', 'Please complete your profile.');
        }

        if ($user->isGuardian()) {
            return redirect()->intended(route('parent.portal'))->with('success', 'Welcome, '.$user->name.'.');
        }

        return redirect()->intended(route('dashboard'))->with('success', 'Welcome back, '.$user->name.'!');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
