<?php

namespace IamApi\Actions\Password;

use IamApi\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class CheckPasswordExpiryAction
{
    /**
     * Execute the check password expiry action
     */
    public function execute(array $data): array
    {
        /** @var User $user */
        $user = $data['user'];

        try {
            $now = Carbon::now();
            $passwordExpiresAt = $user->password_expires_at;
            $passwordChangedAt = $user->password_changed_at ?? $user->created_at;

            // Calculate password age
            $passwordAgeDays = $passwordChangedAt?->diffInDays($now);

            // Get policy settings
            $expiryDays = config('iam.password.expiry_days', 90);
            $warningDays = config('iam.password.warning_days', 7);

            // Calculate days until expiry
            $daysUntilExpiry = $passwordExpiresAt?->diffInDays($now);

            // Check if the password is expired
            $isExpired = $passwordExpiresAt && $passwordExpiresAt->isPast();

            // Check if the password requires change soon
            $requiresChangeSoon = $passwordExpiresAt && $passwordExpiresAt->diffInDays($now) <= $warningDays;

            // Determine a message based on status
            $message = $this->getStatusMessage($isExpired, $requiresChangeSoon, $daysUntilExpiry);

            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'password_expires_at' => $passwordExpiresAt?->toISOString(),
                    'password_changed_at' => $passwordChangedAt?->toISOString(),
                    'days_until_expiry' => $daysUntilExpiry,
                    'is_expired' => $isExpired,
                    'must_change_password' => $user->must_change_password || $isExpired,
                    'password_age_days' => $passwordAgeDays,
                    'warning_threshold_days' => $warningDays,
                    'requires_change_soon' => $requiresChangeSoon,
                    'policy' => [
                        'expiry_days' => $expiryDays,
                        'warning_days' => $warningDays
                    ]
                ]
            ];

        } catch (Exception $e) {
            Log::error('Check password expiry failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to check password expiry. Please try again later.',
                'code' => 500
            ];
        }
    }

    /**
     * Get a status message based on password expiry status
     */
    private function getStatusMessage(bool $isExpired, bool $requiresChangeSoon, ?int $daysUntilExpiry): string
    {
        if ($isExpired) {
            return 'Password has expired and must be changed immediately.';
        }

        if ($requiresChangeSoon && $daysUntilExpiry !== null) {
            if ($daysUntilExpiry === 0) {
                return 'Password expires today. Please change it as soon as possible.';
            }

            if ($daysUntilExpiry === 1) {
                return 'Password expires tomorrow. Please change it soon.';
            }

            return "Password expires in $daysUntilExpiry days. Consider changing it soon.";
        }

        return 'Password expiry status retrieved successfully.';
    }
}
