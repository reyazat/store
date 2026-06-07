<?php

namespace IamApi\Http\Controllers\API;

use IamApi\Actions\Password\ChangePasswordAction;
use IamApi\Actions\Password\CheckPasswordExpiryAction;
use IamApi\Actions\Password\ForcePasswordChangeAction;
use IamApi\Actions\Password\ForgotPasswordAction;
use IamApi\Actions\Password\ResetPasswordAction;
use IamApi\Http\Controllers\Controller;
use IamApi\Http\Requests\Users\ChangePasswordRequest;
use IamApi\Http\Requests\Users\ForcePasswordChangeRequest;
use IamApi\Http\Requests\Users\ForgotPasswordRequest;
use IamApi\Http\Requests\Users\ResetPasswordRequest;
use IamApi\Http\Resources\AbstractResource;
use IamApi\Services\AuthService;
use IamApi\Services\TokenService;
use Illuminate\Http\JsonResponse;

/**
 * @group Password Management
 *
 * APIs for password management including password changes, resets, force changes,
 * expiry checks, and password policy enforcement with history tracking.
 */
class PasswordController extends Controller
{
    public function __construct(
        private readonly ChangePasswordAction $changePasswordAction,
        private readonly ResetPasswordAction $resetPasswordAction,
        private readonly ForcePasswordChangeAction $forcePasswordChangeAction,
        private readonly CheckPasswordExpiryAction $checkPasswordExpiryAction,
        private readonly ForgotPasswordAction $forgotPasswordAction,
        private readonly TokenService $tokenService,
        private readonly AuthService $authService
    ) {}

    /**
     * Change password
     *
     * Change the authenticated user's password with current password verification.
     * Enforces password policy, history checks, and security requirements.
     *
     * @authenticated
     *
     * @bodyParam current_password string required The user's current password. Example: CurrentPassword123!
     * @bodyParam new_password string required The new password (must meet security requirements). Example: NewSecurePassword456!
     * @bodyParam new_password_confirmation string required Confirmation of the new password. Example: NewSecurePassword456!
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Password changed successfully",
     *   "data": {
     *     "password_changed_at": "2024-01-01T12:30:00.000000Z",
     *     "password_expires_at": "2024-04-01T12:30:00.000000Z",
     *     "must_change_password": false,
     *     "password_strength": "strong"
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Password change failed",
     *   "data": {
     *     "errors": [
     *       "Current password is incorrect",
     *       "New password cannot be one of your last 5 passwords",
     *       "Password must contain at least one uppercase letter"
     *     ]
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
     *     "current_password": ["The current password field is required."],
     *     "new_password": ["The new password must be at least 8 characters."],
     *     "new_password_confirmation": ["The new password confirmation does not match."]
     *   }
     * }
     */
    public function change(ChangePasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->changePasswordAction->execute([
            'user' => auth()->user(),
            'current_password' => $data['current_password'],
            'new_password' => $data['new_password'],
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
     * Forgot password
     *
     * Initiate a password reset process by sending OTP to user's email or phone.
     * This endpoint allows users to request a password reset when they forget their password.
     *
     * @unauthenticated
     *
     * @bodyParam identifier string required The email address or phone number to send reset OTP to. Example: user@example.com
     * @bodyParam channel string required The channel to send OTP through (email, sms). Example: email
     * @bodyParam captcha string The CAPTCHA value (required after multiple failed attempts). Example: abc123
     * @bodyParam captcha_key string The CAPTCHA key received from /api/v1/captcha endpoint. Example: key_here
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Password reset OTP sent successfully",
     *   "data": {
     *     "identifier": "user@example.com",
     *     "channel": "email",
     *     "expires_at": "2024-01-01T12:10:00.000000Z",
     *     "attempts_remaining": 5
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Failed to send password reset OTP",
     *   "data": {
     *     "error": "User not found with provided identifier"
     *   }
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "data": {
     *     "identifier": ["The identifier field is required."],
     *     "channel": ["The selected channel is invalid."]
     *   }
     * }
     *
     * @response 429 {
     *   "success": false,
     *   "message": "Too many password reset requests. Please try again later.",
     *   "data": {
     *     "retry_after": 300
     *   }
     * }
     */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $result = $this->forgotPasswordAction->execute($request->validated());

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
     * Reset password
     *
     * Reset password using a valid reset token obtained through OTP verification.
     * This endpoint is used after successful password reset OTP verification.
     *
     * @unauthenticated
     *
     * @bodyParam reset_token string required The password reset token from OTP verification. Example: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
     * @bodyParam new_password string required The new password (must meet security requirements). Example: NewSecurePassword456!
     * @bodyParam new_password_confirmation string required Confirmation of the new password. Example: NewSecurePassword456!
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Password reset successfully",
     *   "data": {
     *     "password_changed_at": "2024-01-01T12:30:00.000000Z",
     *     "password_expires_at": "2024-04-01T12:30:00.000000Z",
     *     "must_change_password": false,
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "user@example.com"
     *     }
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Password reset failed",
     *   "data": {
     *     "errors": [
     *       "Invalid or expired reset token",
     *       "New password cannot be one of your last 5 passwords"
     *     ]
     *   }
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "data": {
     *     "reset_token": ["The reset token field is required."],
     *     "new_password": ["The new password must be at least 8 characters."],
     *     "new_password_confirmation": ["The new password confirmation does not match."]
     *   }
     * }
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->resetPasswordAction->execute([
            'reset_token' => $data['reset_token'],
            'new_password' => $data['new_password']
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
     * Force password change
     *
     * Force a password change for the authenticated user. This is used when the user
     * must change their password (first login, after expiry, or admin requirement).
     *
     * @authenticated
     *
     * @bodyParam new_password string required The new password (must meet security requirements). Example: NewSecurePassword456!
     * @bodyParam new_password_confirmation string required Confirmation of the new password. Example: NewSecurePassword456!
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Password changed successfully",
     *   "data": {
     *     "password_changed_at": "2024-01-01T12:30:00.000000Z",
     *     "password_expires_at": "2024-04-01T12:30:00.000000Z",
     *     "must_change_password": false,
     *     "force_change_reason": "password_expired"
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Password change not required",
     *   "data": null
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "data": {
     *     "new_password": ["The new password must be at least 8 characters."],
     *     "new_password_confirmation": ["The new password confirmation does not match."]
     *   }
     * }
     */
    public function forceChange(ForcePasswordChangeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->forcePasswordChangeAction->execute([
            'user' => auth()->user(),
            'new_password' => $data['new_password']
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
     * Check password expiry
     *
     * Check the password expiry status for the authenticated user. Returns information
     * about password age, expiry date, and whether a change is required.
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Password expiry status retrieved",
     *   "data": {
     *     "password_expires_at": "2024-04-01T12:30:00.000000Z",
     *     "days_until_expiry": 45,
     *     "is_expired": false,
     *     "must_change_password": false,
     *     "password_age_days": 45,
     *     "warning_threshold_days": 7,
     *     "requires_change_soon": false
     *   }
     * }
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Password has expired and must be changed",
     *   "data": {
     *     "password_expires_at": "2024-01-01T12:30:00.000000Z",
     *     "days_until_expiry": -5,
     *     "is_expired": true,
     *     "must_change_password": true,
     *     "password_age_days": 95,
     *     "warning_threshold_days": 7,
     *     "requires_change_soon": true
     *   }
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "Unauthenticated",
     *   "data": null
     * }
     */
    public function checkExpiry(): JsonResponse
    {
        $result = $this->checkPasswordExpiryAction->execute([
            'user' => auth()->user()
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
     * Get password policy
     *
     * Get the current password policy requirements and rules.
     * This endpoint provides information about password complexity requirements.
     *
     * @unauthenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Password policy retrieved successfully",
     *   "data": {
     *     "min_length": 8,
     *     "max_length": 128,
     *     "require_uppercase": true,
     *     "require_lowercase": true,
     *     "require_numbers": true,
     *     "require_symbols": true,
     *     "history_count": 5,
     *     "expiry_days": 90,
     *     "warning_days": 7,
     *     "allowed_symbols": "!@#$%^&*()_+-=[]{}|;:,.<>?",
     *     "policy_description": "Password must be 8-128 characters with uppercase, lowercase, numbers, and symbols. Cannot reuse the last 5 passwords. Expires every 90 days."
     *   }
     * }
     */
    public function policy(): JsonResponse
    {
        $policy = config('iam.password', []);

        return response()->json(
            AbstractResource::success([
                'min_length' => $policy['min_length'] ?? 8,
                'max_length' => $policy['max_length'] ?? 128,
                'require_uppercase' => $policy['require_uppercase'] ?? true,
                'require_lowercase' => $policy['require_lowercase'] ?? true,
                'require_numbers' => $policy['require_numbers'] ?? true,
                'require_symbols' => $policy['require_symbols'] ?? true,
                'history_count' => $policy['history_count'] ?? 5,
                'expiry_days' => $policy['expiry_days'] ?? 90,
                'warning_days' => $policy['warning_days'] ?? 7,
                'allowed_symbols' => $policy['allowed_symbols'] ?? '!@#$%^&*()_+-=[]{}|;:,.<>?',
                'policy_description' => $this->getPolicyDescription($policy)
            ], 'Password policy retrieved successfully')
        );
    }

    /**
     * Get password policy description
     */
    private function getPolicyDescription(array $policy): string
    {
        $minLength = $policy['min_length'] ?? 8;
        $maxLength = $policy['max_length'] ?? 128;
        $historyCount = $policy['history_count'] ?? 5;
        $expiryDays = $policy['expiry_days'] ?? 90;

        $requirements = [];

        if ($policy['require_uppercase'] ?? true) {
            $requirements[] = 'uppercase letters';
        }
        if ($policy['require_lowercase'] ?? true) {
            $requirements[] = 'lowercase letters';
        }
        if ($policy['require_numbers'] ?? true) {
            $requirements[] = 'numbers';
        }
        if ($policy['require_symbols'] ?? true) {
            $requirements[] = 'symbols';
        }

        $requirementsText = implode(', ', $requirements);

        return "Password must be $minLength-$maxLength characters with $requirementsText. " .
               "Cannot reuse last $historyCount passwords. Expires every $expiryDays days.";
    }

    /**
     * Force password change during login flow (uses temp token issued at login)
     *
     * Called when the user logs in and `must_change_password` is true. The temp token
     * from the login response must be sent as Bearer token. After a successful change,
     * a full set of tokens is returned so the frontend can complete the login.
     *
     * @authenticated
     *
     * @bodyParam new_password string required New password. Example: NewSecure123!
     * @bodyParam new_password_confirmation string required Confirmation of new password. Example: NewSecure123!
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Password changed successfully",
     *   "data": {
     *     "user": {},
     *     "tokens": { "access_token": "...", "refresh_token": "...", "expires_in": 900 },
     *     "password_changed_at": "2024-01-01T12:30:00.000000Z"
     *   }
     * }
     */
    public function forceChangeLogin(ForcePasswordChangeRequest $request): JsonResponse
    {
        /** @var \IamApi\Models\User $user */
        $user = auth()->user();

        // Ensure the token carries the force_password_change claim
        $payload = auth()->payload();
        if (!$payload->get('force_password_change')) {
            return response()->json(
                AbstractResource::error('Invalid token for this operation.', null),
                403
            );
        }

        $data = $request->validated();
        $result = $this->forcePasswordChangeAction->execute([
            'user' => $user,
            'new_password' => $data['new_password'],
        ]);

        if (!$result['success']) {
            return response()->json(
                AbstractResource::error($result['message'], $result['data'] ?? null),
                $result['code'] ?? 400
            );
        }

        // Revoke the temp token and issue a real session token
        auth()->invalidate();

        $tokens = $this->tokenService->issue($user->fresh(), [], [
            'device_name' => $request->header('User-Agent', 'Unknown Device'),
        ]);

        if ($user->organization_id) {
            $user->load('organization');
        }

        $this->authService->touchLogin($user, $request->ip());

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('Completed login after forced password change');

        return response()->json(
            AbstractResource::success(
                array_merge($result['data'], [
                    'user' => $user->fresh(),
                    'tokens' => $tokens,
                ]),
                'Password changed and login successful'
            )
        );
    }
}
