<?php

namespace IamApi\Actions\Auth;

use IamApi\Actions\BaseAction;
use IamApi\Services\AuthService;
use IamApi\Services\TokenService;
use IamApi\Services\MfaService;

class LoginWithPasswordAction extends BaseAction
{
    public function __construct(
        protected AuthService $authService,
        protected TokenService $tokenService,
        protected MfaService $mfaService
    ) {}

    public function execute(array $data = []): array
    {
        // Get rate limit key from middleware
        $rateKey = request()->attributes->get('rate_limit_key');
        
        // Validate credentials
        $user = $this->authService->validateCredentials(
            $data['email'],
            $data['password'],
            $rateKey
        );

        if (!$user) {
            return $this->error('Invalid credentials', null, 400);
        }

        // Check if an account is locked
        if ($user->isLocked()) {
            return $this->error('Account is temporarily locked', [
                'locked_until' => $user->locked_until->toISOString()
            ], 423);
        }

        // Check if user is active
        if ($user->status !== 'active') {
            return $this->error('Account is disabled', null, 403);
        }

        // Check if password change is required.
        // Issue a short-lived temp token so the frontend can call /password/force-change
        // without a full session token (user has not completed login yet).
        if ($user->must_change_password) {
            $tempToken = $this->tokenService->issue($user, [
                'temp' => true,
                'force_password_change' => true,
            ], [
                'device_name' => $data['device_name'] ?? 'Unknown Device'
            ]);

            return $this->success([
                'action_required' => 'change_password',
                'temp_token' => $tempToken['access_token'],
                'user_id' => $user->id,
            ], 'Password change required');
        }

        // Check if MFA is enabled
        if ($this->mfaService->isMfaEnabled($user)) {
            // Generate temporary token for MFA verification
            $tempToken = $this->tokenService->issue($user, [
                'temp' => true,
                'mfa_required' => true
            ], [
                'device_name' => $data['device_name'] ?? 'Unknown Device'
            ]);

            return $this->success([
                'action_required' => 'mfa_verification',
                'temp_token' => $tempToken['access_token'],
                'mfa_methods' => ['totp', 'recovery_code']
            ], 'MFA verification required');
        }

        // Generate tokens
        $tokens = $this->tokenService->issue($user, [], [
            'device_name' => $data['device_name'] ?? 'Unknown Device'
        ]);

        // Always load organization so UserResource includes it.
        // Buyers get null, store members get their org, super admin gets null.
        $user->load('organization');

        // Update login info
        $this->authService->touchLogin($user, request()->ip());

        // Log successful login
        activity()
            ->performedOn($user)
            ->causedBy($user)
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'device_name' => $data['device_name'] ?? 'Unknown Device'
            ])
            ->log('User logged in successfully');

        return $this->success([
            'user' => $user,
            'tokens' => $tokens
        ], 'Login successful');
    }
}
