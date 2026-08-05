<?php

namespace App\Http\Controllers;

use App\Models\ProfileEditSetting;
use Illuminate\Http\Request;

class ProfileEditLockController extends Controller
{
    public function edit()
    {
        return view('profile-lock.edit', [
            'setting' => ProfileEditSetting::current()->load('lockedByUser'),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'is_locked' => ['nullable', 'boolean'],
        ]);

        $setting = ProfileEditSetting::current();
        $isLocked = $request->boolean('is_locked');

        $setting->fill([
            'is_locked' => $isLocked,
            'locked_by' => auth()->id(),
            'locked_at' => now(),
        ]);
        $setting->save();
        ProfileEditSetting::clearCache();

        return back()->with('success', $isLocked
            ? 'Profile editing is now locked. Staff who already completed their profile can view it but not change it.'
            : 'Profile editing is unlocked. Staff can update their profile details again.');
    }
}
