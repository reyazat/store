<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        then: function () {
            // Load API routes with 'api' prefix
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register global middleware (applied to all routes)
        $middleware->append(\App\Http\Middleware\ForceJsonResponse::class);
        $middleware->append(\App\Http\Middleware\SetLocaleMiddleware::class);
        $middleware->append(\IamApi\Http\Middleware\SecurityHeaders::class);

        // API throttle (rate limiting) - applied to all API routes
        // Note: sync/export routes have their own stricter throttle:sync/throttle:export
        // Both throttles will apply (most restrictive wins)
        $middleware->api(append: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        ]);

        // Register middleware aliases
        $middleware->alias([
            'locale' => \App\Http\Middleware\SetLocaleMiddleware::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'captcha.throttle' => \IamApi\Http\Middleware\CaptchaOrThrottle::class,
            'password.expiry' => \IamApi\Http\Middleware\CheckPasswordExpiry::class,
            'super.admin' => \IamApi\Http\Middleware\EnsureSuperAdmin::class,
            'owner.or.super' => \IamApi\Http\Middleware\EnsureOwnerOrSuperAdmin::class,
            'org.context' => \IamApi\Http\Middleware\SetOrganizationContext::class,
            'ensure.org.context' => \IamApi\Http\Middleware\EnsureOrganizationContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
