<?php

namespace IamApi\Http\Middleware;

use IamApi\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwnerOrSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * Ensures the user is either:
     * - Super Admin, OR
     * - Owner/Admin/Editor of the organization in the route parameter
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'data' => null,
            ], 401);
        }

        // Super Admin has access to everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Get organization from route parameter
        $org = $request->route('org');

        if (!$org instanceof Organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found',
                'data' => null,
            ], 404);
        }

        // User must belong to this organization
        if (!$user->belongsToOrganization($org)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not belong to this organization',
                'data' => null,
            ], 403);
        }

        // Set organization context for Spatie permission check
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

        // Check if user has owner, admin, or editor role
        // Owner: full control (can manage roles, permissions, members)
        // Admin: can manage most organization settings
        // Editor: can manage content and products
        if (!$user->hasRole(['owner', 'admin', 'editor'])) {
            return response()->json([
                'success' => false,
                'message' => 'This action requires organization owner, admin, or editor privileges',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
