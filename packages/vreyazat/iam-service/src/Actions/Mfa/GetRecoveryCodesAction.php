<?php

namespace IamApi\Actions\Mfa;

use IamApi\Models\User;
use IamApi\Models\UserRecoveryCode;
use Exception;
use Illuminate\Support\Facades\Log;

class GetRecoveryCodesAction
{
    /**
     * Execute the get recovery codes action
     */
    public function execute(array $data): array
    {
        /** @var User $user */
        $user = $data['user'];

        try {
            // Check if MFA is enabled
            if (!$user->mfa_enabled) {
                return [
                    'success' => false,
                    'message' => 'MFA is not enabled for this account.',
                    'code' => 400
                ];
            }

            // Get recovery codes
            /** @var UserRecoveryCode $recoveryCodes */
            $recoveryCodes = $user->recoveryCodes()->orderBy('created_at', 'desc')->get();

            if ($recoveryCodes === null) {
                return [
                    'success' => false,
                    'message' => 'No recovery codes found. Please generate new codes.',
                    'code' => 404
                ];
            }

            // Check if codes were recently generated (within the last 5 minutes)
            $recentlyGenerated = $recoveryCodes->first()->created_at->isAfter(now()->subMinutes(5));

            // Prepare masked codes for security
            $maskedCodes = [];
            $usedCount = 0;

            foreach ($recoveryCodes as $recoveryCode) {
                if ($recoveryCode->used_at) {
                    $usedCount++;
                    continue; // Skip used codes in display
                }

                // Show full code only if recently generated, otherwise mask
                if ($recentlyGenerated) {
                    $maskedCodes[] = $recoveryCode->code;
                } else {
                    $maskedCodes[] = '****' . substr($recoveryCode->code, -4);
                }
            }

            $totalCodes = $recoveryCodes->count();
            $remainingCodes = $totalCodes - $usedCount;

            return [
                'success' => true,
                'message' => 'Recovery codes retrieved successfully',
                'data' => [
                    'recovery_codes' => $maskedCodes,
                    'total_codes' => $totalCodes,
                    'used_codes' => $usedCount,
                    'remaining_codes' => $remainingCodes,
                    'generated_at' => $recoveryCodes->first()->created_at->toISOString(),
                    'recently_generated' => $recentlyGenerated,
                    'warning' => $recentlyGenerated
                        ? 'These codes are shown in full because they were just generated. Save them securely as they will be masked in future views.'
                        : 'Recovery codes are shown masked for security. Generate new codes to see full codes.'
                ]
            ];

        } catch (Exception $e) {
            Log::error('Get recovery codes failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve recovery codes. Please try again later.',
                'code' => 500
            ];
        }
    }
}
