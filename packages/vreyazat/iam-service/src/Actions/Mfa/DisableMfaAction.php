<?php

namespace IamApi\Actions\Mfa;

use IamApi\Models\User;
use IamApi\Services\MfaService;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DisableMfaAction
{
    public function __construct(
        public MfaService $mfaService
    ) {}

    /**
     * Execute the disable MFA action
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

            // Disable MFA
            $result = $this->mfaService->disableMfa($user);

            if (!$result['success']) {
                return $result;
            }

            // Log MFA disabling
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ])
                ->log('MFA disabled');

            return [
                'success' => true,
                'message' => 'MFA disabled successfully',
                'data' => [
                    'mfa_enabled' => false,
                    'disabled_at' => now()->toISOString()
                ]
            ];

        } catch (Exception $e) {
            Log::error('Disable MFA failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to disable MFA. Please try again later.',
                'code' => 500
            ];
        }
    }
}
