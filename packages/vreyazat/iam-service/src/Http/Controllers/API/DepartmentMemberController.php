<?php

namespace IamApi\Http\Controllers\API;

use IamApi\Http\Controllers\Controller;
use IamApi\Rules\NoHtml;
use IamApi\Models\Department;
use IamApi\Models\Organization;
use IamApi\Models\User;
use IamApi\Http\Resources\AbstractResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Department Management
 *
 * APIs for managing department members and their roles.
 */
class DepartmentMemberController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * List department members
     *
     * Get all members of a department.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam department integer required The department ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": null,
     *   "data": {
     *     "members": [
     *       {
     *         "id": 1,
     *         "name": "John Doe",
     *         "email": "john@example.com",
     *         "role": "manager",
     *         "joined_at": "2025-01-01T00:00:00.000000Z"
     *       }
     *     ],
     *     "total": 1
     *   }
     * }
     */
    public function index(Organization $org, Department $department): JsonResponse
    {
        // Verify department belongs to organization
        if ($department->organization_id !== $org->id) {
            return response()->json(
                AbstractResource::error('Department not found'),
                404
            );
        }

        // Verify user belongs to organization
        if (Auth::user()->organization_id !== $org->id && !Auth::user()->isSuperAdmin()) {
            return response()->json(
                AbstractResource::error('Unauthorized'),
                403
            );
        }

        $members = $department->users()
            ->get()
            ->map(fn($user) => [
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role,
                'joined_at' => $user->pivot->created_at->toISOString(),
            ]);

        return response()->json(
            AbstractResource::success([
                'members' => $members,
                'total' => count($members),
            ])
        );
    }

    /**
     * Add member to department
     *
     * Add a user to a department.
     * Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam department integer required The department ID. Example: 1
     * @bodyParam user_id integer required The user ID. Example: 5
     * @bodyParam role string The member role (manager, member). Example: member
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Member added successfully",
     *   "data": {
     *     "member": {
     *       "id": 5,
     *       "name": "Jane Smith",
     *       "email": "jane@example.com",
     *       "role": "member"
     *     }
     *   }
     * }
     */
    public function store(Request $request, Organization $org, Department $department): JsonResponse
    {
        // Verify department belongs to organization
        if ($department->organization_id !== $org->id) {
            return response()->json(
                AbstractResource::error('Department not found'),
                404
            );
        }

        // Verify user has permission to manage organization
        if (!Auth::user()->can('org.manage', $org) && !Auth::user()->isSuperAdmin()) {
            return response()->json(
                AbstractResource::error('Unauthorized'),
                403
            );
        }

        $validated = $request->validate([
            'user_uuid' => 'required|string|uuid|exists:users,uuid',
            'role' => ['sometimes', 'in:manager,member', new NoHtml()],
        ]);

        // Verify user belongs to same organization
        $user = User::where('uuid', $validated['user_uuid'])->first();
        if ($user->organization_id !== $org->id) {
            return response()->json(
                AbstractResource::error('User must belong to the same organization'),
                422
            );
        }

        // Check if user already in department
        if ($department->users()->where('user_id', $user->id)->exists()) {
            return response()->json(
                AbstractResource::error('User is already a member of this department'),
                422
            );
        }

        // Add user to department
        $department->users()->attach($user->id, [
            'role' => $validated['role'] ?? 'member',
        ]);

        return response()->json(
            AbstractResource::success([
                'member' => [
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $validated['role'] ?? 'member',
                ]
            ], 'Member added successfully'),
            201
        );
    }

    /**
     * Update member role
     *
     * Change a member's role in the department.
     * Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam department integer required The department ID. Example: 1
     * @urlParam user integer required The user ID. Example: 5
     * @bodyParam role string required The new role (manager, member). Example: manager
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Member role updated successfully",
     *   "data": {
     *     "member": {
     *       "id": 5,
     *       "name": "Jane Smith",
     *       "email": "jane@example.com",
     *       "role": "manager"
     *     }
     *   }
     * }
     */
    public function update(Request $request, Organization $org, Department $department, User $user): JsonResponse
    {
        // Verify department belongs to organization
        if ($department->organization_id !== $org->id) {
            return response()->json(
                AbstractResource::error('Department not found'),
                404
            );
        }

        // Verify user has permission to manage organization
        if (!Auth::user()->can('org.manage', $org) && !Auth::user()->isSuperAdmin()) {
            return response()->json(
                AbstractResource::error('Unauthorized'),
                403
            );
        }

        $validated = $request->validate([
            'role' => ['required', 'in:manager,member', new NoHtml()],
        ]);

        // Check if user is member of department
        if (!$department->users()->where('user_id', $user->id)->exists()) {
            return response()->json(
                AbstractResource::error('User is not a member of this department'),
                404
            );
        }

        // Update role
        $department->users()->updateExistingPivot($user->id, [
            'role' => $validated['role'],
        ]);

        return response()->json(
            AbstractResource::success([
                'member' => [
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $validated['role'],
                ]
            ], 'Member role updated successfully')
        );
    }

    /**
     * Remove member from department
     *
     * Remove a user from a department.
     * Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam department integer required The department ID. Example: 1
     * @urlParam user integer required The user ID. Example: 5
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Member removed successfully",
     *   "data": null
     * }
     */
    public function destroy(Organization $org, Department $department, User $user): JsonResponse
    {
        // Verify department belongs to organization
        if ($department->organization_id !== $org->id) {
            return response()->json(
                AbstractResource::error('Department not found'),
                404
            );
        }

        // Verify user has permission to manage organization
        if (!Auth::user()->can('org.manage', $org) && !Auth::user()->isSuperAdmin()) {
            return response()->json(
                AbstractResource::error('Unauthorized'),
                403
            );
        }

        // Check if user is member of department
        if (!$department->users()->where('user_id', $user->id)->exists()) {
            return response()->json(
                AbstractResource::error('User is not a member of this department'),
                404
            );
        }

        // Remove user from department
        $department->users()->detach($user->id);

        return response()->json(
            AbstractResource::success(null, 'Member removed successfully')
        );
    }
}
