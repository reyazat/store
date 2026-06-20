<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Platform admin panel is exclusively for super admins.
        // Store owners and editors manage their own stores via /v1/store/* routes.
        if (!$user->is_super_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Platform admin privileges required.',
            ], 403);
        }

        return $next($request);
    }
}
