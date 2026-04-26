<?php

namespace IamApi\Actions\Session;

use IamApi\Actions\BaseAction;
use IamApi\Models\User;
use IamApi\Services\TokenService;
use Exception;
use Illuminate\Support\Facades\Auth;

class GetActiveSessionsAction extends BaseAction
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

            // Get all active sessions for the user
            $activeSessions = $this->tokenService->getActiveTokensForUser($user);

            // Get all sessions history (including revoked) for the user
            $allSessions = $this->tokenService->getAllTokensForUser($user);

            $currentSession = null;
            $otherSessions = [];
            $sessionHistory = [];

            foreach ($activeSessions as $session) {
                $sessionData = [
                    'jti' => $session->jti,
                    'device_name' => $session->device_name,
                    'user_agent' => $session->user_agent,
                    'ip' => $session->ip,
                    'last_used_at' => $session->last_used_at?->toISOString(),
                    'created_at' => $session->created_at->toISOString(),
                    'expires_at' => $session->expires_at->toISOString(),
                    'is_current' => $session->jti === $currentJti,
                    'status' => 'active'
                ];

                if ($session->jti === $currentJti) {
                    $currentSession = $sessionData;
                } else {
                    $otherSessions[] = $sessionData;
                }
            }

            // Build session history (all sessions sorted by created_at descending)
            foreach ($allSessions as $session) {
                $sessionHistory[] = [
                    'jti' => $session->jti,
                    'device_name' => $session->device_name,
                    'user_agent' => $session->user_agent,
                    'ip' => $session->ip,
                    'last_used_at' => $session->last_used_at?->toISOString(),
                    'created_at' => $session->created_at->toISOString(),
                    'expires_at' => $session->expires_at->toISOString(),
                    'revoked_at' => $session->revoked_at?->toISOString(),
                    'is_current' => $session->jti === $currentJti,
                    'status' => $session->revoked_at ? 'revoked' : ($session->expires_at < now() ? 'expired' : 'active')
                ];
            }

            return $this->success([
                'current_session' => $currentSession,
                'other_sessions' => $otherSessions,
                'total_active_sessions' => $activeSessions->count(),
                'session_history' => $sessionHistory,
                'total_sessions' => $allSessions->count()
            ]);

        } catch (Exception $e) {
            return $this->error('Failed to retrieve active sessions: ' . $e->getMessage(), null, 500);
        }
    }
}
