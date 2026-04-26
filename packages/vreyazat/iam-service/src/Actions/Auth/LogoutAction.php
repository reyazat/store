<?php

namespace IamApi\Actions\Auth;

use IamApi\Actions\BaseAction;
use IamApi\Models\User;
use IamApi\Services\TokenService;
use Illuminate\Support\Facades\Auth;

class LogoutAction extends BaseAction
{
    public function __construct(
        protected TokenService $tokenService
    ) {}

    public function execute(array $data = []): array
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return $this->error('User not authenticated', null, 401);
        }

        // Get current token JTI from JWT payload
        $payload = Auth::payload();
        $jti = $payload->get('jti');

        if ($data['logout_all_devices'] ?? false) {
            // Revoke all tokens for user
            $this->tokenService->revokeAllForUser($user);

            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'logout_type' => 'all_devices'
                ])
                ->log('User logged out from all devices');

            return $this->success(null, 'Logged out from all devices successfully');
        }

// Revoke current token only
        if ($jti) {
            $this->tokenService->revokeByJti($jti);
        }

        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'logout_type' => 'current_device'
            ])
            ->log('User logged out');

        return $this->success(null, 'Logged out successfully');
    }
}
