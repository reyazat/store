<?php

namespace IamApi\Http\Controllers\API;

use IamApi\Actions\Organizations\CreateOrganizationAction;
use IamApi\Actions\Organizations\DeleteOrganizationAction;
use IamApi\Actions\Organizations\GetOrganizationAction;
use IamApi\Actions\Organizations\ListOrganizationsAction;
use IamApi\Actions\Organizations\UpdateOrganizationAction;
use IamApi\Http\Requests\Organizations\CreateOrganizationRequest;
use IamApi\Http\Requests\Organizations\UpdateOrganizationRequest;
use IamApi\Http\Resources\AbstractResource;
use IamApi\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * @group Organization Management
 *
 * APIs for managing the organization and its settings.
 * Each user belongs to a single organization with role-based access control.
 */
class OrganizationController extends Controller
{
    public function __construct(
        protected ListOrganizationsAction $listAction,
        protected CreateOrganizationAction $createAction,
        protected GetOrganizationAction $getAction,
        protected UpdateOrganizationAction $updateAction,
        protected DeleteOrganizationAction $deleteAction
    ) {
        $this->middleware('auth:api');
    }

    /**
     * List organizations
     *
     * Get all organizations where the authenticated user is a member.
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": null,
     *   "data": {
     *     "organizations": [
     *       {
     *         "id": 1,
     *         "name": "Acme Corp",
     *         "created_at": "2025-09-30T10:00:00.000000Z",
     *         "updated_at": "2025-09-30T10:00:00.000000Z",
     *         "user_role": "owner",
     *         "members_count": 5
     *       }
     *     ]
     *   }
     * }
     */
    public function index(): JsonResponse
    {
        $result = $this->listAction->execute([
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
     * Create organization
     *
     * Create a new organization. The authenticated user becomes the owner.
     *
     * @authenticated
     *
     * @bodyParam name string required The organization name. Example: Acme Corp
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Organization created successfully",
     *   "data": {
     *     "organization": {
     *       "id": 1,
     *       "name": "Acme Corp",
     *       "created_at": "2025-09-30T10:00:00.000000Z",
     *       "user_role": "owner"
     *     }
     *   }
     * }
     */
    public function store(CreateOrganizationRequest $request): JsonResponse
    {
        $result = $this->createAction->execute($request->validated());

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
     * Get organization details
     *
     * Get detailed information about a specific organization.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": null,
     *   "data": {
     *     "organization": {
     *       "id": 1,
     *       "name": "Acme Corp",
     *       "created_at": "2025-09-30T10:00:00.000000Z",
     *       "updated_at": "2025-09-30T10:00:00.000000Z",
     *       "user_role": "owner",
     *       "statistics": {
     *         "total_members": 5,
     *         "owners_count": 1,
     *         "admins_count": 2,
     *         "domains_count": 1,
     *         "verified_domains_count": 1,
     *         "pending_invitations": 2
     *       }
     *     }
     *   }
     * }
     */
    public function show(Organization $org): JsonResponse
    {
        $result = $this->getAction->execute([
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
     * Update organization
     *
     * Update organization details. Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @bodyParam name strings The organization name. Example: Acme Corporation
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Organization updated successfully",
     *   "data": {
     *     "organization": {
     *       "id": 1,
     *       "name": "Acme Corporation",
     *       "updated_at": "2025-09-30T11:00:00.000000Z"
     *     }
     *   }
     * }
     */
    public function update(UpdateOrganizationRequest $request, Organization $org): JsonResponse
    {
        $result = $this->updateAction->execute(array_merge(
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
            AbstractResource::success($result['data'], $result['message'])
        );
    }

    /**
     * Delete organization
     *
     * Delete an organization. Only organization owners can perform this action.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Organization deleted successfully",
     *   "data": null
     * }
     */
    public function destroy(Organization $org): JsonResponse
    {
        $result = $this->deleteAction->execute([
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
}
