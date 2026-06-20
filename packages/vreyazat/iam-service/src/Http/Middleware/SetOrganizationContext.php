<?php

namespace IamApi\Http\Middleware;

use Closure;
use Spatie\Permission\PermissionRegistrar;

class SetOrganizationContext
{
    /**
     * Set the organization context for Spatie Permission
     * 
     * Since each user now has only one organization, we use the user's organization_id
     * instead of a header parameter.
     * 
     * For buyers (organization_id = null), we explicitly set context to null
     * to prevent permission checks from using a stale organization context.
     */
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        
        if ($user) {
            // Set organization context (null for buyers, organization_id for store members)
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);
        }
        
        return $next($request);
    }
}
