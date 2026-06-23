<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreOwnerMiddleware
{
    /**
     * Allow users that belong to an Organization (store) to access the store panel.
     * Super admins are explicitly blocked — they manage the platform via /v1/admin,
     * and can view store data via /v1/admin/organizations/{id}/* routes.
     *
     * Supported roles and their access level:
     * - owner   : full control (all read + write)
     * - admin   : manage store settings, members, products, orders
     * - editor  : manage products and content; view orders
     * - user    : view orders, products (read-only panel access)
     * - member  : view products and users (read-only panel access)
     *
     * Write operations are further guarded inside each controller/action via
     * OrganizationService::hasPermission(), so role-level access is the only
     * check needed here.
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

        if (!$user->hasRole(['owner', 'admin', 'editor', 'user', 'member'])) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this store panel.',
            ], 403);
        }

        return $next($request);
    }
}
