<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreOwnerMiddleware
{
    /**
     * Allow users with management roles in an Organization (store).
     * Super admins are explicitly blocked — they manage the platform via /v1/admin,
     * and can view store data via /v1/admin/organizations/{id}/* routes.
     *
     * Supported roles:
     * - owner: full control
     * - admin: can manage most store settings and members
     * - editor: can manage content and products
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

        // Super admin must use /v1/admin/organizations/{id}/* routes
        if ($user->is_super_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Super admin accounts must use the platform admin panel.',
            ], 403);
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
