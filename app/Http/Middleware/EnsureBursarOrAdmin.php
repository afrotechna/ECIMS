<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBursarOrAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->canAccessFinance()) {
            abort(403, 'This area is for staff with a finance portfolio (see role permissions).');
        }

        return $next($request);
    }
}
