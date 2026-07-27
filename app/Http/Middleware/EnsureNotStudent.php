<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ($user->isStudent() || $user->isGuardian())) {
            abort(403, 'This area is for staff only.');
        }

        return $next($request);
    }
}
