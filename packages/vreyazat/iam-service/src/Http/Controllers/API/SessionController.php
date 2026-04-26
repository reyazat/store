<?php

namespace IamApi\Http\Controllers\API;

use IamApi\Actions\Session\GetActiveSessionsAction;
use IamApi\Actions\Session\RevokeSessionAction;
use IamApi\Http\Requests\Session\RevokeSessionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * @group Session Management
 *
 * APIs for managing user sessions including listing active sessions and revoking specific sessions.
 * These endpoints help users monitor and control their active login sessions across different devices.
 */
class SessionController extends Controller
{
    public function __construct(
        private readonly GetActiveSessionsAction $getActiveSessionsAction,
        private readonly RevokeSessionAction $revokeSessionAction
    ) {
        $this->middleware('auth:api');
        $this->middleware('password.expiry');
    }

    /**
     * Get active sessions
     *
     * Retrieve a list of all active sessions (refresh tokens) for the authenticated user.
     * Shows device information, IP addresses, last used timestamps, and session identifiers.
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Active sessions retrieved successfully",
     *   "data": {
     *     "current_session": {
     *       "jti": "550e8400-e29b-41d4-a716-446655440000",
     *       "device_name": "Chrome on Windows",
     *       "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
     *       "ip": "192.168.1.100",
     *       "last_used_at": "2024-01-15T10:30:00Z",
     *       "created_at": "2024-01-15T08:00:00Z",
     *       "expires_at": "2024-02-15T08:00:00Z",
     *       "is_current": true
     *     },
     *     "other_sessions": [
     *       {
     *         "jti": "550e8400-e29b-41d4-a716-446655440001",
     *         "device_name": "Safari on iPhone",
     *         "user_agent": "Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)",
     *         "ip": "192.168.1.101",
     *         "last_used_at": "2024-01-14T15:20:00Z",
     *         "created_at": "2024-01-14T14:00:00Z",
     *         "expires_at": "2024-02-14T14:00:00Z",
     *         "is_current": false
     *       }
     *     ],
     *     "total_sessions": 2
     *   }
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "Unauthenticated",
     *   "data": null
     * }
     */
    public function index(): JsonResponse
    {
        $result = $this->getActiveSessionsAction->execute();

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data' => null
            ], $result['status_code'] ?? 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Active sessions retrieved successfully',
            'data' => $result['data']
        ]);
    }

    /**
     * Revoke a specific session
     *
     * Revoke (logout) a specific session by its JTI identifier.
     * This allows users to remotely log out from other devices or sessions.
     * The current session cannot be revoked using this endpoint.
     *
     * @authenticated
     *
     * @bodyParam jti string required The session identifier (JTI) to revoke. Example: 550e8400-e29b-41d4-a716-446655440001
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Session revoked successfully",
     *   "data": {
     *     "revoked_session": {
     *       "jti": "550e8400-e29b-41d4-a716-446655440001",
     *       "device_name": "Safari on iPhone",
     *       "revoked_at": "2024-01-15T10:35:00Z"
     *     }
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Cannot revoke current session. Use logout endpoint instead.",
     *   "data": null
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Session not found or already revoked",
     *   "data": null
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "data": {
     *     "jti": ["The jti field is required."]
     *   }
     * }
     */
    public function revoke(RevokeSessionRequest $request): JsonResponse
    {
        $result = $this->revokeSessionAction->execute($request->validated());

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'data' => $result['data'] ?? null
            ], $result['status_code'] ?? 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Session revoked successfully',
            'data' => $result['data']
        ]);
    }
}
