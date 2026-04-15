<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * Force all requests to expect JSON responses and set proper headers.
     * This is essential for API-based applications.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        // Only force JSON for API routes
        if ($request->is('api/*') || $request->expectsJson()) {
            $request->headers->set('Accept', 'application/json');
        }

        $response = $next($request);

        // Ensure response is JSON only for API routes
        if ($response instanceof Response && ($request->is('api/*') || $request->expectsJson())) {
            // Set JSON content type if not already set
            if (!$response->headers->has('Content-Type')) {
                $response->headers->set('Content-Type', 'application/json');
            }

            // Add CORS headers if needed
            if (config('cors.enabled', true)) {
                $this->addCorsHeaders($response, $request);
            }

            // Add security headers
            $this->addSecurityHeaders($response);

            // Add API version header
            $response->headers->set('X-API-Version', config('app.api_version', '1.0'));
        }

        return $response;
    }

    /**
     * Add CORS headers to response.
     *
     * @param Response $response
     * @param Request $request
     * @return void
     */
    protected function addCorsHeaders(Response $response, Request $request): void
    {
        $allowedOrigins = config('cors.allowed_origins', ['*']);
        $origin = $request->header('Origin');

        // Check if origin is allowed
        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin ?: '*');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-Locale, X-API-Key');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400'); // 24 hours
    }

    /**
     * Add security headers to response.
     *
     * @param Response $response
     * @return void
     */
    protected function addSecurityHeaders(Response $response): void
    {
        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // XSS Protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Remove X-Powered-By header
        $response->headers->remove('X-Powered-By');
    }
}
