<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
        ]);

        $login = trim($request->input('login'));
        $user = null;

        if (str_contains($login, '@')) {
            $user = User::where('email', $login)->first();
        } else {
            $user = User::where('nactvet_reg_no', $login)
                ->orWhere('check_number', $login)
                ->orWhere('staff_id', $login)
                ->first();
        }

        if (! $user || ! $user->email) {
            throw ValidationException::withMessages([
                'login' => 'No account with a registered email was found. Contact the Academic Office or IT.',
            ]);
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'login' => __($status),
            ]);
        }

        return back()->with('success', 'If an account exists for that login, a reset link was sent to the registered email.');
    }
}
