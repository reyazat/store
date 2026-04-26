<?php

namespace Database\Seeders;

use IamApi\Models\User;
use IamApi\Models\UserRecoveryCode;
use IamApi\Models\UserTotpSecret;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MfaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔐 Seeding MFA data...');

        DB::transaction(function () {
            // Get users with MFA enabled
            $mfaUsers = User::where('mfa_enabled', true)->get();

            foreach ($mfaUsers as $user) {
                // Create TOTP secret
                $totpSecret = UserTotpSecret::create([
                    'user_id' => $user->id,
                    'secret' => $this->generateTotpSecret(),
                    'enabled' => true,
                    'created_at' => $user->created_at->addMinutes(rand(10, 60)),
                    'updated_at' => now()->subDays(rand(0, 7)),
                ]);

                // Generate recovery codes
                $recoveryCodes = [];
                for ($i = 0; $i < 10; $i++) {
                    $code = $this->generateRecoveryCode();
                    $recoveryCodes[] = $code;

                    UserRecoveryCode::create([
                        'user_id' => $user->id,
                        'code_hash' => Hash::make($code),
                        'used_at' => ($i < 2 && rand(0, 1)) ? now()->subDays(rand(1, 30)) : null,
                        'created_at' => $totpSecret->created_at->addMinutes($i),
                        'updated_at' => ($i < 2 && rand(0, 1)) ? now()->subDays(rand(1, 30)) : $totpSecret->created_at->addMinutes($i),
                    ]);
                }

                $this->command->line("   - Created MFA setup for: {$user->name}");
                $this->command->line("     TOTP Secret: {$totpSecret->secret}");
                $this->command->line("     Recovery Codes: " . implode(', ', array_slice($recoveryCodes, 0, 3)) . '...');
            }

            // Create some users with partial MFA setup (TOTP secret but not activated)
            $partialMfaUsers = User::where('mfa_enabled', false)
                ->where('status', 'active')
                ->limit(2)
                ->get();

            foreach ($partialMfaUsers as $user) {
                // Create TOTP secret but don't enable MFA yet
                $totpSecret = UserTotpSecret::create([
                    'user_id' => $user->id,
                    'secret' => $this->generateTotpSecret(),
                    'enabled' => false,
                    'created_at' => now()->subDays(rand(1, 7)),
                    'updated_at' => now()->subDays(rand(1, 7)),
                ]);

                $this->command->line("   - Created partial MFA setup for: {$user->name} (TOTP secret only)");
                $this->command->line("     TOTP Secret: {$totpSecret->secret}");
            }

            $totpCount = UserTotpSecret::count();
            $recoveryCodeCount = UserRecoveryCode::count();
            $usedRecoveryCodeCount = UserRecoveryCode::whereNotNull('used_at')->count();
            $activeMfaUsers = User::where('mfa_enabled', true)->count();

            $this->command->info("✅ Created MFA data:");
            $this->command->line("   - TOTP secrets: {$totpCount}");
            $this->command->line("   - Recovery codes: {$recoveryCodeCount}");
            $this->command->line("   - Used recovery codes: {$usedRecoveryCodeCount}");
            $this->command->line("   - Active MFA users: {$activeMfaUsers}");
            
            $this->command->warn("📝 Note: For testing TOTP, use Google Authenticator or similar app with the provided secrets");
        });
    }

    /**
     * Generate a TOTP secret (Base32 encoded)
     */
    private function generateTotpSecret(): string
    {
        // Generate 20 random bytes and encode as Base32
        $bytes = random_bytes(20);
        return $this->base32Encode($bytes);
    }

    /**
     * Generate a recovery code
     */
    private function generateRecoveryCode(): string
    {
        // Generate 8-character recovery code (letters and numbers)
        return strtoupper(Str::random(4) . '-' . Str::random(4));
    }

    /**
     * Base32 encode (simplified implementation for TOTP secrets)
     */
    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;
        
        for ($i = 0, $j = strlen($data); $i < $j; $i++) {
            $v <<= 8;
            $v += ord($data[$i]);
            $vbits += 8;
            
            while ($vbits >= 5) {
                $vbits -= 5;
                $output .= $alphabet[$v >> $vbits];
                $v &= ((1 << $vbits) - 1);
            }
        }
        
        if ($vbits > 0) {
            $v <<= (5 - $vbits);
            $output .= $alphabet[$v];
        }
        
        return $output;
    }
}
