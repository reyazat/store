<?php

namespace IamApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Mews\Captcha\Facades\Captcha;
use IamApi\Http\Resources\AbstractResource;

class CaptchaOrThrottle
{

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $type = 'login')
    {
        return match ($type) {
            'login' => $this->handleLoginThrottle($request, $next),
            'otp' => $this->handleOtpThrottle($request, $next),
            'password_reset' => $this->handlePasswordResetThrottle($request, $next),
            'registration' => $this->handleRegistrationThrottle($request, $next),
            'api' => $this->handleApiThrottle($request, $next),
            default => $next($request)
        };
    }

    /**
     * Handle login throttling with CAPTCHA
     */
    private function handleLoginThrottle(Request $request, Closure $next)
    {
        $identifier = Str::lower((string)$request->input('email', $request->input('identifier', '')));
        $key = 'login:' . $identifier . ':' . $request->ip();

        $config = config('iam.authentication.throttle', []);
        $maxAttempts = $config['max_attempts'] ?? 5;
        $lockAttempts = $config['lock_attempts'] ?? 10;
        $windowSeconds = $config['window_seconds'] ?? 900; // 15 minutes

        // Check if completely locked out
        if (RateLimiter::tooManyAttempts($key, $lockAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json(
                AbstractResource::error(
                    'Too many login attempts. Account temporarily locked.',
                    [
                        'retry_after' => $retryAfter,
                        'locked_until' => now()->addSeconds($retryAfter)->toISOString()
                    ]
                ),
                429
            );
        }

        // Check if CAPTCHA is required
        $currentAttempts = RateLimiter::attempts($key);
        if ($currentAttempts >= $maxAttempts) {
            $captchaValue = $request->input('captcha');
            $captchaKey = $request->input('captcha_key');

            if (!$captchaValue || !$captchaKey) {
                return response()->json(
                    AbstractResource::error(
                        'CAPTCHA verification required after multiple failed attempts.',
                        [
                            'captcha_required' => true,
                            'attempts' => $currentAttempts,
                            'max_attempts' => $maxAttempts
                        ]
                    ),
                    429
                );
            }

            // Verify CAPTCHA using mews/captcha
            if (!Captcha::check_api($captchaValue, $captchaKey)) {
                RateLimiter::hit($key, $windowSeconds);

                return response()->json(
                    AbstractResource::error(
                        'Invalid CAPTCHA. Please try again.',
                        [
                            'captcha_required' => true,
                            'captcha_invalid' => true
                        ]
                    ),
                    400
                );
            }

            // CAPTCHA verified, reset attempts for this window
            RateLimiter::clear($key);
        }

        // Store rate limit key for use in controllers
        $request->attributes->set('rate_limit_key', $key);
        $request->attributes->set('rate_limit_window', $windowSeconds);

        return $next($request);
    }

    /**
     * Handle OTP throttling
     */
    private function handleOtpThrottle(Request $request, Closure $next)
    {
        $identifier = $request->input('identifier', '');
        $type = $request->input('type', 'general');
        $key = 'otp:' . $type . ':' . hash('sha256', $identifier) . ':' . $request->ip();

        $maxAttempts = 5; // 5 OTP requests per 15 minutes
        $windowSeconds = 900;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json(
                AbstractResource::error(
                    'Too many OTP requests. Please try again later.',
                    ['retry_after' => $retryAfter]
                ),
                429
            );
        }

        $request->attributes->set('rate_limit_key', $key);
        $request->attributes->set('rate_limit_window', $windowSeconds);

        return $next($request);
    }

    /**
     * Handle password reset throttling
     */
    private function handlePasswordResetThrottle(Request $request, Closure $next)
    {
        $email = Str::lower((string)$request->input('email', ''));
        $key = 'password_reset:' . $email . ':' . $request->ip();

        $maxAttempts = 3; // 3 password reset requests per hour
        $windowSeconds = 3600;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json(
                AbstractResource::error(
                    'Too many password reset requests. Please try again later.',
                    ['retry_after' => $retryAfter]
                ),
                429
            );
        }

        $request->attributes->set('rate_limit_key', $key);
        $request->attributes->set('rate_limit_window', $windowSeconds);

        return $next($request);
    }

    /**
     * Handle registration throttling
     */
    private function handleRegistrationThrottle(Request $request, Closure $next)
    {
        $key = 'registration:' . $request->ip();

        $maxAttempts = 3; // 3 registration attempts per hour per IP
        $windowSeconds = 3600;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json(
                AbstractResource::error(
                    'Too many registration attempts. Please try again later.',
                    ['retry_after' => $retryAfter]
                ),
                429
            );
        }

        $request->attributes->set('rate_limit_key', $key);
        $request->attributes->set('rate_limit_window', $windowSeconds);

        return $next($request);
    }

    /**
     * Handle general API throttling
     */
    private function handleApiThrottle(Request $request, Closure $next)
    {
        $user = $request->user();
        $key = $user
            ? 'api:user:' . $user->id
            : 'api:ip:' . $request->ip();

        $maxAttempts = $user ? 60 : 20; // 60 requests per minute for authenticated users, 20 for guests
        $windowSeconds = 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json(
                AbstractResource::error(
                    'API rate limit exceeded. Please slow down your requests.',
                    [
                        'retry_after' => $retryAfter,
                        'limit' => $maxAttempts,
                        'window_seconds' => $windowSeconds
                    ]
                ),
                429
            );
        }

        // Hit the rate limiter
        RateLimiter::hit($key, $windowSeconds);

        // Add rate limit headers
        $response = $next($request);

        if (method_exists($response, 'header')) {
            $remaining = $maxAttempts - RateLimiter::attempts($key);
            $response->header('X-RateLimit-Limit', $maxAttempts);
            $response->header('X-RateLimit-Remaining', max(0, $remaining));
            $response->header('X-RateLimit-Reset', now()->addSeconds($windowSeconds)->timestamp);
        }

        return $response;
    }
}
