<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfilePhotoController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048', 'dimensions:min_width=200,min_height=200'],
        ], [
            'photo.dimensions' => 'The photo is too small. Please upload an image at least 200x200 pixels.',
            'photo.mimes' => 'The photo must be a JPG or PNG file.',
        ]);

        $user = auth()->user();
        $oldPath = $user->profile_photo_path;

        $path = $request->file('photo')->store('profile-photos/'.$user->id, 'public');
        $user->update(['profile_photo_path' => $path]);

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return back()->with('success', 'Profile photo updated.');
    }

    public function destroy(Request $request)
    {
        $user = auth()->user();
        if ($user->profile_photo_path) {
            if (Storage::disk('public')->exists($user->profile_photo_path)) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            $user->update(['profile_photo_path' => null]);
        }

        return back()->with('success', 'Profile photo removed.');
    }
}
