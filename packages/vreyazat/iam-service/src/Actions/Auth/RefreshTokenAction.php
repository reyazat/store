<?php

namespace IamApi\Actions\Auth;

use IamApi\Actions\BaseAction;
use IamApi\Services\TokenService;
use Exception;
use Illuminate\Support\Facades\Log;

class RefreshTokenAction extends BaseAction
{
    public function __construct(
        protected TokenService $tokenService
    ) {}

    public function execute(array $data = []): array
    {
        try {
            // Refresh the token
            $tokens = $this->tokenService->refresh($data['refresh_token']);

            return $this->success([
                'tokens' => $tokens
            ], 'Token refreshed successfully');

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->error('Invalid or expired refresh token', null, 401);
        }
    }
}
