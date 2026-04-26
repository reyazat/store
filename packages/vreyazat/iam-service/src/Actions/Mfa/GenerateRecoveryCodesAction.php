<?php

namespace IamApi\Actions\Mfa;

use IamApi\Models\User;
use IamApi\Services\MfaService;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class GenerateRecoveryCodesAction
{
    public function __construct(
        public MfaService $mfaService
    ) {}

    /**
     * Execute the generate recovery codes action
     */
    public function execute(array $data): array
    {
        /** @var User $user */
        $user = $data['user'];
        $currentPassword = $data['current_password'];

        try {
            // Verify the current password
            if (!Hash::check($currentPassword, $user->password)) {
                return [
                    'success' => false,
                    'message' => 'Invalid current password.',
                    'code' => 401
                ];
            }

            // Check if MFA is enabled
            if (!$user->mfa_enabled) {
                return [
                    'success' => false,
                    'message' => 'MFA is not enabled for this account.',
                    'code' => 400
                ];
            }

            // Generate new recovery codes
            $result = $this->mfaService->generateRecoveryCodesWithResponse($user);

            if (!$result['success']) {
                return $result;
            }

            // Log recovery codes generation
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'codes_count' => count($result['data']['codes'])
                ])
                ->log('New MFA recovery codes generated');

            return [
                'success' => true,
                'message' => 'New recovery codes generated successfully',
                'data' => [
                    'recovery_codes' => $result['data']['codes'],
                    'generated_at' => now()->toISOString(),
                    'warning' => 'These codes can only be used once each. Store them securely.'
                ]
            ];

        } catch (Exception $e) {
            Log::error('Generate recovery codes failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate recovery codes. Please try again later.',
                'code' => 500
            ];
        }
    }
}
