<?php

namespace IamApi\Actions\Mfa;

use IamApi\Models\User;
use IamApi\Services\MfaService;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class EnableMfaAction
{
    public function __construct(
        public MfaService $mfaService
    ) {}

    /**
     * Execute the enable MFA action
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

            // Check if MFA is already enabled
            if ($user->mfa_enabled) {
                return [
                    'success' => false,
                    'message' => 'MFA is already enabled for this account.',
                    'code' => 400
                ];
            }

            // Generate TOTP secret and setup data
            $result = $this->mfaService->setupTotp($user);

            if (!$result['success']) {
                return $result;
            }

            // Generate recovery codes
            $recoveryCodesResult = $this->mfaService->generateRecoveryCodesWithResponse($user);

            if (!$recoveryCodesResult['success']) {
                return $recoveryCodesResult;
            }

            // Log MFA setup initiation
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ])
                ->log('MFA setup initiated');

            return [
                'success' => true,
                'message' => 'MFA setup initiated. Please scan the QR code with your authenticator app.',
                'data' => [
                    'secret' => $result['data']['secret'],
                    'qr_code' => $result['data']['qr_code'],
                    'backup_codes' => $recoveryCodesResult['data']['codes'],
                    'setup_instructions' => $this->getSetupInstructions()
                ]
            ];

        } catch (Exception $e) {
            Log::error('Enable MFA failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to enable MFA. Please try again later.',
                'code' => 500
            ];
        }
    }

    /**
     * Get setup instructions for MFA
     */
    private function getSetupInstructions(): string
    {
        return "1. Install Google Authenticator or similar app on your mobile device\n" .
               "2. Scan the QR code or enter the secret key manually\n" .
               "3. Enter the 6-digit code from your app to verify setup\n" .
               "4. Save your recovery codes in a secure location\n" .
               "5. Each recovery code can only be used once";
    }
}
