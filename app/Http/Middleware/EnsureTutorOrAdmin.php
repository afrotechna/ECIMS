<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTutorOrAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->canAccessAcademics()) {
            abort(403, 'This area is for staff with an academic portfolio (see role permissions).');
        }

        return $next($request);
    }
}
