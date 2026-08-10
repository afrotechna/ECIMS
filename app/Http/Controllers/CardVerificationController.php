<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\View\View;

class CardVerificationController extends Controller
{
    /** Public, unauthenticated: anyone scanning a student ID card's QR code lands here. */
    public function show(string $token): View
    {
        $student = Student::where('card_verification_token', $token)->with('programme')->first();

        if (! $student) {
            return view('card-verification.show', ['student' => null]);
        }

        $isValid = $student->status === 'active' && $student->idCardReady();
        $photoUrl = $student->userAccount?->profile_photo_url;

        return view('card-verification.show', [
            'student' => $student,
            'isValid' => $isValid,
            'photoUrl' => $photoUrl,
        ]);
    }
}
