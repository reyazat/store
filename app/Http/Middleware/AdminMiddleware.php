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

        // Super admin always has full access
        if ($user->is_super_admin) {
            return $next($request);
        }

        // Check if user has admin role
        $adminRoles = ['superadmin', 'admin', 'owner', 'editor'];

        $userRoles = $user->roles()->pluck('name')->toArray();

        $hasAdminRole = !empty(array_intersect($adminRoles, $userRoles));

        if (!$hasAdminRole) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin privileges required.',
            ], 403);
        }

        return $next($request);
    }
}
