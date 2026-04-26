<?php

namespace IamApi\Http\Controllers\API;

use IamApi\Actions\Rbac\AssignPermissionToUserAction;
use IamApi\Actions\Rbac\CreatePermissionAction;
use IamApi\Actions\Rbac\DeletePermissionAction;
use IamApi\Actions\Rbac\GetPermissionAction;
use IamApi\Actions\Rbac\ListPermissionsAction;
use IamApi\Actions\Rbac\RevokePermissionFromUserAction;
use IamApi\Actions\Rbac\UpdatePermissionAction;
use IamApi\Http\Requests\Rbac\CreatePermissionRequest;
use IamApi\Http\Requests\Rbac\UpdatePermissionRequest;
use IamApi\Http\Resources\AbstractResource;
use IamApi\Models\Organization;
use IamApi\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Spatie\Permission\Models\Permission;

/**
 * @group RBAC - Permissions
 *
 * APIs for managing permissions within organizations (tenant-scoped).
 */
class PermissionController extends Controller
{
    /**
     * List permissions
     *
     * Get a paginated list of all permissions for an organization.
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
     *     "permissions": [
     *       {
     *         "id": 1,
     *         "name": "user.view",
     *         "guard_name": "api",
     *         "roles_count": 2,
     *         "users_count": 1,
     *         "created_at": "2025-10-01T12:00:00.000000Z",
     *         "updated_at": "2025-10-01T12:00:00.000000Z"
     *       }
     *     ],
     *     "total": 1
     *   }
     * }
     */
    public function index(Organization $org, ListPermissionsAction $action): JsonResponse
    {
        $result = $action->execute($org);

        return response()->json(
            AbstractResource::success($result)
        );
    }

    /**
     * Create permission
     *
     * Create a new permission for an organization.
     * Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     *
     * @bodyParam name string required The permission name. Example: article.publish
     * @bodyParam guard_name string The guard name. Defaults to 'api'. Example: api
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Permission created successfully.",
     *   "data": {
     *     "id": 5,
     *     "name": "article.publish",
     *     "guard_name": "api",
     *     "organization_id": 1,
     *     "created_at": "2025-10-01T12:00:00.000000Z",
     *     "updated_at": "2025-10-01T12:00:00.000000Z"
     *   }
     * }
     */
    public function store(Organization $org, CreatePermissionRequest $request, CreatePermissionAction $action): JsonResponse
    {
        $permission = $action->execute($org, $request->validated());

        return response()->json(
            AbstractResource::success($permission, 'Permission created successfully.'),
            201
        );
    }

    /**
     * Get permission details
     *
     * Get detailed information about a specific permission.
     * Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam permission integer required The permission ID. Example: 5
     *
     * @response 200 {
     *   "success": true,
     *   "message": null,
     *   "data": {
     *     "id": 5,
     *     "name": "article.publish",
     *     "guard_name": "api",
     *     "organization_id": 1,
     *     "roles": [
     *       {
     *         "id": 2,
     *         "name": "editor",
     *         "guard_name": "api"
     *       }
     *     ],
     *     "users": [],
     *     "created_at": "2025-10-01T12:00:00.000000Z",
     *     "updated_at": "2025-10-01T12:00:00.000000Z"
     *   }
     * }
     */
    public function show(Organization $org, int $permission, GetPermissionAction $action): JsonResponse
    {
        $permissionModel = $action->execute($org, $permission);

        return response()->json(
            AbstractResource::success($permissionModel)
        );
    }

    /**
     * Update permission
     *
     * Update a permission's name.
     * Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam permission integer required The permission ID. Example: 5
     *
     * @bodyParam name string The new permission name. Example: article.publish-draft
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Permission updated successfully.",
     *   "data": {
     *     "id": 5,
     *     "name": "article.publish-draft",
     *     "guard_name": "api",
     *     "organization_id": 1,
     *     "created_at": "2025-10-01T12:00:00.000000Z",
     *     "updated_at": "2025-10-01T12:05:00.000000Z"
     *   }
     * }
     */
    public function update(
        Organization $org,
        int $permission,
        UpdatePermissionRequest $request,
        UpdatePermissionAction $action
    ): JsonResponse {
        $permissionModel = $action->execute($org, $permission, $request->validated());

        return response()->json(
            AbstractResource::success($permissionModel, 'Permission updated successfully.')
        );
    }

    /**
     * Delete permission
     *
     * Delete a permission from an organization.
     * Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam permission integer required The permission ID. Example: 5
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Permission deleted successfully.",
     *   "data": null
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Cannot delete permission 'article.publish' because it is assigned to 2 role(s) and 1 user(s).",
     *   "errors": {
     *     "permission": ["Cannot delete permission 'article.publish' because it is assigned to 2 role(s) and 1 user(s)."]
     *   }
     * }
     */
    public function destroy(Organization $org, int $permission, DeletePermissionAction $action): JsonResponse
    {
        $deleted = $action->execute($org, $permission);

        if (!$deleted) {
            return response()->json(
                AbstractResource::error('Failed to delete permission', $action->getErrors()),
                422
            );
        }

        return response()->json(
            AbstractResource::success(null, 'Permission deleted successfully.')
        );
    }

    /**
     * Assign permission to user
     *
     * Assign a permission directly to a user within an organization.
     * Requires permission.assign permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam permission integer required The permission ID. Example: 5
     * @urlParam user integer required The user ID. Example: 7
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Permission assigned to user successfully.",
     *   "data": {
     *     "id": 7,
     *     "name": "Jane Doe",
     *     "email": "jane@example.com",
     *     "roles": [],
     *     "permissions": [
     *       {
     *         "id": 5,
     *         "name": "article.publish",
     *         "guard_name": "api"
     *       }
     *     ]
     *   }
     * }
     */
    public function assignToUser(
        Organization $org,
        Permission $permission,
        User $user,
        AssignPermissionToUserAction $action
    ): JsonResponse {
        // Action now expects UUID instead of ID
        $userModel = $action->execute($org, $permission->id, $user->uuid);

        if ($action->hasErrors()) {
            return response()->json(
                AbstractResource::error('Failed to assign permission', $action->getErrors()),
                422
            );
        }

        return response()->json(
            AbstractResource::success($userModel, 'Permission assigned to user successfully.')
        );
    }

    /**
     * Revoke permission from user
     *
     * Remove a permission from a user within an organization.
     * Requires permission.assign permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam permission integer required The permission ID. Example: 5
     * @urlParam user integer required The user ID. Example: 7
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Permission revoked from user successfully.",
     *   "data": {
     *     "id": 7,
     *     "name": "Jane Doe",
     *     "email": "jane@example.com",
     *     "roles": [],
     *     "permissions": []
     *   }
     * }
     */
    public function revokeFromUser(
        Organization $org,
        Permission $permission,
        User $user,
        RevokePermissionFromUserAction $action
    ): JsonResponse {
        // Action now expects UUID instead of ID
        $userModel = $action->execute($org, $permission->id, $user->uuid);

        return response()->json(
            AbstractResource::success($userModel, 'Permission revoked from user successfully.')
        );
    }
}
