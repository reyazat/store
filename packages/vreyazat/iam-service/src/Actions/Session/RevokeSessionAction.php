<?php

namespace IamApi\Actions\Session;

use IamApi\Actions\BaseAction;
use IamApi\Models\User;
use IamApi\Services\TokenService;
use Exception;
use Illuminate\Support\Facades\Auth;

class RevokeSessionAction extends BaseAction
{
    public function __construct(
        public TokenService $tokenService
    ) {}

    public function execute(array $data = []): array
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            $currentJti = Auth::payload()->get('jti');
            $targetJti = $data['jti'];

            // Prevent revoking current session
            if ($targetJti === $currentJti) {
                return $this->error('Cannot revoke current session. Use logout endpoint instead.');
            }

            // Get all active sessions for the user to verify ownership
            $activeSessions = $this->tokenService->getActiveTokensForUser($user);
            $targetSession = $activeSessions->firstWhere('jti', $targetJti);

            if (!$targetSession) {
                return $this->error('Session not found or already revoked', null, 404);
            }

            // Revoke the session
            $revoked = $this->tokenService->revokeByJti($targetJti);

            if (!$revoked) {
                return $this->error('Failed to revoke session', null, 500);
            }

            // Log the session revocation
            activity()
                ->performedOn($user)
                ->withProperties([
                    'revoked_session_jti' => $targetJti,
                    'revoked_device' => $targetSession->device_name,
                    'revoked_ip' => $targetSession->ip,
                    'revoked_by_jti' => $currentJti
                ])
                ->log('Session revoked remotely');

            return $this->success([
                'revoked_session' => [
                    'jti' => $targetJti,
                    'device_name' => $targetSession->device_name,
                    'revoked_at' => now()->toISOString()
                ]
            ]);

        } catch (Exception $e) {
            return $this->error('Failed to revoke session: ' . $e->getMessage(), null, 500);
        }
    }
}
