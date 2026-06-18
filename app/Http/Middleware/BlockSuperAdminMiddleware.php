<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockSuperAdminMiddleware
{
    /**
     * Block super admin users from accessing customer-only routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_super_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Super admin accounts cannot perform customer actions.',
            ], 403);
        }

        return $next($request);
    }
}
