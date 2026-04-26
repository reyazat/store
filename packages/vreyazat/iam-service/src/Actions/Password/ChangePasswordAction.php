<?php

namespace IamApi\Actions\Password;

use IamApi\Models\User;
use IamApi\Services\PasswordPolicyService;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ChangePasswordAction
{
    public function __construct(
        public PasswordPolicyService $passwordPolicyService
    ) {}

    /**
     * Execute the change password action
     */
    public function execute(array $data): array
    {
        /** @var User $user */
        $user = $data['user'];
        $currentPassword = $data['current_password'];
        $newPassword = $data['new_password'];

        // Rate limiting key
        $rateLimitKey = 'password_change:' . $user->id;

        // Check rate limiting (3 attempts per hour)
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return [
                'success' => false,
                'message' => 'Too many password change attempts. Please try again later.',
                'code' => 429,
                'data' => [
                    'retry_after' => $seconds
                ]
            ];
        }

        try {
            // Verify the current password
            if (!Hash::check($currentPassword, $user->password)) {
                RateLimiter::hit($rateLimitKey, 3600);

                // Log a failed password change attempt
                activity()
                    ->performedOn($user)
                    ->causedBy($user)
                    ->withProperties([
                        'ip' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'reason' => 'incorrect_current_password'
                    ])
                    ->log('Failed password change attempt');

                return [
                    'success' => false,
                    'message' => 'Current password is incorrect.',
                    'code' => 400,
                    'data' => [
                        'errors' => ['Current password is incorrect']
                    ]
                ];
            }

            // Validate new password against policy
            $policyResult = $this->passwordPolicyService->validatePassword($newPassword, $user);

            if (!$policyResult['valid']) {
                RateLimiter::hit($rateLimitKey, 3600);

                return [
                    'success' => false,
                    'message' => 'Password does not meet security requirements.',
                    'code' => 400,
                    'data' => [
                        'errors' => $policyResult['errors']
                    ]
                ];
            }

            // Check password history
            $historyResult = $this->passwordPolicyService->checkPasswordHistory($user, $newPassword);

            if (!$historyResult['allowed']) {
                RateLimiter::hit($rateLimitKey, 3600);

                return [
                    'success' => false,
                    'message' => 'Password cannot be reused.',
                    'code' => 400,
                    'data' => [
                        'errors' => ['New password cannot be one of your last ' . config('iam.password.history_count', 5) . ' passwords']
                    ]
                ];
            }

            // Update password
            $result = $this->passwordPolicyService->updatePassword($user, $newPassword);

            if (!$result['success']) {
                return $result;
            }

            // Clear rate limit on successful change
            RateLimiter::clear($rateLimitKey);

            // Log successful password change
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'password_strength' => $policyResult['strength'] ?? 'unknown'
                ])
                ->log('Password changed successfully');

            /** @var User $freshUser */
            $freshUser = $user->fresh();

            return [
                'success' => true,
                'message' => 'Password changed successfully',
                'data' => [
                    'password_changed_at' => $freshUser->password_changed_at ? $freshUser->password_changed_at->toISOString() : null,
                    'password_expires_at' => $freshUser->password_expires_at ? $freshUser->password_expires_at->toISOString() : null,
                    'must_change_password' => false,
                    'password_strength' => $policyResult['strength'] ?? 'strong'
                ]
            ];

        } catch (Exception $e) {
            Log::error('Change password failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to change password. Please try again later.',
                'code' => 500,
                'data' => [
                    'error_detail' => $e->getMessage()
                ]
            ];
        }
    }
}
