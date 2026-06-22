<?php

namespace IamApi\Database\Seeders;

use IamApi\Models\JwtToken;
use IamApi\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JwtTokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔑 Seeding JWT tokens...');

        DB::transaction(function () {
            // Get some active users
            $users = User::where('status', 'active')
                ->whereNotNull('last_login_at')
                ->limit(5)
                ->get();

            foreach ($users as $user) {
                // Create active token
                $activeToken = JwtToken::create([
                    'user_id' => $user->id,
                    'jti' => Str::uuid()->toString(),
                    'refresh_token_hash' => hash('sha256', Str::random(64)),
                    'expires_at' => now()->addMinutes(config('jwt.refresh_ttl', 43200)),
                    'last_used_at' => now()->subMinutes(rand(1, 60)),
                    'user_agent' => $this->getRandomUserAgent(),
                    'ip' => $this->getRandomIp(),
                    'device_name' => $this->getRandomDeviceName(),
                    'revoked_at' => null,
                    'created_at' => now()->subHours(rand(1, 24)),
                    'updated_at' => now()->subMinutes(rand(1, 60)),
                ]);

                // Create some expired tokens
                if (rand(0, 1)) {
                    $expiredToken = JwtToken::create([
                        'user_id' => $user->id,
                        'jti' => Str::uuid()->toString(),
                        'refresh_token_hash' => hash('sha256', Str::random(64)),
                        'expires_at' => now()->subDays(rand(1, 7)),
                        'last_used_at' => now()->subDays(rand(1, 7)),
                        'user_agent' => $this->getRandomUserAgent(),
                        'ip' => $this->getRandomIp(),
                        'device_name' => $this->getRandomDeviceName(),
                        'revoked_at' => null,
                        'created_at' => now()->subDays(rand(7, 30)),
                        'updated_at' => now()->subDays(rand(1, 7)),
                    ]);
                }

                // Create some revoked tokens
                if (rand(0, 1)) {
                    $revokedToken = JwtToken::create([
                        'user_id' => $user->id,
                        'jti' => Str::uuid()->toString(),
                        'refresh_token_hash' => hash('sha256', Str::random(64)),
                        'expires_at' => now()->addDays(rand(1, 30)),
                        'last_used_at' => now()->subDays(rand(1, 5)),
                        'user_agent' => $this->getRandomUserAgent(),
                        'ip' => $this->getRandomIp(),
                        'device_name' => $this->getRandomDeviceName(),
                        'revoked_at' => now()->subDays(rand(1, 5)),
                        'created_at' => now()->subDays(rand(5, 15)),
                        'updated_at' => now()->subDays(rand(1, 5)),
                    ]);
                }

                $this->command->line("   - Created tokens for user: {$user->name}");
            }

            $tokenCount = JwtToken::count();
            $activeCount = JwtToken::whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->count();
            $expiredCount = JwtToken::where('expires_at', '<=', now())->count();
            $revokedCount = JwtToken::whereNotNull('revoked_at')->count();

            $this->command->info("✅ Created JWT tokens:");
            $this->command->line("   - Total tokens: {$tokenCount}");
            $this->command->line("   - Active tokens: {$activeCount}");
            $this->command->line("   - Expired tokens: {$expiredCount}");
            $this->command->line("   - Revoked tokens: {$revokedCount}");
        });
    }

    /**
     * Get random user agent string
     */
    private function getRandomUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (iPad; CPU OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Android 11; Mobile; rv:89.0) Gecko/89.0 Firefox/89.0',
            'IAM-Mobile-App/1.0.0 (iOS 14.6; iPhone13,2)',
            'IAM-Mobile-App/1.0.0 (Android 11; SM-G991B)',
        ];

        return $userAgents[array_rand($userAgents)];
    }

    /**
     * Get random IP address
     */
    private function getRandomIp(): string
    {
        $ips = [
            '192.168.1.' . rand(100, 200),
            '10.0.0.' . rand(50, 100),
            '172.16.0.' . rand(10, 50),
            '203.0.113.' . rand(1, 254),
            '198.51.100.' . rand(1, 254),
        ];

        return $ips[array_rand($ips)];
    }

    /**
     * Get random device name
     */
    private function getRandomDeviceName(): string
    {
        $devices = [
            'Chrome on Windows',
            'Safari on macOS',
            'Chrome on Linux',
            'Safari on iPhone',
            'Safari on iPad',
            'Firefox on Android',
            'IAM Mobile App (iOS)',
            'IAM Mobile App (Android)',
            'Edge on Windows',
            'Firefox on macOS',
        ];

        return $devices[array_rand($devices)];
    }
}
