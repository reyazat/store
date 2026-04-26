<?php

namespace IamApi\Http\Controllers\API;

use IamApi\Actions\Rbac\AssignPermissionsToRoleAction;
use IamApi\Actions\Rbac\AssignRoleToUserAction;
use IamApi\Actions\Rbac\CreateRoleAction;
use IamApi\Actions\Rbac\DeleteRoleAction;
use IamApi\Actions\Rbac\GetRoleAction;
use IamApi\Actions\Rbac\ListRolesAction;
use IamApi\Actions\Rbac\RevokePermissionFromRoleAction;
use IamApi\Actions\Rbac\RevokeRoleFromUserAction;
use IamApi\Actions\Rbac\UpdateRoleAction;
use IamApi\Http\Requests\Rbac\AssignPermissionsRequest;
use IamApi\Http\Requests\Rbac\CreateRoleRequest;
use IamApi\Http\Requests\Rbac\UpdateRoleRequest;
use IamApi\Http\Resources\AbstractResource;
use IamApi\Models\Organization;
use IamApi\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Spatie\Permission\Models\Role;

/**
 * @group RBAC - Roles
 *
 * APIs for managing roles within organizations (tenant-scoped).
 */
class RoleController extends Controller
{
    /**
     * List roles
     *
     * Get a paginated list of all roles for an organization.
     * Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": null,
     *   "data": {
     *     "roles": [
     *       {
     *         "id": 1,
     *         "name": "admin",
     *         "guard_name": "api",
     *         "permissions_count": 5,
     *         "users_count": 3,
     *         "created_at": "2025-10-01T12:00:00.000000Z",
     *         "updated_at": "2025-10-01T12:00:00.000000Z"
     *       }
     *     ],
     *     "total": 1
     *   }
     * }
     */
    public function index(Organization $org, ListRolesAction $action): JsonResponse
    {
        $result = $action->execute($org);

        return response()->json(
            AbstractResource::success($result)
        );
    }

    /**
     * Create role
     *
     * Create a new role for an organization.
     * Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     *
     * @bodyParam name string required The role name. Example: editor
     * @bodyParam guard_name string The guard name. Defaults to 'api'. Example: api
     * @bodyParam permissions array Optional array of permission names to assign. Example: ["user.view", "user.edit"]
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Role created successfully.",
     *   "data": {
     *     "id": 2,
     *     "name": "editor",
     *     "guard_name": "api",
     *     "organization_id": 1,
     *     "permissions": [],
     *     "created_at": "2025-10-01T12:00:00.000000Z",
     *     "updated_at": "2025-10-01T12:00:00.000000Z"
     *   }
     * }
     */
    public function store(Organization $org, CreateRoleRequest $request, CreateRoleAction $action): JsonResponse
    {
        $role = $action->execute($org, $request->validated());

        return response()->json(
            AbstractResource::success($role, 'Role created successfully.'),
            201
        );
    }

    /**
     * Get role details
     *
     * Get detailed information about a specific role.
     * Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam role integer required The role ID. Example: 2
     *
     * @response 200 {
     *   "success": true,
     *   "message": null,
     *   "data": {
     *     "id": 2,
     *     "name": "editor",
     *     "guard_name": "api",
     *     "organization_id": 1,
     *     "permissions": [
     *       {
     *         "id": 1,
     *         "name": "user.view",
     *         "guard_name": "api"
     *       }
     *     ],
     *     "users": [],
     *     "created_at": "2025-10-01T12:00:00.000000Z",
     *     "updated_at": "2025-10-01T12:00:00.000000Z"
     *   }
     * }
     */
    public function show(Organization $org, int $role, GetRoleAction $action): JsonResponse
    {
        $roleModel = $action->execute($org, $role);

        return response()->json(
            AbstractResource::success($roleModel)
        );
    }

    /**
     * Update role
     *
     * Update a role's name or permissions.
     * Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam role integer required The role ID. Example: 2
     *
     * @bodyParam name string The new role name. Example: senior-editor
     * @bodyParam permissions array Array of permission names to sync. Example: ["user.view", "user.edit", "user.delete"]
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Role updated successfully.",
     *   "data": {
     *     "id": 2,
     *     "name": "senior-editor",
     *     "guard_name": "api",
     *     "organization_id": 1,
     *     "permissions": [
     *       {
     *         "id": 1,
     *         "name": "user.view",
     *         "guard_name": "api"
     *       }
     *     ],
     *     "created_at": "2025-10-01T12:00:00.000000Z",
     *     "updated_at": "2025-10-01T12:05:00.000000Z"
     *   }
     * }
     */
    public function update(Organization $org, int $role, UpdateRoleRequest $request, UpdateRoleAction $action): JsonResponse
    {
        $roleModel = $action->execute($org, $role, $request->validated());

        return response()->json(
            AbstractResource::success($roleModel, 'Role updated successfully.')
        );
    }

    /**
     * Delete role
     *
     * Delete a role from an organization.
     * Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam role integer required The role ID. Example: 2
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Role deleted successfully.",
     *   "data": null
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Cannot delete role 'editor' because it is assigned to 3 user(s).",
     *   "errors": {
     *     "role": ["Cannot delete role 'editor' because it is assigned to 3 user(s)."]
     *   }
     * }
     */
    public function destroy(Organization $org, int $role, DeleteRoleAction $action): JsonResponse
    {
        $deleted = $action->execute($org, $role);

        if (!$deleted) {
            return response()->json(
                AbstractResource::error('Failed to delete role', $action->getErrors()),
                422
            );
        }

        return response()->json(
            AbstractResource::success(null, 'Role deleted successfully.')
        );
    }

    /**
     * Assign permissions to role
     *
     * Assign one or more permissions to a role.
     * Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam role integer required The role ID. Example: 2
     *
     * @bodyParam permissions array required Array of permission names. Example: ["user.create", "user.delete"]
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Permissions assigned to role successfully.",
     *   "data": {
     *     "id": 2,
     *     "name": "editor",
     *     "guard_name": "api",
     *     "permissions": [
     *       {
     *         "id": 1,
     *         "name": "user.view",
     *         "guard_name": "api"
     *       },
     *       {
     *         "id": 2,
     *         "name": "user.create",
     *         "guard_name": "api"
     *       }
     *     ]
     *   }
     * }
     */
    public function assignPermissions(
        Organization $org,
        int $role,
        AssignPermissionsRequest $request,
        AssignPermissionsToRoleAction $action
    ): JsonResponse {
        $roleModel = $action->execute($org, $role, $request->validated()['permissions']);

        return response()->json(
            AbstractResource::success($roleModel, 'Permissions assigned to role successfully.')
        );
    }

    /**
     * Revoke permission from role
     *
     * Remove a specific permission from a role.
     * Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam role integer required The role ID. Example: 2
     * @urlParam permission integer required The permission ID. Example: 3
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Permission revoked from role successfully.",
     *   "data": {
     *     "id": 2,
     *     "name": "editor",
     *     "guard_name": "api",
     *     "permissions": [
     *       {
     *         "id": 1,
     *         "name": "user.view",
     *         "guard_name": "api"
     *       }
     *     ]
     *   }
     * }
     */
    public function revokePermission(
        Organization $org,
        int $role,
        int $permission,
        RevokePermissionFromRoleAction $action
    ): JsonResponse {
        $roleModel = $action->execute($org, $role, $permission);

        return response()->json(
            AbstractResource::success($roleModel, 'Permission revoked from role successfully.')
        );
    }

    /**
     * Assign role to user
     *
     * Assign a role to a user within an organization.
     * Requires role.assign permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam role integer required The role ID. Example: 2
     * @urlParam user integer required The user ID. Example: 5
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Role assigned to user successfully.",
     *   "data": {
     *     "id": 5,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "roles": [
     *       {
     *         "id": 2,
     *         "name": "editor",
     *         "guard_name": "api"
     *       }
     *     ],
     *     "permissions": []
     *   }
     * }
     */
    public function assignToUser(
        Organization $org,
        Role $role,
        User $user,
        AssignRoleToUserAction $action
    ): JsonResponse {
        // Action now expects UUID instead of ID
        $userModel = $action->execute($org, $role->id, $user->uuid);

        if ($action->hasErrors()) {
            return response()->json(
                AbstractResource::error('Failed to assign role', $action->getErrors()),
                422
            );
        }

        return response()->json(
            AbstractResource::success($userModel, 'Role assigned to user successfully.')
        );
    }

    /**
     * Revoke role from user
     *
     * Remove a role from a user within an organization.
     * Requires role.assign permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam role integer required The role ID. Example: 2
     * @urlParam user integer required The user ID. Example: 5
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Role revoked from user successfully.",
     *   "data": {
     *     "id": 5,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "roles": [],
     *     "permissions": []
     *   }
     * }
     */
    public function revokeFromUser(
        Organization $org,
        Role $role,
        User $user,
        RevokeRoleFromUserAction $action
    ): JsonResponse {
        // Action now expects UUID instead of ID
        $userModel = $action->execute($org, $role->id, $user->uuid);

        return response()->json(
            AbstractResource::success($userModel, 'Role revoked from user successfully.')
        );
    }
}
