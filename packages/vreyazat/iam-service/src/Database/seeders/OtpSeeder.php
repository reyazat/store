<?php

namespace Database\Seeders;

use IamApi\Models\Otp;
use IamApi\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OtpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📱 Seeding OTP records...');

        DB::transaction(function () {
            // Get some users for OTP testing
            $users = User::limit(6)->get();

            foreach ($users as $index => $user) {
                // Create active OTP for email verification
                if ($index < 2) {
                    $code = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                    Otp::create([
                        'user_id' => $user->id,
                        'purpose' => 'verify_email',
                        'channel' => 'email',
                        'code_hash' => Hash::make($code),
                        'expires_at' => now()->addMinutes(10),
                        'attempts' => rand(0, 2),
                        'max_attempts' => 5,
                        'used' => false,
                    ]);
                    $this->command->line("   - Created email verification OTP for: {$user->email}");
                }

                // Create OTP for password reset
                if ($index >= 1 && $index < 3) {
                    $code = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                    Otp::create([
                        'user_id' => $user->id,
                        'purpose' => 'password_reset',
                        'channel' => 'email',
                        'code_hash' => Hash::make($code),
                        'expires_at' => now()->addMinutes(15),
                        'attempts' => rand(0, 1),
                        'max_attempts' => 3,
                        'used' => false,
                    ]);
                    $this->command->line("   - Created password reset OTP for: {$user->email}");
                }

                // Create SMS OTP for phone verification
                if ($user->phone && $index >= 1 && $index < 3) {
                    $code = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                    Otp::create([
                        'user_id' => $user->id,
                        'purpose' => 'verify_phone',
                        'channel' => 'sms',
                        'code_hash' => Hash::make($code),
                        'expires_at' => now()->addMinutes(5),
                        'attempts' => rand(0, 1),
                        'max_attempts' => 3,
                        'used' => false,
                    ]);
                    $this->command->line("   - Created SMS verification OTP for: {$user->phone}");
                }

                // Create login OTP (for 2FA)
                if ($user->mfa_enabled && $index < 2) {
                    $code = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                    Otp::create([
                        'user_id' => $user->id,
                        'purpose' => '2fa',
                        'channel' => 'email',
                        'code_hash' => Hash::make($code),
                        'expires_at' => now()->addMinutes(10),
                        'attempts' => 0,
                        'max_attempts' => 3,
                        'used' => false,
                    ]);
                    $this->command->line("   - Created 2FA OTP for: {$user->email}");
                }

                // Create some expired OTPs
                if ($index >= 3) {
                    $code = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                    Otp::create([
                        'user_id' => $user->id,
                        'purpose' => 'verify_email',
                        'channel' => 'email',
                        'code_hash' => Hash::make($code),
                        'expires_at' => now()->subMinutes(rand(15, 60)),
                        'attempts' => rand(2, 5),
                        'max_attempts' => 5,
                        'used' => false,
                    ]);
                    $this->command->line("   - Created expired OTP for: {$user->email}");
                }

                // Create some used OTPs
                if ($index >= 4) {
                    $code = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                    Otp::create([
                        'user_id' => $user->id,
                        'purpose' => 'password_reset',
                        'channel' => 'email',
                        'code_hash' => Hash::make($code),
                        'expires_at' => now()->addMinutes(15),
                        'attempts' => rand(1, 2),
                        'max_attempts' => 3,
                        'used' => true,
                        'created_at' => now()->subHours(rand(1, 12)),
                    ]);
                    $this->command->line("   - Created used OTP for: {$user->email}");
                }
            }

            // Create OTPs with maximum attempts reached
            $maxAttemptUsers = $users->take(2);
            foreach ($maxAttemptUsers as $user) {
                $code = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                Otp::create([
                    'user_id' => $user->id,
                    'purpose' => 'verify_email',
                    'channel' => 'email',
                    'code_hash' => Hash::make($code),
                    'expires_at' => now()->addMinutes(10),
                    'attempts' => 5,
                    'max_attempts' => 5,
                    'used' => false,
                ]);
                $this->command->line("   - Created max-attempts OTP for: {$user->email}");
            }

            $otpCount = Otp::count();
            $activeCount = Otp::where('expires_at', '>', now())
                ->where('used', false)
                ->whereRaw('attempts < max_attempts')
                ->count();
            $expiredCount = Otp::where('expires_at', '<=', now())->count();
            $usedCount = Otp::where('used', true)->count();
            $maxAttemptsCount = Otp::whereRaw('attempts >= max_attempts')->count();

            $this->command->info("✅ Created OTP records:");
            $this->command->line("   - Total OTPs: {$otpCount}");
            $this->command->line("   - Active OTPs: {$activeCount}");
            $this->command->line("   - Expired OTPs: {$expiredCount}");
            $this->command->line("   - Used OTPs: {$usedCount}");
            $this->command->line("   - Max attempts reached: {$maxAttemptsCount}");
        });
    }
}
