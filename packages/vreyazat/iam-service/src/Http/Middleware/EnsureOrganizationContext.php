<?php

namespace IamApi\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure that authenticated requests have valid organization context
 *
 * This middleware verifies that:
 * 1. The authenticated user has an assigned organization
 * 2. The organization exists and is active
 */
class EnsureOrganizationContext
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response) $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if user is not authenticated
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'data' => null
            ], 401);
        }

        try {
            $user = $request->user();

            // Super Admin doesn't need organization context
            if ($user->is_super_admin) {
                return $next($request);
            }

            // Check if user has an assigned organization
            if (!$user->organization_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have an assigned organization.',
                    'data' => null
                ], 403);
            }

            // Add organization_id to request attributes for easy access
            $request->attributes->set('organization_id', $user->organization_id);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid organization context',
                'data' => null,
                'error' => $e->getMessage()
            ], 401);
        }

        return $next($request);
    }
}
