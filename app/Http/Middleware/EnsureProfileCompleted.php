<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->profile_completed_at && ! $user->must_change_password && ! $request->routeIs('profile.complete*')) {
            return redirect()->route('profile.complete')->with('info', 'Please complete your profile.');
        }

        return $next($request);
    }
}
