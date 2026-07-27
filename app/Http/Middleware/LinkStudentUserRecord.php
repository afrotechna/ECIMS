<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LinkStudentUserRecord
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user?->isStudent()) {
            $user->ensureLinkedToStudentRecord();
        }

        return $next($request);
    }
}
