<?php

namespace IamApi\Actions\Password;

use IamApi\Models\User;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use IamApi\Services\PasswordPolicyService;
use IamApi\Services\TokenService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ResetPasswordAction
{
    public function __construct(
        public PasswordPolicyService $passwordPolicyService,
        public TokenService $tokenService,
        public UserRepositoryInterface $userRepository
    ) {}

    /**
     * Execute the reset password action
     */
    public function execute(array $data): array
    {
        $resetToken = $data['reset_token'];
        $newPassword = $data['new_password'];

        // Rate limiting key based on token
        $rateLimitKey = 'password_reset:' . hash('sha256', $resetToken);

        // Check rate limiting (3 attempts per 15 minutes)
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return [
                'success' => false,
                'message' => 'Too many password reset attempts. Please try again later.',
                'code' => 429,
                'data' => [
                    'retry_after' => $seconds
                ]
            ];
        }

        try {
            // Verify reset token
            $tokenResult = $this->tokenService->verifyToken($resetToken);

            if (!$tokenResult['valid']) {
                RateLimiter::hit($rateLimitKey, 900);

                return [
                    'success' => false,
                    'message' => 'Invalid or expired reset token.',
                    'code' => 400,
                    'data' => [
                        'errors' => ['Invalid or expired reset token']
                    ]
                ];
            }

            // Check if the token is for password reset
            $payload = $tokenResult['payload'];
            if (!isset($payload['password_reset']) || !$payload['password_reset']) {
                RateLimiter::hit($rateLimitKey, 900);

                return [
                    'success' => false,
                    'message' => 'Invalid reset token.',
                    'code' => 400,
                    'data' => [
                        'errors' => ['Token is not valid for password reset']
                    ]
                ];
            }

            // Get user from token
            /** @var User $user */
            $user = $this->userRepository->find($payload['sub']);
            if (!$user) {
                RateLimiter::hit($rateLimitKey, 900);

                return [
                    'success' => false,
                    'message' => 'User not found.',
                    'code' => 404
                ];
            }

            // Validate new password against policy
            $policyResult = $this->passwordPolicyService->validatePassword($newPassword, $user);

            if (!$policyResult['valid']) {
                RateLimiter::hit($rateLimitKey, 900);

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
                RateLimiter::hit($rateLimitKey, 900);

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

            // Clear must_change_password flag if set
            $user->update([
                'must_change_password' => false
            ]);

            // Clear rate limit on successful reset
            RateLimiter::clear($rateLimitKey);

            // Invalidate the reset token by blocklisting it
            $this->tokenService->blacklistToken($resetToken);

            // Log successful password reset
            activity()
                ->performedOn($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'method' => 'password_reset_token'
                ])
                ->log('Password reset successfully');

            return [
                'success' => true,
                'message' => 'Password reset successfully',
                'data' => [
                    'password_changed_at' => $user->fresh()->password_changed_at->toISOString(),
                    'password_expires_at' => $user->fresh()->password_expires_at->toISOString(),
                    'must_change_password' => false,
                    'user' => $user->only(['id','name','email','status'])
                ]
            ];

        } catch (Exception $e) {
            Log::error('Reset password failed', [
                'error' => $e->getMessage(),
                'token_hash' => hash('sha256', $resetToken)
            ]);

            return [
                'success' => false,
                'message' => 'Failed to reset password. Please try again later.',
                'code' => 500
            ];
        }
    }
}
