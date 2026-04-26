<?php

namespace IamApi\Http\Middleware;

use IamApi\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwnerOrSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * Ensures the user is either:
     * - Super Admin, OR
     * - Owner of the organization in the route parameter
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

        // Check if user is owner of this organization
        if (!$user->isOwnerOf($org)) {
            return response()->json([
                'success' => false,
                'message' => 'This action requires organization owner privileges',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
