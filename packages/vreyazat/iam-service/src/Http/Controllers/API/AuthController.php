<?php

namespace IamApi\Http\Controllers\API;

use IamApi\Actions\Auth\LoginWithPasswordAction;
use IamApi\Actions\Auth\LogoutAction;
use IamApi\Actions\Auth\RefreshTokenAction;
use IamApi\Http\Requests\Auth\LoginRequest;
use IamApi\Http\Requests\Auth\LogoutRequest;
use IamApi\Http\Requests\Auth\RefreshTokenRequest;
use IamApi\Http\Resources\AbstractResource;
use IamApi\Http\Resources\Auth\AuthResource;
use IamApi\Http\Resources\Auth\TokenResource;
use IamApi\Http\Resources\User\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * @group Authentication
 *
 * APIs for user authentication including login, logout, token refresh, and profile retrieval.
 * These endpoints handle JWT-based authentication with refresh token support.
 */
class AuthController extends Controller
{
    public function __construct(
        protected LoginWithPasswordAction $loginAction,
        protected LogoutAction $logoutAction,
        protected RefreshTokenAction $refreshAction
    ) {
        $this->middleware('auth:api')->except(['login', 'refresh']);
        // Rate limiting is handled by 'captcha.throttle:login' middleware at route level
    }

    /**
     * Login with email and password
     *
     * Authenticate a user with their email and password. Returns JWT access token and refresh token.
     * Rate limited to prevent brute force attacks. May require CAPTCHA after multiple failed attempts.
     *
     * @unauthenticated
     *
     * @bodyParam email string required The user's email address. Example: user@example.com
     * @bodyParam password string required The user's password. Example: SecurePassword123!
     * @bodyParam device_name string The name of the device being used. Example: Chrome on Windows
     * @bodyParam captcha string The CAPTCHA value (required after multiple failed attempts). Example: abc123
     * @bodyParam captcha_key string The CAPTCHA key received from /api/v1/captcha endpoint. Example: key_here
     * @bodyParam remember_me boolean Whether to extend the refresh token lifetime. Example: true
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Login successful",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "user@example.com",
     *       "email_verified_at": "2024-01-01T00:00:00.000000Z",
     *       "phone": "+1234567890",
     *       "phone_verified_at": null,
     *       "status": "active",
     *       "last_login_at": "2024-01-01T12:00:00.000000Z",
     *       "mfa_enabled": false,
     *       "must_change_password": false,
     *       "password_expires_at": "2024-04-01T00:00:00.000000Z",
     *       "is_super_admin": false,
     *       "is_locked": false,
     *       "locked_until": null,
     *       "organization": {
     *         "id": 1,
     *         "name": "Acme Corp",
     *         "created_at": "2024-01-01T00:00:00.000000Z",
     *         "updated_at": "2024-01-01T00:00:00.000000Z"
     *       },
     *       "created_at": "2024-01-01T00:00:00.000000Z",
     *       "updated_at": "2024-01-01T12:00:00.000000Z"
     *     },
     *     "tokens": {
     *       "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
     *       "refresh_token": "def50200e8d4f5c8a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p7q8r9s0t1u2v3w4x5y6z7",
     *       "token_type": "bearer",
     *       "expires_in": 900
     *     }
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Invalid credentials",
     *   "data": null
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "data": {
     *     "email": ["The email field is required."],
     *     "password": ["The password field is required."]
     *   }
     * }
     *
     * @response 423 {
     *   "success": false,
     *   "message": "Account is locked due to multiple failed login attempts",
     *   "data": {
     *     "locked_until": "2024-01-01T13:00:00.000000Z"
     *   }
     * }
     *
     * @response 429 {
     *   "success": false,
     *   "message": "Too many login attempts. Please try again later.",
     *   "data": {
     *     "retry_after": 300
     *   }
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->loginAction->execute($request->validated());

        if (!$result['success']) {
            return response()->json(
                AbstractResource::error($result['message'], $result['data'] ?? null),
                $result['code'] ?? 400
            );
        }

        // Check if MFA or other action is required
        if (isset($result['data']['action_required'])) {
            return response()->json(
                AbstractResource::success($result['data'], $result['message'])
            );
        }

        return response()->json(
            AbstractResource::success(
                new AuthResource($result['data']),
                $result['message']
            )
        );
    }

    /**
     * Refresh JWT token
     *
     * Refresh an expired or near-expired JWT access token using a valid refresh token.
     * This endpoint allows users to maintain their session without re-entering credentials.
     *
     * @unauthenticated
     *
     * @bodyParam refresh_token string required The refresh token received during login. Example: def50200e8d4f5c8a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p7q8r9s0t1u2v3w4x5y6z7
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Token refreshed successfully",
     *   "data": {
     *     "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
     *     "refresh_token": "def50200f9e5g6d8h7i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2g3",
     *     "token_type": "bearer",
     *     "expires_in": 900
     *   }
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "Invalid or expired refresh token",
     *   "data": null
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "data": {
     *     "refresh_token": ["The refresh token field is required."]
     *   }
     * }
     */
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $result = $this->refreshAction->execute($request->validated());

        if (!$result['success']) {
            return response()->json(
                AbstractResource::error($result['message'], $result['data'] ?? null),
                401
            );
        }

        return response()->json(
            AbstractResource::success(
                new TokenResource($result['data']['tokens']),
                $result['message']
            )
        );
    }

    /**
     * Logout user
     *
     * Logout the authenticated user by invalidating their JWT tokens and refresh tokens.
     * This endpoint requires authentication and will blocklist the current tokens.
     *
     * @authenticated
     *
     * @bodyParam refresh_token string required The refresh token to invalidate. Example: def50200e8d4f5c8a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p7q8r9s0t1u2v3w4x5y6z7
     * @bodyParam logout_all_devices boolean Whether to log out from all devices. Example: false
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Logged out successfully",
     *   "data": null
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Logout failed",
     *   "data": null
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "Unauthenticated",
     *   "data": null
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "data": {
     *     "refresh_token": ["The refresh token field is required."]
     *   }
     * }
     */
    public function logout(LogoutRequest $request): JsonResponse
    {
        $result = $this->logoutAction->execute($request->validated());

        if (!$result['success']) {
            return response()->json(
                AbstractResource::error($result['message'], $result['data'] ?? null),
                400
            );
        }

        return response()->json(
            AbstractResource::success(null, $result['message'])
        );
    }

    /**
     * Get authenticated user profile
     *
     * Retrieve the profile information of the currently authenticated user.
     * This endpoint requires a valid JWT token in the Authorization header.
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": "User profile retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "user@example.com",
     *     "email_verified_at": "2024-01-01T00:00:00.000000Z",
     *     "phone": "+1234567890",
     *     "phone_verified_at": null,
     *     "status": "active",
     *     "last_login_at": "2024-01-01T12:00:00.000000Z",
     *     "last_login_ip": "192.168.1.1",
     *     "mfa_enabled": false,
     *     "must_change_password": false,
     *     "password_expires_at": "2024-04-01T00:00:00.000000Z",
     *     "failed_login_attempts": 0,
     *     "locked_until": null,
     *     "roles": ["member"],
     *     "permissions": ["users.view"],
     *     "organization": {
     *       "id": 1,
     *       "name": "Acme Corp",
     *       "created_at": "2024-01-01T00:00:00.000000Z",
     *       "updated_at": "2024-01-01T00:00:00.000000Z"
     *     },
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T12:00:00.000000Z"
     *   }
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "Unauthenticated",
     *   "data": null
     * }
     */
    public function me(): JsonResponse
    {
        $user = auth()->user();

        return response()->json(
            AbstractResource::success(
                new UserResource($user),
                'User profile retrieved successfully'
            )
        );
    }
}
