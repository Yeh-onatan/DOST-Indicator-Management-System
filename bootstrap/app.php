<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register middleware aliases
        $middleware->alias([
            'superadmin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'admin' => \App\Http\Middleware\EnsureAdministrator::class,
            'permission' => \App\Http\Middleware\HasPermission::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\RejectHttpsInDevelopment::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
            \Illuminate\Http\Middleware\TrustProxies::class,
            // Security headers
            \App\Http\Middleware\SecurityHeaders::class,
            // Eager load user relationships to prevent lazy loading violations
            \App\Http\Middleware\EagerLoadUserRelationships::class,
        ]);

        // Check password expiry for authenticated users
        $middleware->append(\App\Http\Middleware\CheckPasswordExpiry::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
