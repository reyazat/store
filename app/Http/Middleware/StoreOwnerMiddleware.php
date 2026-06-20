<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreOwnerMiddleware
{
    /**
     * Allow users with management roles in an Organization (store).
     * Super admins can also access store panels for monitoring and management.
     *
     * Supported roles:
     * - owner: full control
     * - admin: can manage most store settings and members
     * - editor: can manage content and products
     * - super admin: can access any store panel for platform management
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

        // Super admin can access any store panel for monitoring and management
        if ($user->is_super_admin) {
            // Super admin has full access - no organization context needed
            return $next($request);
        }

        $org = $user->organization;

        if (!$org) {
            return response()->json([
                'success' => false,
                'message' => 'No store associated with this account.',
            ], 403);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($org->id);

        if (!$user->hasRole(['owner', 'admin', 'editor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Store owner, admin, or editor privileges required.',
            ], 403);
        }

        return $next($request);
    }
}
