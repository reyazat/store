<?php

namespace IamApi\Services;

use IamApi\Models\User;
use IamApi\Repositories\Contracts\JwtTokenRepositoryInterface;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class TokenService
{
    protected JwtTokenRepositoryInterface $tokenRepository;
    protected UserRepositoryInterface $userRepository;

    public function __construct(
        JwtTokenRepositoryInterface $tokenRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->tokenRepository = $tokenRepository;
        $this->userRepository = $userRepository;
    }

    public function issue(User $user, array $claims = [], array $deviceInfo = []): array
    {
        return DB::transaction(function () use ($user, $claims, $deviceInfo) {
            // Check if single device only mode is enabled
            $singleDeviceOnly = config('iam.session.single_device_only', true);
            
            if ($singleDeviceOnly) {
                // Revoke all existing tokens for this user (logout from other devices)
                $this->revokeAllForUser($user);
                
                // Log activity
                activity()
                    ->performedOn($user)
                    ->causedBy($user)
                    ->withProperties([
                        'reason' => 'single_device_login',
                        'ip' => $deviceInfo['ip'] ?? request()->ip(),
                        'device_name' => $deviceInfo['device_name'] ?? 'Unknown Device',
                    ])
                    ->log('Previous sessions revoked due to single device policy');
            }

            $jti = (string) Str::uuid();
            
            // Merge organization_id into claims if provided
            $tokenClaims = ['jti' => $jti] + $claims;
            
            $access = Auth::guard('api')->claims($tokenClaims)->fromUser($user);

            $refreshPlain = Str::random(64);

            $tokenData = [
                'user_id' => $user->id,
                'jti' => $jti,
                'refresh_token_hash' => hash('sha256', $refreshPlain),
                'expires_at' => now()->addMinutes((int) config('iam.jwt.refresh_ttl', 43200)),
                'last_used_at' => now(),
                'user_agent' => $deviceInfo['user_agent'] ?? request()->userAgent(),
                'ip' => $deviceInfo['ip'] ?? request()->ip(),
                'device_name' => $deviceInfo['device_name'] ?? 'Unknown Device',
            ];

            $this->tokenRepository->create($tokenData);

            // JWT ttl is in minutes, convert to seconds for expires_in
            $ttl = (int) config('jwt.ttl', 30);
            $expiresInSeconds = $ttl * 60;

            return [
                'access_token' => $access,
                'refresh_token' => $refreshPlain,
                'expires_in' => $expiresInSeconds,
                'token_type' => 'Bearer',
            ];
        });
    }

    /**
     * @throws Exception
     */
    public function refresh(string $refresh): array
    {
        $hash = hash('sha256', $refresh);
        $token = $this->tokenRepository->findByRefreshToken($hash);

        if (!$token || $token->revoked_at || $token->expires_at < now()) {
            throw new Exception('Invalid or expired refresh token');
        }

        // Get user and check status BEFORE revoking old token
        $user = $this->userRepository->find($token->user_id);
        if (!$user) {
            throw new Exception('User not found for token refresh');
        }

        // Security checks - prevent refresh for inactive/locked users
        if ($user->status !== 'active') {
            throw new Exception('Account is disabled');
        }

        if ($user->isLocked()) {
            throw new Exception('Account is locked');
        }

        // Check if user still has organization access (unless Super Admin)
        if (!$user->organization_id && !$user->is_super_admin) {
            throw new Exception('User has no organization access');
        }

        // Now safe to revoke old token
        $this->tokenRepository->update($token, ['revoked_at' => now()]);

        return $this->issue($user, [], [
            'user_agent' => $token->user_agent,
            'ip' => $token->ip,
            'device_name' => $token->device_name,
        ]);
    }

    public function revokeByJti(string $jti): bool
    {
        return $this->tokenRepository->revokeByJti($jti);
    }

    public function revokeAllForUser(User $user): bool
    {
        return $this->tokenRepository->revokeAllForUser($user);
    }

    public function getActiveTokensForUser(User $user): Collection
    {
        return $this->tokenRepository->findActiveTokensForUser($user);
    }

    public function getAllTokensForUser(User $user): Collection
    {
        return $this->tokenRepository->findAllTokensForUser($user);
    }

    public function cleanupExpiredTokens(): int
    {
        return $this->tokenRepository->revokeExpiredTokens();
    }

    /**
     * Verify JWT token and return validation result
     */
    public function verifyToken(string $token): array
    {
        try {
            // Parse and validate the token
            $payload = JWTAuth::setToken($token)->getPayload();

            // Get the JTI from payload
            $jti = $payload->get('jti');

            // Check if token exists in our database and is not revoked
            if ($jti) {
                $dbToken = $this->tokenRepository->findByJti($jti);
                if (!$dbToken || $dbToken->revoked_at) {
                    return [
                        'valid' => false,
                        'error' => 'Token has been revoked',
                        'payload' => null
                    ];
                }
            }

            return [
                'valid' => true,
                'payload' => $payload->toArray(),
                'user_id' => $payload->get('sub'),
                'jti' => $jti
            ];

        } catch (JWTException $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
                'payload' => null
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => 'Token verification failed: ' . $e->getMessage(),
                'payload' => null
            ];
        }
    }

    /**
     * Blacklist/revoke a token by token string
     */
    public function blacklistToken(string $token): bool
    {
        try {
            // Parse and get the JTI
            $payload = JWTAuth::setToken($token)->getPayload();
            $jti = $payload->get('jti');

            if ($jti) {
                // Revoke by JTI
                return $this->revokeByJti($jti);
            }

            return false;
        } catch (JWTException $e) {
            Log::error('Failed to blacklist token: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify admin unlock token
     * This verifies a special admin-issued token for unlocking user accounts
     */
    public function verifyAdminUnlockToken(string $token, int $userId): array
    {
        try {
            // Verify the token
            $result = $this->verifyToken($token);

            if (!$result['valid']) {
                return [
                    'valid' => false,
                    'error' => $result['error'] ?? 'Invalid token'
                ];
            }

            // Check if the token has admin unlock permission
            $payload = $result['payload'];
            
            // Verify token purpose is for account unlock
            if (!isset($payload['purpose']) || $payload['purpose'] !== 'account_unlock') {
                return [
                    'valid' => false,
                    'error' => 'Token is not valid for account unlock'
                ];
            }

            // Verify token is for the correct user
            if (isset($payload['target_user_id']) && $payload['target_user_id'] != $userId) {
                return [
                    'valid' => false,
                    'error' => 'Token is not valid for this user'
                ];
            }

            // Verify the issuer has Super Admin privileges
            $issuerId = $payload['sub'] ?? null;
            if ($issuerId) {
                $issuer = $this->userRepository->find($issuerId);
                if (!$issuer || !$issuer->isSuperAdmin()) {
                    return [
                        'valid' => false,
                        'error' => 'Token was not issued by a Super Admin'
                    ];
                }
            }

            return [
                'valid' => true,
                'issuer_id' => $issuerId,
                'target_user_id' => $payload['target_user_id'] ?? null
            ];

        } catch (Exception $e) {
            Log::error('Admin unlock token verification failed: ' . $e->getMessage());
            return [
                'valid' => false,
                'error' => 'Token verification failed: ' . $e->getMessage()
            ];
        }
    }
}
