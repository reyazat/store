<?php

namespace IamApi\Actions\Password;

use IamApi\Models\User;
use IamApi\Services\PasswordPolicyService;
use Exception;
use Illuminate\Support\Facades\Log;

class ForcePasswordChangeAction
{
    public function __construct(
        public PasswordPolicyService $passwordPolicyService
    ) {}

    /**
     * Execute the force password change action
     */
    public function execute(array $data): array
    {
        /** @var User $user */
        $user = $data['user'];
        $newPassword = $data['new_password'];

        try {
            // Check if user must change password
            if (!$user->must_change_password && !$this->isPasswordExpired($user)) {
                return [
                    'success' => false,
                    'message' => 'Password change is not required for this account.',
                    'code' => 400
                ];
            }

            // Determine the reason for forced change
            $forceChangeReason = $this->getForceChangeReason($user);

            // Validate new password against policy
            $policyResult = $this->passwordPolicyService->validatePassword($newPassword, $user);

            if (!$policyResult['valid']) {
                return [
                    'success' => false,
                    'message' => 'Password does not meet security requirements.',
                    'code' => 400,
                    'data' => [
                        'errors' => $policyResult['errors']
                    ]
                ];
            }

            // Check password history (only if not first login)
            if ($forceChangeReason !== 'first_login') {
                $historyResult = $this->passwordPolicyService->checkPasswordHistory($user, $newPassword);

                if (!$historyResult['allowed']) {
                    return [
                        'success' => false,
                        'message' => 'Password cannot be reused.',
                        'code' => 400,
                        'data' => [
                            'errors' => ['New password cannot be one of your last ' . config('iam.password.history_count', 5) . ' passwords']
                        ]
                    ];
                }
            }

            // Update password
            $result = $this->passwordPolicyService->updatePassword($user, $newPassword);

            if (!$result['success']) {
                return $result;
            }

            // Clear must_change_password flag
            $user->update([
                'must_change_password' => false
            ]);

            // Log forced password change
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'force_change_reason' => $forceChangeReason,
                    'password_strength' => $policyResult['strength'] ?? 'unknown'
                ])
                ->log('Forced password change completed');

            return [
                'success' => true,
                'message' => 'Password changed successfully',
                'data' => [
                    'password_changed_at' => $user->fresh()->password_changed_at->toISOString(),
                    'password_expires_at' => $user->fresh()->password_expires_at->toISOString(),
                    'must_change_password' => false,
                    'force_change_reason' => $forceChangeReason
                ]
            ];

        } catch (Exception $e) {
            Log::error('Force password change failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to change password. Please try again later.',
                'code' => 500
            ];
        }
    }

    /**
     * Check if the password is expired
     */
    private function isPasswordExpired(User $user): bool
    {
        return $user->password_expires_at && $user->password_expires_at->isPast();
    }

    /**
     * Get the reason for forced password change
     */
    private function getForceChangeReason(User $user): string
    {
        // Check if this is the first login (no password_changed_at)
        if (!$user->password_changed_at) {
            return 'first_login';
        }

        // Check if the password is expired
        if ($this->isPasswordExpired($user)) {
            return 'password_expired';
        }

        // Check if admin forced change
        if ($user->must_change_password) {
            return 'admin_required';
        }

        return 'unknown';
    }
}
