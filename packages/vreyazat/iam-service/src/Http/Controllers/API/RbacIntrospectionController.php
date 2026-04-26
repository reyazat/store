<?php

namespace IamApi\Http\Controllers\API;

use IamApi\Http\Resources\AbstractResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spatie\Permission\PermissionRegistrar;

/**
 * @group RBAC - Introspection
 *
 * APIs for users to view their own roles and permissions within the current organization context.
 */
class RbacIntrospectionController extends Controller
{
    /**
     * Get my roles
     *
     * Get all roles assigned to the authenticated user in their organization.
     * Requires authentication.
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": null,
     *   "data": {
     *     "organization_id": 1,
     *     "roles": [
     *       {
     *         "id": 2,
     *         "name": "editor",
     *         "guard_name": "api",
     *         "permissions_count": 5
     *       },
     *       {
     *         "id": 3,
     *         "name": "moderator",
     *         "guard_name": "api",
     *         "permissions_count": 3
     *       }
     *     ],
     *     "total": 2
     *   }
     * }
     */
    public function myRoles(Request $request): JsonResponse
    {
        $user = $request->user();

        // Super Admin has system-level access, no organization-specific roles
        if ($user->is_super_admin) {
            return response()->json(
                AbstractResource::success([
                    'is_super_admin' => true,
                    'roles' => [],
                    'total' => 0,
                    'message' => 'Super Admin has system-level access'
                ])
            );
        }

        // Get user's organization
        if (!$user->organization_id) {
            return response()->json(
                AbstractResource::error('User does not have an assigned organization'),
                403
            );
        }

        $organizationId = $user->organization_id;

        // Set the team context
        app(PermissionRegistrar::class)->setPermissionsTeamId($organizationId);

        // Get user's roles for their organization
        $roles = $user->roles()
            ->where('roles.organization_id', $organizationId)
            ->withCount('permissions')
            ->get();

        $result = [
            'organization_id' => (int) $organizationId,
            'roles' => $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'permissions_count' => $role->permissions_count,
                ];
            }),
            'total' => $roles->count(),
        ];

        return response()->json(
            AbstractResource::success($result)
        );
    }

    /**
     * Get my permissions
     *
     * Get all permissions available to the authenticated user in their organization.
     * This includes both direct permissions and permissions inherited from roles.
     * Requires authentication.
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": null,
     *   "data": {
     *     "organization_id": 1,
     *     "direct_permissions": [
     *       {
     *         "id": 10,
     *         "name": "article.publish",
     *         "guard_name": "api"
     *       }
     *     ],
     *     "role_permissions": [
     *       {
     *         "id": 1,
     *         "name": "user.view",
     *         "guard_name": "api",
     *         "via_role": "editor"
     *       },
     *       {
     *         "id": 2,
     *         "name": "user.edit",
     *         "guard_name": "api",
     *         "via_role": "editor"
     *       }
     *     ],
     *     "all_permissions": [
     *       "user.view",
     *       "user.edit",
     *       "article.publish"
     *     ],
     *     "total": 3
     *   }
     * }
     */
    public function myPermissions(Request $request): JsonResponse
    {
        $user = $request->user();

        // Super Admin has system-level access with all IAM permissions
        if ($user->is_super_admin) {
            $iamPermissions = \Spatie\Permission\Models\Permission::where('guard_name', 'api')
                ->whereNull('organization_id')
                ->where('name', 'like', 'iam.%')
                ->get();

            return response()->json(
                AbstractResource::success([
                    'is_super_admin' => true,
                    'direct_permissions' => $iamPermissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'guard_name' => $permission->guard_name,
                        ];
                    }),
                    'role_permissions' => [],
                    'all_permissions' => $iamPermissions->pluck('name'),
                    'total' => $iamPermissions->count(),
                    'message' => 'Super Admin has system-level access to all IAM permissions'
                ])
            );
        }

        // Get user's organization
        if (!$user->organization_id) {
            return response()->json(
                AbstractResource::error('User does not have an assigned organization'),
                403
            );
        }

        $organizationId = $user->organization_id;

        // Set the team context
        app(PermissionRegistrar::class)->setPermissionsTeamId($organizationId);

        // Get direct permissions
        $directPermissions = $user->permissions()
            ->where('permissions.organization_id', $organizationId)
            ->get();

        // Get permissions via roles
        $roles = $user->roles()
            ->where('roles.organization_id', $organizationId)
            ->with('permissions')
            ->get();

        $rolePermissions = collect();
        foreach ($roles as $role) {
            foreach ($role->permissions as $permission) {
                $rolePermissions->push([
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name,
                    'via_role' => $role->name,
                ]);
            }
        }

        // Get all unique permission names
        $allPermissionNames = $directPermissions->pluck('name')
            ->merge($rolePermissions->pluck('name'))
            ->unique()
            ->values();

        $result = [
            'organization_id' => (int) $organizationId,
            'direct_permissions' => $directPermissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name,
                ];
            }),
            'role_permissions' => $rolePermissions,
            'all_permissions' => $allPermissionNames,
            'total' => $allPermissionNames->count(),
        ];

        return response()->json(
            AbstractResource::success($result)
        );
    }
}
