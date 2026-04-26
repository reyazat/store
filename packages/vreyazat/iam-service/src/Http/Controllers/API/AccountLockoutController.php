<?php

namespace IamApi\Http\Controllers\API;

use IamApi\Actions\Account\CheckLockoutStatusAction;
use IamApi\Actions\Account\LockAccountAction;
use IamApi\Actions\Account\UnlockAccountAction;
use IamApi\Http\Controllers\Controller;
use IamApi\Http\Requests\Account\UnlockAccountRequest;
use IamApi\Http\Resources\AbstractResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use IamApi\Rules\NoHtml;

/**
 * @group Account Lockout
 *
 * APIs for managing account lockout status, including checking lockout status,
 * unlocking accounts, and handling failed login attempt tracking.
 */
class AccountLockoutController extends Controller
{
    public function __construct(
        private readonly CheckLockoutStatusAction $checkLockoutStatusAction,
        private readonly UnlockAccountAction $unlockAccountAction,
        private readonly LockAccountAction $lockAccountAction
    ) {}

    /**
     * Check account lockout status
     *
     * Check the current lockout status for the authenticated user's account,
     * including failed login attempts and lockout expiry information.
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Account lockout status retrieved",
     *   "data": {
     *     "is_locked": false,
     *     "locked_until": null,
     *     "failed_login_attempts": 2,
     *     "max_attempts": 10,
     *     "remaining_attempts": 8,
     *     "lockout_duration_minutes": 30,
     *     "last_failed_attempt": "2024-01-01T11:45:00.000000Z",
     *     "can_attempt_login": true
     *   }
     * }
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Account is currently locked",
     *   "data": {
     *     "is_locked": true,
     *     "locked_until": "2024-01-01T12:30:00.000000Z",
     *     "failed_login_attempts": 10,
     *     "max_attempts": 10,
     *     "remaining_attempts": 0,
     *     "lockout_duration_minutes": 30,
     *     "last_failed_attempt": "2024-01-01T12:00:00.000000Z",
     *     "can_attempt_login": false,
     *     "unlock_in_seconds": 1800
     *   }
     * }
     *
     * @response 401 {
     *   "success": false,
     *   "message": "Unauthenticated",
     *   "data": null
     * }
     */
    public function status(): JsonResponse
    {
        $result = $this->checkLockoutStatusAction->execute([
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
     * Unlock an account
     *
     * Unlock the authenticated user's account if it's currently locked.
     * This requires additional verification such as OTP or admin intervention.
     *
     * @authenticated
     *
     * @bodyParam unlock_method string required The method to unlock an account (otp, admin, security_question). Example: otp
     * @bodyParam verification_code string The OTP code if using OTP method. Example: ۱۲۳, ۴۵۶
     * @bodyParam admin_token string The admin unlock token if using admin method. Example: admin_unlock_token_here
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Account unlocked successfully",
     *   "data": {
     *     "unlocked_at": "2024-01-01T12:30:00.000000Z",
     *     "unlock_method": "otp",
     *     "failed_login_attempts": 0,
     *     "can_attempt_login": true
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Account is not locked",
     *   "data": null
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Invalid verification code",
     *   "data": {
     *     "attempts_remaining": 2
     *   }
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "data": {
     *     "unlock_method": ["The unlock method field is required."],
     *     "verification_code": ["The verification code is required when using OTP method."]
     *   }
     * }
     */
    public function unlock(UnlockAccountRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->unlockAccountAction->execute([
            'user' => auth()->user(),
            'unlock_method' => $data['unlock_method'],
            'verification_code' => $data['verification_code'],
            'admin_token' => $data['admin_token'],
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
     * Lock account (Admin only)
     *
     * Manually lock a user account. This endpoint is restricted to administrators
     * and is used for security or policy enforcement.
     *
     * @authenticated
     *
     * @bodyParam user_id integer required The ID of the user to lock. Example: 123
     * @bodyParam reason string required The reason for locking the account. Example: Suspicious activity detected
     * @bodyParam duration_minutes integer The lockout duration in minutes (default: 30). Example: 60
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Account locked successfully",
     *   "data": {
     *     "user_id": 123,
     *     "locked_at": "2024-01-01T12:30:00.000000Z",
     *     "locked_until": "2024-01-01T13:30:00.000000Z",
     *     "reason": "Suspicious activity detected",
     *     "locked_by": "admin@example.com"
     *   }
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Account is already locked",
     *   "data": null
     * }
     *
     * @response 403 {
     *   "success": false,
     *   "message": "Insufficient permissions to lock accounts",
     *   "data": null
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "User not found",
     *   "data": null
     * }
     */
    public function lock(Request $request): JsonResponse
    {
        // Note: This route is already protected by 'super.admin' middleware in routes/api.php
        // This check is redundant but kept for extra safety
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(
                AbstractResource::error('This action requires Super Admin privileges'),
                403
            );
        }

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['required', 'string', 'max:500', new NoHtml()],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'], // Max 1 week
        ]);

        $result = $this->lockAccountAction->execute([
            'admin_user' => auth()->user(),
            'user_id' => $data['user_id'],
            'reason' => $data['reason'],
            'duration_minutes' => $data['duration_minutes'] ?? 30
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
     * Get lockout policy
     *
     * Get the current account lockout policy settings and thresholds.
     * This endpoint provides information about lockout rules and limits.
     *
     * @unauthenticated
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Lockout policy retrieved successfully",
     *   "data": {
     *     "max_failed_attempts": 10,
     *     "lockout_duration_minutes": 30,
     *     "progressive_lockout": true,
     *     "lockout_thresholds": [
     *       {"attempts": 5, "duration_minutes": 5},
     *       {"attempts": 7, "duration_minutes": 15},
     *       {"attempts": 10, "duration_minutes": 30}
     *     ],
     *     "auto_unlock": true,
     *     "unlock_methods": ["otp", "admin", "time_based"],
     *     "policy_description": "Account will be locked after 10 failed login attempts for 30 minutes. Progressive lockout applies with shorter durations for fewer attempts."
     *   }
     * }
     */
    public function policy(): JsonResponse
    {
        $lockoutConfig = config('iam.authentication.lockout', []);

        return response()->json(
            AbstractResource::success([
                'max_failed_attempts' => $lockoutConfig['max_attempts'] ?? 10,
                'lockout_duration_minutes' => $lockoutConfig['duration_minutes'] ?? 30,
                'progressive_lockout' => $lockoutConfig['progressive'] ?? true,
                'lockout_thresholds' => $lockoutConfig['thresholds'] ?? [
                    ['attempts' => 5, 'duration_minutes' => 5],
                    ['attempts' => 7, 'duration_minutes' => 15],
                    ['attempts' => 10, 'duration_minutes' => 30]
                ],
                'auto_unlock' => $lockoutConfig['auto_unlock'] ?? true,
                'unlock_methods' => $lockoutConfig['unlock_methods'] ?? ['otp', 'admin', 'time_based'],
                'policy_description' => $this->getPolicyDescription($lockoutConfig)
            ], 'Lockout policy retrieved successfully')
        );
    }

    /**
     * Get lockout policy description
     */
    private function getPolicyDescription(array $config): string
    {
        $maxAttempts = $config['max_attempts'] ?? 10;
        $duration = $config['duration_minutes'] ?? 30;
        $progressive = $config['progressive'] ?? true;

        $description = "Account will be locked after $maxAttempts failed login attempts for $duration minutes.";

        if ($progressive) {
            $description .= " Progressive lockout applies with shorter durations for fewer attempts.";
        }

        return $description;
    }
}
