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
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * @group Authentication
 *
 * APIs for user authentication including login, logout, token refresh, and profile retrieval.
 * These endpoints handle JWT-based authentication with refresh token support.
 * Uses HttpOnly cookies for secure token storage with Double Submit Cookie pattern for CSRF protection.
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
     * Authenticate a user with their email and password. Returns JWT access token and refresh token
     * in HttpOnly cookies for enhanced security. Also sets a CSRF token cookie for Double Submit pattern.
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
     *     "csrf_token": "abc123...",
     *     "expires_in": 1800
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
            // For MFA, we still return temp token in body (shorter lived)
            return response()->json(
                AbstractResource::success($result['data'], $result['message'])
            );
        }

        // Load user organization (if exists — Super Admin doesn't have one)
        $user = $result['data']['user'];
        if ($user->organization_id) {
            $user->load('organization');
        }

        // Return tokens in response body (Bearer token strategy)
        return response()->json(
            AbstractResource::success([
                'user'   => $user,
                'tokens' => $result['data']['tokens'],
            ], $result['message'])
        );
    }

    /**
     * Refresh JWT token
     *
     * Refresh an expired or near-expired JWT access token using a valid refresh token
     * from HttpOnly cookie. Returns new tokens in cookies.
     *
     * @unauthenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Token refreshed successfully",
     *   "data": {
     *     "csrf_token": "abc123...",
     *     "expires_in": 1800
     *   }
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "Invalid or expired refresh token",
     *   "data": null
     * }
     */
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        // Accept refresh_token from request body (Bearer token strategy)
        $refreshToken = $request->input('refresh_token');

        if (!$refreshToken) {
            return response()->json(
                AbstractResource::error('Refresh token not found', null),
                401
            );
        }

        $result = $this->refreshAction->execute(['refresh_token' => $refreshToken]);

        if (!$result['success']) {
            return response()->json(
                AbstractResource::error($result['message'], $result['data'] ?? null),
                401
            );
        }

        // Return tokens in response body (Bearer token strategy)
        return response()->json(
            AbstractResource::success($result['data']['tokens'], $result['message'])
        );
    }

    /**
     * Logout user
     *
     * Logout the authenticated user by invalidating their JWT tokens and refresh tokens.
     * Clears all authentication cookies.
     *
     * @authenticated
     *
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
     */
    public function logout(LogoutRequest $request): JsonResponse
    {
        $result = $this->logoutAction->execute($request->validated());

        if (!$result['success']) {
            return response()->json(
                AbstractResource::error($result['message'], $result['data'] ?? null),
                $result['code'] ?? 400
            );
        }

        return response()->json(
            AbstractResource::success(null, $result['message'])
        );
    }

    /**
     * Get authenticated user profile
     *
     * Retrieve the currently authenticated user's profile information.
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": "User retrieved successfully",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "user@example.com",
     *       "roles": ["admin"],
     *       "permissions": ["users.manage"]
     *     }
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
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(
                AbstractResource::error('Unauthenticated', null),
                401
            );
        }

        return response()->json(
            AbstractResource::success(
                ['user' => $user],
                'User retrieved successfully'
            )
        );
    }
}
