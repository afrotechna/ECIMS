<?php

namespace App\Http\Controllers;

use App\Http\Middleware\CheckMaintenanceMode;
use App\Models\MaintenanceSetting;
use App\Models\User;
use App\Notifications\MaintenanceScheduledNotification;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;

class MaintenanceController extends Controller
{
    /**
     * Memorable, publicly-reachable path back in during a full maintenance lockout.
     * Grants only a time-limited login-page bypass for this session — actually signing
     * in still requires real admin credentials (see CheckMaintenanceMode).
     */
    public function adminEntry(Request $request): RedirectResponse
    {
        $request->session()->put(CheckMaintenanceMode::BYPASS_SESSION_KEY, now()->addMinutes(30));

        return redirect()->route('login.create');
    }

    public function edit()
    {
        return view('maintenance.edit', [
            'setting' => MaintenanceSetting::current(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'is_active' => ['nullable', 'boolean'],
            'title' => ['nullable', 'string', 'max:150'],
            'message' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $setting = MaintenanceSetting::current();

        $setting->fill([
            'is_active' => $request->boolean('is_active'),
            'title' => $data['title'] ?? null,
            'message' => $data['message'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'activated_by' => auth()->id(),
        ]);

        $shouldNotify = $setting->is_active && $setting->isDirty(['is_active', 'title', 'message', 'starts_at', 'ends_at']);

        $setting->save();
        MaintenanceSetting::clearCache();

        if ($shouldNotify) {
            Notification::send(User::all(), new MaintenanceScheduledNotification($setting));
        }

        return back()->with('success', $setting->is_active
            ? 'Maintenance mode updated — all users have been notified.'
            : 'Maintenance mode deactivated. The system is accessible to everyone again.');
    }
}
