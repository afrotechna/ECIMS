<?php

use App\Http\Middleware\EnsureBursarOrAdmin;
use App\Http\Middleware\LinkStudentUserRecord;
use App\Http\Middleware\EnsureNotStudent;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureProfileCompleted;
use App\Http\Middleware\EnsureStaffModulePermission;
use App\Http\Middleware\EnsureTutorOrAdmin;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Cloudflare Tunnel: trust forwarded HTTPS/host so sessions & CSRF work (fixes 419).
        $trusted = env('TRUSTED_PROXIES');
        if ($trusted !== null && $trusted !== '') {
            $middleware->trustProxies(
                at: $trusted === '*' ? '*' : array_map('trim', explode(',', $trusted)),
                headers: Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO
                    | Request::HEADER_X_FORWARDED_PREFIX,
            );
        }

        $middleware->web(append: [
            SetLocale::class,
            LinkStudentUserRecord::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login.create'));
        $middleware->alias([
            'guest' => RedirectIfAuthenticated::class,
            'admin' => EnsureUserIsAdmin::class,
            'password.changed' => EnsurePasswordChanged::class,
            'profile.completed' => EnsureProfileCompleted::class,
            'not_student' => EnsureNotStudent::class,
            'tutor_or_admin' => EnsureTutorOrAdmin::class,
            'bursar_or_admin' => EnsureBursarOrAdmin::class,
            'module.permission' => EnsureStaffModulePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
