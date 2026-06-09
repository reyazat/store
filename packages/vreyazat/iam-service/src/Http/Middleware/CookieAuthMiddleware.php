<?php

namespace IamApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * Middleware to extract JWT token from HttpOnly cookie
 * Falls back to Authorization header if cookie is not present
 */
class CookieAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Try to get token from cookie first
        $cookieToken = $request->cookie('access_token');
        
        if ($cookieToken) {
            // Set the token for JWTAuth
            try {
                JWTAuth::setToken($cookieToken);
                // Also set it in the request for the auth guard
                $request->headers->set('Authorization', 'Bearer ' . $cookieToken);
            } catch (\Exception $e) {
                // Invalid token in cookie, will fall back to header
            }
        }

        return $next($request);
    }
}
