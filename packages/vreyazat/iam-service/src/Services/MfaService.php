<?php

namespace IamApi\Services;

use IamApi\Models\User;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use IamApi\Repositories\Contracts\UserTotpSecretRepositoryInterface;
use IamApi\Repositories\Contracts\UserRecoveryCodeRepositoryInterface;
use Exception;
use OTPHP\TOTP;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MfaService
{
    protected UserRepositoryInterface $userRepository;
    protected UserTotpSecretRepositoryInterface $totpRepository;
    protected UserRecoveryCodeRepositoryInterface $recoveryCodeRepository;

    public function __construct(
        UserRepositoryInterface $userRepository,
        UserTotpSecretRepositoryInterface $totpRepository,
        UserRecoveryCodeRepositoryInterface $recoveryCodeRepository
    ) {
        $this->userRepository = $userRepository;
        $this->totpRepository = $totpRepository;
        $this->recoveryCodeRepository = $recoveryCodeRepository;
    }

    /**
     * Generate TOTP secret for user
     */
    public function generateTotpSecret(User $user): array
    {
        $secret = $this->generateSecret();

        // Create or update TOTP secret
        $existingSecret = $this->totpRepository->findByUser($user);
        if ($existingSecret) {
            $this->totpRepository->updateSecretForUser($user, $secret);
        } else {
            $this->totpRepository->createForUser($user, $secret);
        }

        $totp = TOTP::create($secret);
        $totp->setLabel($user->email);
        $totp->setIssuer(config('app.name', 'IAM API'));

        return [
            'secret' => $secret,
            'qr_code' => $this->generateQrCode($totp->getProvisioningUri()),
            'backup_codes' => $this->generateRecoveryCodes($user)
        ];
    }

    /**
     * Enable TOTP for user after verification
     */
    public function enableTotp(User $user, string $code): bool
    {
        $totpSecret = $this->totpRepository->findByUser($user);
        if (!$totpSecret) {
            return false;
        }

        if (!$this->verifyTotpCodeForSetup($user, $code)) {
            return false;
        }

        return $this->totpRepository->enableForUser($user);
    }

    /**
     * Disable TOTP for user
     */
    public function disableTotp(User $user): bool
    {
        // Delete recovery codes
        $this->recoveryCodeRepository->deleteAllForUser($user);

        // Delete TOTP secret
        return $this->totpRepository->deleteForUser($user);
    }

    /**
     * Verify TOTP code (for already enabled MFA)
     */
    public function verifyTotpCode(User $user, string $code): bool
    {
        $totpSecret = $this->totpRepository->findByUser($user);
        if (!$totpSecret || !$totpSecret->enabled) {
            return false;
        }

        $totp = TOTP::create($totpSecret->secret);
        return $totp->verify($code, null, 30); // 30-second window
    }

    /**
     * Verify TOTP code during setup (before enabled)
     */
    public function verifyTotpCodeForSetup(User $user, string $code): bool
    {
        $totpSecret = $this->totpRepository->findByUser($user);
        if (!$totpSecret) {
            return false;
        }

        $totp = TOTP::create($totpSecret->secret);
        return $totp->verify($code, null, 30); // 30-second window
    }

    /**
     * Verify recovery code
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        // Get all unused recovery codes for the user
        $recoveryCodes = $this->recoveryCodeRepository->findUnusedByUser($user);
        
        foreach ($recoveryCodes as $recoveryCode) {
            if (Hash::check($code, $recoveryCode->code_hash)) {
                // Mark as used
                return $this->recoveryCodeRepository->markAsUsed($recoveryCode);
            }
        }
        
        return false;
    }

    /**
     * Generate new recovery codes (low-level method)
     */
    public function generateRecoveryCodes(User $user, int $count = 8): array
    {
        $codes = [];
        $hashes = [];

        for ($i = 0; $i < $count; $i++) {
            $code = Str::random(10);
            $codes[] = $code;
            $hashes[] = Hash::make($code);
        }

        // Replace existing codes
        $this->recoveryCodeRepository->regenerateCodesForUser($user, $hashes);

        return $codes;
    }

    /**
     * Generate recovery codes (high-level method with response structure)
     */
    public function generateRecoveryCodesWithResponse(User $user, int $count = 8): array
    {
        try {
            $codes = $this->generateRecoveryCodes($user, $count);

            return [
                'success' => true,
                'data' => [
                    'codes' => $codes,
                    'count' => count($codes),
                    'generated_at' => now()->toISOString()
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to generate recovery codes: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if user has MFA enabled
     */
    public function isMfaEnabled(User $user): bool
    {
        return $user->mfa_enabled && $this->totpRepository->isEnabledForUser($user);
    }


    /**
     * Generate random secret
     */
    protected function generateSecret(): string
    {
        return TOTP::generate()->getSecret();
    }

    /**
     * Generate QR code for TOTP setup
     */
    protected function generateQrCode(string $uri): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        return $writer->writeString($uri);
    }

    /**
     * Setup TOTP for user (high-level method)
     */
    public function setupTotp(User $user): array
    {
        try {
            // Generate TOTP secret and QR code
            $totpData = $this->generateTotpSecret($user);

            return [
                'success' => true,
                'data' => $totpData
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to setup TOTP: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Disable MFA for user (high-level method)
     */
    public function disableMfa(User $user): array
    {
        try {
            // Disable TOTP
            $disabled = $this->disableTotp($user);

            if (!$disabled) {
                return [
                    'success' => false,
                    'message' => 'Failed to disable MFA'
                ];
            }

            // Update user's mfa_enabled field
            $this->userRepository->update($user, ['mfa_enabled' => false]);

            return [
                'success' => true,
                'message' => 'MFA disabled successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to disable MFA: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Complete MFA setup by verifying TOTP code and enabling MFA
     */
    public function completeMfaSetup(User $user, string $code): array
    {
        try {
            // Verify TOTP code during setup (before enabled)
            $isValid = $this->verifyTotpCodeForSetup($user, $code);

            if (!$isValid) {
                return [
                    'success' => false,
                    'message' => 'Invalid TOTP code'
                ];
            }

            // Enable TOTP and update user's mfa_enabled field
            $this->totpRepository->enableForUser($user);
            $this->userRepository->update($user, ['mfa_enabled' => true]);

            return [
                'success' => true,
                'message' => 'MFA enabled successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to enable MFA: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify TOTP code (high-level method with response structure)
     * This is used during MFA setup before it's enabled
     */
    public function verifyTotp(User $user, string $code): array
    {
        try {
            $isValid = $this->verifyTotpCodeForSetup($user, $code);

            if (!$isValid) {
                return [
                    'success' => false,
                    'message' => 'Invalid TOTP code'
                ];
            }

            return [
                'success' => true,
                'message' => 'TOTP code verified successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to verify TOTP: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify recovery code (high-level method with response structure)
     */
    public function verifyRecoveryCodeWithResponse(User $user, string $code): array
    {
        try {
            $isValid = $this->verifyRecoveryCode($user, $code);

            if (!$isValid) {
                return [
                    'success' => false,
                    'message' => 'Invalid or already used recovery code'
                ];
            }

            return [
                'success' => true,
                'message' => 'Recovery code verified successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to verify recovery code: ' . $e->getMessage()
            ];
        }
    }
}
