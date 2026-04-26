<?php

namespace Database\Seeders;

use IamApi\Models\Organization;
use IamApi\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class PerformanceTestSeeder extends Seeder
{
    /**
     * Run the database seeds for performance testing.
     * Creates large datasets to test system performance under load.
     */
    public function run(): void
    {
        $this->command->info('🚀 Seeding performance test data...');
        $this->command->warn('This will create large amounts of test data. Continue? (y/N)');
        
        if (!$this->command->confirm('Create performance test data?', false)) {
            $this->command->info('Performance test seeding cancelled.');
            return;
        }

        $startTime = microtime(true);

        DB::transaction(function () {
            // Create large organizations
            $this->createLargeOrganizations();
            
            // Create many users
            $this->createManyUsers();
            
            // Create bulk JWT tokens
            $this->createBulkTokens();
            
            // Create bulk activity logs
            $this->createBulkActivityLogs();
        });

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        $this->command->info("✅ Performance test data created in {$duration} seconds");
    }

    /**
     * Create large organizations for testing
     */
    private function createLargeOrganizations(): void
    {
        $this->command->info('Creating large organizations...');

        for ($i = 1; $i <= 10; $i++) {
            $org = Organization::create([
                'name' => "Large Organization {$i}",
                'slug' => "large-org-{$i}",
                'description' => "Large organization {$i} for performance testing with many members",
                'website' => "https://large-org-{$i}.example.com",
                'email' => "contact@large-org-{$i}.example.com",
                'phone' => '+1-555-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'address' => "{$i}00 Performance Street, Load Test City, LT {$i}0000",
                'settings' => [
                    'timezone' => 'UTC',
                    'date_format' => 'Y-m-d',
                    'time_format' => 'H:i:s',
                    'currency' => 'USD',
                    'language' => 'en',
                    'features' => [
                        'mfa_required' => $i % 2 === 0,
                        'password_expiry_days' => 90,
                        'session_timeout' => 30,
                        'ip_whitelist_enabled' => false,
                    ]
                ],
                'status' => 'active',
                'created_at' => now()->subDays(rand(1, 365)),
                'updated_at' => now()->subDays(rand(0, 30)),
            ]);

            $this->command->line("   - Created: {$org->name}");
        }
    }

    /**
     * Create many users for performance testing
     */
    private function createManyUsers(): void
    {
        $this->command->info('Creating bulk users...');
        
        $organizations = Organization::where('name', 'LIKE', 'Large Organization%')->get();
        $batchSize = 100;
        $totalUsers = 1000;
        
        for ($batch = 0; $batch < $totalUsers / $batchSize; $batch++) {
            $users = [];
            
            for ($i = 1; $i <= $batchSize; $i++) {
                $userNumber = ($batch * $batchSize) + $i;
                $org = $organizations->random();
                
                $users[] = [
                    'name' => "Test User {$userNumber}",
                    'email' => "testuser{$userNumber}@performance.test",
                    'organization_id' => $org->id,
                    'email_verified_at' => rand(0, 1) ? now()->subDays(rand(0, 30)) : null,
                    'password' => Hash::make('TestUser123!'),
                    'phone' => '+1-555-' . str_pad($userNumber, 7, '0', STR_PAD_LEFT),
                    'phone_verified_at' => rand(0, 1) ? now()->subDays(rand(0, 30)) : null,
                    'status' => ['active', 'active', 'active', 'inactive'][rand(0, 3)], // 75% active
                    'must_change_password' => rand(0, 9) === 0, // 10% must change
                    'password_changed_at' => now()->subDays(rand(1, 90)),
                    'password_expires_at' => now()->addDays(rand(30, 90)),
                    'last_login_at' => rand(0, 1) ? now()->subDays(rand(0, 30)) : null,
                    'last_login_ip' => '192.168.' . rand(1, 255) . '.' . rand(1, 255),
                    'failed_login_attempts' => rand(0, 3),
                    'locked_until' => rand(0, 99) === 0 ? now()->addHours(1) : null, // 1% locked
                    'mfa_enabled' => $org->settings['features']['mfa_required'] ?? false || rand(0, 4) === 0, // Required or 20%
                    'created_at' => now()->subDays(rand(1, 365)),
                    'updated_at' => now()->subDays(rand(0, 30)),
                ];
            }
            
            // Bulk insert users
            User::insert($users);
            
            // Get the inserted users and assign roles
            $insertedUsers = User::where('email', 'LIKE', 'testuser%@performance.test')
                ->whereBetween('id', [
                    User::where('email', 'LIKE', 'testuser%@performance.test')->min('id') + ($batch * $batchSize),
                    User::where('email', 'LIKE', 'testuser%@performance.test')->min('id') + (($batch + 1) * $batchSize) - 1
                ])
                ->get();
            
            // Assign roles to users
            foreach ($insertedUsers as $user) {
                // Set organization context
                app(PermissionRegistrar::class)->setPermissionsTeamId($user->organization_id);
                
                // Assign role
                $user->assignRole('member');
            }
            
            $this->command->line("   - Created batch " . ($batch + 1) . " ({$batchSize} users)");
        }
        
        $this->command->info("   - Total users created: {$totalUsers}");
    }

    /**
     * Create bulk JWT tokens for performance testing
     */
    private function createBulkTokens(): void
    {
        $this->command->info('Creating bulk JWT tokens...');
        
        $users = User::where('email', 'LIKE', 'testuser%@performance.test')
            ->where('status', 'active')
            ->limit(200)
            ->get();
        
        $tokens = [];
        $tokenCount = 0;
        
        foreach ($users as $user) {
            // Create 2-5 tokens per user
            $userTokenCount = rand(2, 5);
            
            for ($i = 0; $i < $userTokenCount; $i++) {
                $createdAt = now()->subDays(rand(0, 30));
                $isExpired = rand(0, 4) === 0; // 20% expired
                $isRevoked = rand(0, 9) === 0; // 10% revoked
                
                $tokens[] = [
                    'user_id' => $user->id,
                    'jti' => \Illuminate\Support\Str::uuid()->toString(),
                    'refresh_token_hash' => hash('sha256', \Illuminate\Support\Str::random(64)),
                    'expires_at' => $isExpired 
                        ? $createdAt->copy()->subDays(rand(1, 10))
                        : $createdAt->copy()->addMinutes(config('jwt.refresh_ttl', 43200)),
                    'last_used_at' => $isRevoked ? null : now()->subMinutes(rand(1, 1440)),
                    'user_agent' => $this->getRandomUserAgent(),
                    'ip' => '192.168.' . rand(1, 255) . '.' . rand(1, 255),
                    'device_name' => $this->getRandomDeviceName(),
                    'is_revoked' => $isRevoked,
                    'revoked_at' => $isRevoked ? now()->subDays(rand(1, 30)) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $isRevoked ? now()->subDays(rand(1, 30)) : now()->subMinutes(rand(1, 1440)),
                ];
                
                $tokenCount++;
                
                // Insert in batches of 500
                if (count($tokens) >= 500) {
                    DB::table('jwt_tokens')->insert($tokens);
                    $tokens = [];
                }
            }
        }
        
        // Insert remaining tokens
        if (!empty($tokens)) {
            DB::table('jwt_tokens')->insert($tokens);
        }
        
        $this->command->info("   - Created {$tokenCount} JWT tokens");
    }

    /**
     * Create bulk activity logs for performance testing
     */
    private function createBulkActivityLogs(): void
    {
        $this->command->info('Creating bulk activity logs...');
        
        $users = User::where('email', 'LIKE', 'testuser%@performance.test')->limit(100)->get();
        $activities = [];
        $activityCount = 0;
        
        $logTypes = ['user', 'authentication', 'security', 'admin', 'organization'];
        $descriptions = [
            'user' => ['User registered', 'Profile updated', 'Account activated'],
            'authentication' => ['User logged in', 'Failed login attempt', 'User logged out'],
            'security' => ['Password changed', 'MFA enabled', 'Account locked'],
            'admin' => ['User role assigned', 'User account locked', 'User password reset'],
            'organization' => ['User joined organization', 'Organization settings updated', 'User role changed'],
        ];
        
        foreach ($users as $user) {
            // Create 10-50 activities per user
            $userActivityCount = rand(10, 50);
            
            for ($i = 0; $i < $userActivityCount; $i++) {
                $logName = $logTypes[array_rand($logTypes)];
                $description = $descriptions[$logName][array_rand($descriptions[$logName])];
                $createdAt = now()->subDays(rand(0, 365));
                
                $activities[] = [
                    'log_name' => $logName,
                    'description' => $description,
                    'subject_type' => User::class,
                    'subject_id' => $user->id,
                    'causer_type' => User::class,
                    'causer_id' => $user->id,
                    'properties' => json_encode([
                        'ip' => '192.168.' . rand(1, 255) . '.' . rand(1, 255),
                        'user_agent' => $this->getRandomUserAgent(),
                        'timestamp' => $createdAt->toISOString(),
                    ]),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
                
                $activityCount++;
                
                // Insert in batches of 1000
                if (count($activities) >= 1000) {
                    DB::table('activity_log')->insert($activities);
                    $activities = [];
                }
            }
        }
        
        // Insert remaining activities
        if (!empty($activities)) {
            DB::table('activity_log')->insert($activities);
        }
        
        $this->command->info("   - Created {$activityCount} activity log entries");
    }

    /**
     * Get random user agent
     */
    private function getRandomUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15',
            'Mozilla/5.0 (Android 11; Mobile; rv:89.0) Gecko/89.0 Firefox/89.0',
        ];
        
        return $userAgents[array_rand($userAgents)];
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
            'Firefox on Android',
        ];
        
        return $devices[array_rand($devices)];
    }
}
