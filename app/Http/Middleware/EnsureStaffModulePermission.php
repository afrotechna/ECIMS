<?php

namespace App\Http\Middleware;

use App\Support\RolePermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffModulePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if ($user->isStudent() && in_array($routeName, ['calendar.index', 'calendar.events.feed', 'institution-documents.download'], true)) {
            return $next($request);
        }

        if ($user->isGuardian() && in_array($routeName, [
            'parent.portal',
            'college-documents.index',
            'institution-documents.download',
            'payments.receipt',
            'password.change',
            'password.change.store',
            'logout',
            'notifications.index',
            'notifications.read',
            'notifications.read-all',
        ], true)) {
            return $next($request);
        }

        if ($user->isStudent()) {
            return $next($request);
        }

        $resolved = RolePermissions::resolveRoutePermission($request);
        if ($resolved === null) {
            return $next($request);
        }

        [$module, $action] = $resolved;
        if (! $user->canModule($module, $action)) {
            abort(403, 'Your role ('.(\App\Models\User::roleLabel($user->role)).') cannot '.$action.' in: '.(RolePermissions::modules()[$module] ?? $module).'.');
        }

        return $next($request);
    }
}
