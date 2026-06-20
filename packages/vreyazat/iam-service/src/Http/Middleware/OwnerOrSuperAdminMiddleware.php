<?php

namespace IamApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class OwnerOrSuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Get organization from route parameter
        $org = $request->route('org');

        // Check if user is super admin
        if ($user->is_super_admin) {
            return $next($request);
        }

        // Check if user belongs to the organization and has appropriate role
        if ($user->organization_id === $org->id) {
            // Set organization context for permission check
            app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

            // Check if user has owner, admin, or editor role
            // Owner: full control
            // Admin: can manage most organization settings
            // Editor: can manage content and products
            if ($user->hasRole(['owner', 'admin', 'editor'])) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Forbidden. This action requires owner, admin, or editor role.'
        ], 403);
    }
}
