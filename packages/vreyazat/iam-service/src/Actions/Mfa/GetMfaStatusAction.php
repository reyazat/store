<?php

namespace IamApi\Actions\Mfa;

use IamApi\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class GetMfaStatusAction
{
    /**
     * Execute the get MFA status action
     */
    public function execute(array $data): array
    {
        /** @var User $user */
        $user = $data['user'];

        try {
            // Get TOTP secret information with eager loading
            $user->load(['totpSecret', 'recoveryCodes']);
            $totpSecret = $user->totpSecret;

            return [
                'success' => true,
                'message' => 'MFA status retrieved successfully',
                'data' => [
                    'mfa_enabled' => $user->mfa_enabled,
                    'totp_configured' => $totpSecret !== null && $totpSecret->enabled,
                    'recovery_codes_count' => $user->recoveryCodes()->where('used_at', null)->count(),
                    'total_recovery_codes' => $user->recoveryCodes()->count(),
                    'backup_codes_generated_at' => $totpSecret?->created_at?->toISOString(),
                    'setup_completed' => $user->mfa_enabled && $totpSecret !== null && $totpSecret->enabled,
                    'totp_secret_exists' => $totpSecret !== null
                ]
            ];

        } catch (Exception $e) {
            Log::error('Get MFA status failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve MFA status. Please try again later.',
                'code' => 500
            ];
        }
    }
}
