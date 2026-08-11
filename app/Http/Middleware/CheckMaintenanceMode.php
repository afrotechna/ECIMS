<?php

namespace App\Http\Middleware;

use App\Models\MaintenanceSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /** Session key holding "unlocked until" timestamp, set by visiting the admin-entry route. */
    public const BYPASS_SESSION_KEY = 'maintenance_admin_entry_until';

    /**
     * Routes that stay reachable even during a full lockout: locale switching (harmless,
     * used from the maintenance page itself), logout, and the admin-entry route that
     * grants the login bypass below. Everything else — including the public landing page
     * and the normal login page — shows the maintenance screen while active.
     */
    private const ALWAYS_ALLOWED = ['locale.switch', 'logout', 'maintenance.admin-entry'];

    /** Only reachable once the admin-entry route has set a valid, unexpired bypass. */
    private const LOGIN_ROUTES = ['login.create', 'login.store'];

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

        $routeName = $request->route()?->getName();

        if (in_array($routeName, self::ALWAYS_ALLOWED, true)) {
            return $next($request);
        }

        if (in_array($routeName, self::LOGIN_ROUTES, true) && $this->hasValidBypass($request)) {
            return $next($request);
        }

        return response()->view('maintenance', ['setting' => $setting], 503);
    }

    private function hasValidBypass(Request $request): bool
    {
        $until = $request->session()->get(self::BYPASS_SESSION_KEY);

        return $until instanceof \Illuminate\Support\Carbon && now()->lt($until);
    }
}
