<?php

namespace App\Http\Middleware;

use App\Models\MaintenanceSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /** Routes that must keep working during maintenance so the public can see why, and an admin can sign in to turn it off. */
    private const ALWAYS_ALLOWED = ['home', 'locale.switch', 'login.create', 'login.store', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $setting = MaintenanceSetting::current();

        if (! $setting->isEffectiveNow()) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && $user->isAdmin()) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::ALWAYS_ALLOWED, true)) {
            return $next($request);
        }

        return response()->view('maintenance', ['setting' => $setting], 503);
    }
}
