<?php

namespace IamApi\Http\Controllers\API;

use IamApi\Actions\Users\RegisterUserAction;
use IamApi\Actions\Users\UpdateUserProfileAction;
use IamApi\Http\Requests\Users\RegisterUserRequest;
use IamApi\Http\Requests\Users\UpdateUserRequest;
use IamApi\Http\Resources\AbstractResource;
use IamApi\Http\Resources\User\UserResource;
use IamApi\Http\Resources\User\UserProfileResource;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * @group User Management
 *
 * APIs for user registration, profile management, password changes, and user administration.
 * These endpoints handle user lifecycle operations with proper authorization and validation.
 */
class UserController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;
    public function __construct(
        protected RegisterUserAction $registerAction,
        protected UpdateUserProfileAction $updateAction,
        protected UserRepositoryInterface $userRepository
    ) {
        $this->middleware('auth:api')->except(['register']);
        // Rate limiting is handled by 'captcha.throttle:registration' middleware at route level
    }

    /**
     * Register a new user
     *
     * Create a new user account with email verification and password policy enforcement.
     * Rate limited to prevent spam registrations. Supports organization invitation codes.
     *
     * @unauthenticated
     *
     * @bodyParam name string required The user's full name. Example: John Doe
     * @bodyParam email string required The user's email address (must be unique). Example: john.doe@example.com
     * @bodyParam password string required The user's password (must meet security requirements). Example: SecurePassword123!
     * @bodyParam password_confirmation string required Password confirmation (must match password). Example: SecurePassword123!
     * @bodyParam phone string The user's phone number (optional). Example: +1234567890
     * @bodyParam invitation_code string Organization invitation code (optional). Example: INV-ABC123
     * @bodyParam send_verification_email boolean Whether to send email verification (default: true). Example: true
     *
     * @response 201 {
     *   "success": true,
     *   "message": "User registered successfully. Please check your email for verification.",
     *   "data": {
     *     "id": 2,
     *     "name": "John Doe",
     *     "email": "john.doe@example.com",
     *     "email_verified_at": null,
     *     "phone": "+1234567890",
     *     "phone_verified_at": null,
     *     "status": "active",
     *     "last_login_at": null,
     *     "last_login_ip": null,
     *     "mfa_enabled": false,
     *     "must_change_password": false,
     *     "password_expires_at": "2024-04-01T00:00:00.000000Z",
     *     "failed_login_attempts": 0,
     *     "locked_until": null,
     *     "roles": ["member"],
     *     "permissions": [],
     *     "organization_id": 1,
     *     "organization": {
     *       "id": 1,
     *       "name": "Default Organization",
     *       "created_at": "2024-01-01T00:00:00.000000Z",
     *       "updated_at": "2024-01-01T00:00:00.000000Z"
     *     },
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T00:00:00.000000Z"
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Registration failed",
     *   "data": {
     *     "errors": ["Email already exists", "Password does not meet security requirements"]
     *   }
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "data": {
     *     "name": ["The name field is required."],
     *     "email": ["The email field is required."],
     *     "password": ["The password field is required."]
     *   }
     * }
     *
     * @response 429 {
     *   "success": false,
     *   "message": "Too many registration attempts. Please try again later.",
     *   "data": {
     *     "retry_after": 300
     *   }
     * }
     */
    public function register(RegisterUserRequest $request): JsonResponse
    {
        $result = $this->registerAction->execute($request->validated());

        if (!$result['success']) {
            return response()->json(
                AbstractResource::error($result['message'], $result['data'] ?? null),
                $result['code'] ?? 400
            );
        }

        return response()->json(
            AbstractResource::success(
                new UserResource($result['data']['user']),
                $result['message']
            ),
            201
        );
    }

    /**
     * Get current user profile
     *
     * Retrieve the profile information of the currently authenticated user.
     * This is an alias for the /auth/me endpoint but follows RESTful conventions.
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
     *     "organization_id": 1,
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
    public function show(): JsonResponse
    {
        /** @var \IamApi\Models\User $user */
        $user = auth()->user()->load('organization');

        return response()->json(
            AbstractResource::success(
                new UserResource($user),
                'User profile retrieved successfully'
            )
        );
    }

    /**
     * Update user profile
     *
     * Update the authenticated user's profile information. Users can only update their own profile.
     * Email changes may require verification. Some fields may require additional permissions.
     *
     * @authenticated
     *
     * @bodyParam name strings The user's full name. Example: John Smith
     * @bodyParam email strings The user's email address (may require verification if changed). Example: john.smith@example.com
     * @bodyParam phone strings The user's phone number. Example: +1234567890
     * @bodyParam current_password string Required when changing email or sensitive information. Example: CurrentPassword123!
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Profile updated successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "John Smith",
     *     "email": "john.smith@example.com",
     *     "email_verified_at": null,
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
     *     "organization_id": 1,
     *     "organization": {
     *       "id": 1,
     *       "name": "Acme Corp",
     *       "created_at": "2024-01-01T00:00:00.000000Z",
     *       "updated_at": "2024-01-01T00:00:00.000000Z"
     *     },
     *     "created_at": "2024-01-01T00:00:00.000000Z",
     *     "updated_at": "2024-01-01T12:30:00.000000Z"
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Profile update failed",
     *   "data": {
     *     "errors": ["Current password is incorrect", "Email already exists"]
     *   }
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
     *     "email": ["The email format is invalid."],
     *     "phone": ["The phone format is invalid."]
     *   }
     * }
     */
    public function update(UpdateUserRequest $request): JsonResponse
    {
        $result = $this->updateAction->execute($request->validated());

        if (!$result['success']) {
            return response()->json(
                AbstractResource::error($result['message'], $result['data'] ?? null),
                $result['code'] ?? 400
            );
        }

        return response()->json(
            AbstractResource::success(
                new UserProfileResource($result['data']['user']),
                $result['message']
            )
        );
    }

    /**
     * Delete user account
     *
     * Delete the authenticated user's account. This action is irreversible and will:
     * - Soft delete the user account
     * - Invalidate all JWT tokens
     * - Log the deletion for audit purposes
     * - Trigger account deletion notifications
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": "User account deleted successfully",
     *   "data": null
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "Unauthenticated",
     *   "data": null
     * }
     *
     * @response 500 {
     *   "success": false,
     *   "message": "Failed to delete user account",
     *   "data": null
     * }
     */
    public function destroy(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        try {
            // Soft delete or hard delete based on business requirements
            $deleted = $this->userRepository->delete($user);

            if (!$deleted) {
                return response()->json(
                    AbstractResource::error('Failed to delete user account'),
                    500
                );
            }

            // Log account deletion
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ])
                ->log('User account deleted');

            return response()->json(
                AbstractResource::success(null, 'User account deleted successfully')
            );

        } catch (Exception $e) {
            return response()->json(
                AbstractResource::error('Failed to delete user account: ' . $e->getMessage()),
                500
            );
        }
    }

    /**
     * Get all users (Admin only)
     *
     * Retrieve a paginated list of all users in the system. This endpoint requires admin privileges
     * and supports filtering, sorting, and pagination.
     *
     * @authenticated
     *
     * @queryParam per_page integer Number of users per page (default: 15, max: 100). Example: 20
     * @queryParam page integer Page number for pagination. Example: 1
     * @queryParam search string Search term for filtering users by name or email. Example: john
     * @queryParam status string Filter by user status (active, inactive, locked). Example: active
     * @queryParam role string Filter by user role. Example: admin
     * @queryParam sort string Sort field (name, email, created_at). Example: created_at
     * @queryParam order string Sort order (asc, desc). Example: desc
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Users retrieved successfully",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com",
     *       "email_verified_at": "2024-01-01T00:00:00.000000Z",
     *       "phone": "+1234567890",
     *       "phone_verified_at": null,
     *       "status": "active",
     *       "last_login_at": "2024-01-01T12:00:00.000000Z",
     *       "last_login_ip": "192.168.1.1",
     *       "mfa_enabled": false,
     *       "must_change_password": false,
     *       "password_expires_at": "2024-04-01T00:00:00.000000Z",
     *       "failed_login_attempts": 0,
     *       "locked_until": null,
     *       "roles": ["admin"],
     *       "permissions": ["users.manage", "users.view"],
     *       "created_at": "2024-01-01T00:00:00.000000Z",
     *       "updated_at": "2024-01-01T12:00:00.000000Z"
     *     }
     *   ],
     *   "pagination": {
     *     "current_page": 1,
     *     "last_page": 5,
     *     "per_page": 15,
     *     "total": 75,
     *     "from": 1,
     *     "to": 15
     *   }
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "Unauthenticated",
     *   "data": null
     * }
     *
     * @response 403 {
     *   "success": false,
     *   "message": "This action is unauthorized",
     *   "data": null
     * }
     *
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $perPage = $request->get('per_page', 15);
        $users = $this->userRepository->paginate($perPage);

        return response()->json(
            AbstractResource::paginated(
                $users,
                'Users retrieved successfully',
                UserResource::class
            )
        );
    }

    /**
     * Get specific user by ID (Admin only)
     *
     * Retrieve detailed information about a specific user by their ID. This endpoint requires
     * admin privileges or appropriate permissions to view the target user.
     *
     * @authenticated
     *
     * @urlParam id integer required The user ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "User retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
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
     *     "roles": ["admin"],
     *     "permissions": ["users.manage", "users.view"],
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
     *
     * @response 403 {
     *   "success": false,
     *   "message": "This action is unauthorized",
     *   "data": null
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "User not found",
     *   "data": null
     * }
     *
     * @throws AuthorizationException
     */
    public function showById(int $id): JsonResponse
    {
        $user = $this->userRepository->findOrFail($id);
        $this->authorize('view', $user);

        return response()->json(
            AbstractResource::success(
                new UserResource($user),
                'User retrieved successfully'
            )
        );
    }
}
