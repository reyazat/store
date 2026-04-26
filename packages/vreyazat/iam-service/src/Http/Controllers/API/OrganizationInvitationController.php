<?php

namespace IamApi\Http\Controllers\API;

use IamApi\Actions\Organizations\AcceptInvitationAction;
use IamApi\Actions\Organizations\CancelInvitationAction;
use IamApi\Actions\Organizations\CreateInvitationAction;
use IamApi\Actions\Organizations\ListInvitationsAction;
use IamApi\Actions\Organizations\ResendInvitationAction;
use IamApi\Http\Requests\Organizations\CreateInvitationRequest;
use IamApi\Http\Resources\AbstractResource;
use IamApi\Models\Organization;
use IamApi\Models\OrganizationInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * @group Organization Management
 *
 * APIs for managing organization invitations.
 */
class OrganizationInvitationController extends Controller
{
    public function __construct(
        protected ListInvitationsAction $listAction,
        protected CreateInvitationAction $createAction,
        protected ResendInvitationAction $resendAction,
        protected CancelInvitationAction $cancelAction,
        protected AcceptInvitationAction $acceptAction
    ) {
        $this->middleware('auth:api');
    }

    /**
     * List organization invitations
     *
     * Get all pending invitations for an organization.
     * Requires org. Manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": null,
     *   "data": {
     *     "invitations": [
     *       {
     *         "id": 1,
     *         "email": "newuser@example.com",
     *         "role": "member",
     *         "status": "pending",
     *         "invited_by": {
     *           "id": 1,
     *           "name": "John Doe",
     *           "email": "john@example.com"
     *         },
     *         "created_at": "2025-09-30T10:00:00.000000Z",
     *         "expires_at": "2025-10-02T10:00:00.000000Z"
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
     * Create invitation
     *
     * Invite a user to join the organization.
     * Requires org. Manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @bodyParam email string required The invitee's email address. Example: newuser@example.com
     * @bodyParam role string required The role to assign (admin or member). Example: member
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Invitation sent successfully",
     *   "data": {
     *     "invitation": {
     *       "id": 1,
     *       "email": "newuser@example.com",
     *       "role": "member",
     *       "status": "pending",
     *       "expires_at": "2025-10-02T10:00:00.000000Z",
     *       "token": "abc123xyz..."
     *     }
     *   }
     * }
     */
    public function store(CreateInvitationRequest $request, Organization $org): JsonResponse
    {
        $result = $this->createAction->execute(array_merge(
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
     * Resend invitation
     *
     * Resend an invitation email and extend its expiration.
     * Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam invite integer required The invitation ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Invitation resent successfully",
     *   "data": {
     *     "invitation": {
     *       "id": 1,
     *       "email": "newuser@example.com",
     *       "expires_at": "2025-10-03T10:00:00.000000Z"
     *     }
     *   }
     * }
     */
    public function resend(Organization $org, OrganizationInvitation $invite): JsonResponse
    {
        // Verify invitation belongs to organization
        if ($invite->organization_id !== $org->id) {
            return response()->json(
                AbstractResource::error('Invitation not found'),
                404
            );
        }

        $result = $this->resendAction->execute([
            'organization' => $org,
            'user' => Auth::user(),
            'invitation_id' => $invite->id
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
     * Cancel invitation
     *
     * Cancel a pending invitation.
     * Requires org.manage permission.
     *
     * @authenticated
     *
     * @urlParam org integer required The organization ID. Example: 1
     * @urlParam invite integer required The invitation ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Invitation cancelled successfully",
     *   "data": null
     * }
     */
    public function destroy(Organization $org, OrganizationInvitation $invite): JsonResponse
    {
        // Verify invitation belongs to organization
        if ($invite->organization_id !== $org->id) {
            return response()->json(
                AbstractResource::error('Invitation not found'),
                404
            );
        }

        $result = $this->cancelAction->execute([
            'organization' => $org,
            'user' => Auth::user(),
            'invitation_id' => $invite->id
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
     * Accept invitation
     *
     * Accept an organization invitation using the token.
     * This is a public endpoint that requires authentication.
     *
     * @authenticated
     *
     * @urlParam token string required The invitation token. Example: abc123xyz...
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Invitation accepted successfully",
     *   "data": null
     * }
     */
    public function accept(string $token): JsonResponse
    {
        $result = $this->acceptAction->execute([
            'token' => $token
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
