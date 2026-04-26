<?php

namespace IamApi\Http\Controllers\API;

use IamApi\Actions\Organizations\AddMemberAction;
use IamApi\Actions\Organizations\ListMembersAction;
use IamApi\Actions\Organizations\RemoveMemberAction;
use IamApi\Actions\Organizations\UpdateMemberRoleAction;
use IamApi\Http\Requests\Organizations\AddMemberRequest;
use IamApi\Http\Requests\Organizations\UpdateMemberRoleRequest;
use IamApi\Http\Resources\AbstractResource;
use IamApi\Models\Organization;
use IamApi\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * @group Organization Management
 *
 * APIs for managing organization members and their roles.
 */
class OrganizationMemberController extends Controller
{
    public function __construct(
        protected ListMembersAction $listAction,
        protected AddMemberAction $addAction,
        protected UpdateMemberRoleAction $updateAction,
        protected RemoveMemberAction $removeAction
    ) {
        $this->middleware('auth:api');
    }

    /**
     * List organization members
     *
     * Get all members of an organization with their roles.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
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
     *         "role": "owner",
     *         "joined_at": "2025-09-30T10:00:00.000000Z"
     *       }
     *     ],
     *     "total": 1
     *   }
     * }
     */
    public function index(Organization $org): JsonResponse
    {
        $result = $this->listAction->execute([
            'organization' => $org,
            'user' => Auth::user()
        ]);

        if (!$result['success']) {
            return response()->json(
                AbstractResource::error($result['message'], $result['data'] ?? null),
                $result['code'] ?? 400
            );
        }

        return response()->json(
            AbstractResource::success($result['data'], $result['message'])
        );
    }

    /**
     * Add member to organization
     *
     * Add a new member to the organization or assign an existing user.
     * Requires org. Manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @bodyParam user_id integer The user ID (required without email). Example: 5
     * @bodyParam email strings The user email (required without user_id). Example: newuser@example.com
     * @bodyParam role string required The member role (admin or member). Example: member
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
    public function store(AddMemberRequest $request, Organization $org): JsonResponse
    {
        $result = $this->addAction->execute(array_merge(
            $request->validated(),
            [
                'organization' => $org,
                'user' => Auth::user()
            ]
        ));

        if (!$result['success']) {
            return response()->json(
                AbstractResource::error($result['message'], $result['data'] ?? null),
                $result['code'] ?? 400
            );
        }

        return response()->json(
            AbstractResource::success($result['data'], $result['message']),
            201
        );
    }

    /**
     * Update a member role
     *
     * Change a member's role in the organization.
     * Requires org. Manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam user integer required The user ID. Example: 5
     * @bodyParam role string required The new role (owner, admin, or member). Example: admin
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Member role updated successfully",
     *   "data": {
     *     "member": {
     *       "id": 5,
     *       "name": "Jane Smith",
     *       "email": "jane@example.com",
     *       "role": "admin"
     *     }
     *   }
     * }
     */
    public function update(UpdateMemberRoleRequest $request, Organization $org, User $user): JsonResponse
    {
        $result = $this->updateAction->execute(array_merge(
            $request->validated(),
            [
                'organization' => $org,
                'user' => Auth::user(),
                'target_user_uuid' => $user->uuid  // Changed from target_user_id to target_user_uuid
            ]
        ));

        if (!$result['success']) {
            return response()->json(
                AbstractResource::error($result['message'], $result['data'] ?? null),
                $result['code'] ?? 400
            );
        }

        return response()->json(
            AbstractResource::success($result['data'], $result['message'])
        );
    }

    /**
     * Remove member from organization
     *
     * Remove a member from the organization.
     * Requires org. Manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam user integer required The user ID. Example: 5
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Member removed successfully",
     *   "data": null
     * }
     */
    public function destroy(Organization $org, User $user): JsonResponse
    {
        $result = $this->removeAction->execute([
            'organization' => $org,
            'user' => Auth::user(),
            'target_user_uuid' => $user->uuid  // Changed from target_user_id to target_user_uuid
        ]);

        if (!$result['success']) {
            return response()->json(
                AbstractResource::error($result['message'], $result['data'] ?? null),
                $result['code'] ?? 400
            );
        }

        return response()->json(
            AbstractResource::success($result['data'], $result['message'])
        );
    }
}
