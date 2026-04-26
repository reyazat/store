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
     */
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        
        // If user is authenticated, use their organization_id
        if ($user && $user->organization_id) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);
        }
        
        return $next($request);
    }
}
