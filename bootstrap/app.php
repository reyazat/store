<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

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
        // Trust all proxies (required when behind nginx-proxy-manager)
        // '**' means trust any IP (safe for Docker internal networks)
        $middleware->trustProxies(at: '**', headers: 
            \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        );

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
        // Override ValidationException JSON response to produce a fully Farsi summary message.
        // Laravel's default summarize() appends "(and N more errors)" in English — we replace
        // that suffix with its Persian equivalent before the response is sent.
        $exceptions->render(function (ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                $errors  = $e->errors();
                $allMsgs = array_merge(...array_values($errors));
                $first   = $allMsgs[0] ?? 'خطای اعتبارسنجی';
                $rest    = count($allMsgs) - 1;

                if ($rest > 0) {
                    $message = $first . ' ' . "(و {$rest} خطای دیگر)";
                } else {
                    $message = $first;
                }

                return response()->json([
                    'message' => $message,
                    'errors'  => $errors,
                ], 422);
            }
        });
    })->create();
