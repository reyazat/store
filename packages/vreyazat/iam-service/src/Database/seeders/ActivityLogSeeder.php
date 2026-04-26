<?php

namespace Database\Seeders;

use IamApi\Models\Organization;
use IamApi\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class ActivityLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📊 Seeding activity logs...');

        DB::transaction(function () {
            $users = User::limit(10)->get();
            $organizations = Organization::limit(3)->get();

            foreach ($users as $user) {
                // User registration activity
                $this->createActivity([
                    'log_name' => 'user',
                    'description' => 'User registered',
                    'subject_type' => User::class,
                    'subject_id' => $user->id,
                    'causer_type' => User::class,
                    'causer_id' => $user->id,
                    'properties' => [
                        'attributes' => [
                            'name' => $user->name,
                            'email' => $user->email,
                            'status' => $user->status,
                        ],
                        'ip' => $this->getRandomIp(),
                        'user_agent' => $this->getRandomUserAgent(),
                    ],
                    'created_at' => $user->created_at,
                ]);

                // Login activities
                for ($i = 0; $i < rand(3, 8); $i++) {
                    $loginTime = $user->created_at->addDays(rand(0, 30))->addHours(rand(0, 23));
                    
                    $this->createActivity([
                        'log_name' => 'authentication',
                        'description' => rand(0, 9) > 0 ? 'User logged in' : 'Failed login attempt',
                        'subject_type' => User::class,
                        'subject_id' => $user->id,
                        'causer_type' => User::class,
                        'causer_id' => $user->id,
                        'properties' => [
                            'ip' => $this->getRandomIp(),
                            'user_agent' => $this->getRandomUserAgent(),
                            'success' => rand(0, 9) > 0,
                            'method' => 'password',
                        ],
                        'created_at' => $loginTime,
                    ]);
                }

                // Profile update activities
                if (rand(0, 1)) {
                    $this->createActivity([
                        'log_name' => 'user',
                        'description' => 'Profile updated',
                        'subject_type' => User::class,
                        'subject_id' => $user->id,
                        'causer_type' => User::class,
                        'causer_id' => $user->id,
                        'properties' => [
                            'attributes' => [
                                'name' => $user->name,
                                'phone' => $user->phone,
                            ],
                            'old' => [
                                'name' => 'Old Name',
                                'phone' => '+1-555-0000',
                            ],
                            'ip' => $this->getRandomIp(),
                            'user_agent' => $this->getRandomUserAgent(),
                        ],
                        'created_at' => $user->updated_at,
                    ]);
                }

                // Password change activities
                if (rand(0, 2) === 0) {
                    $this->createActivity([
                        'log_name' => 'security',
                        'description' => 'Password changed',
                        'subject_type' => User::class,
                        'subject_id' => $user->id,
                        'causer_type' => User::class,
                        'causer_id' => $user->id,
                        'properties' => [
                            'ip' => $this->getRandomIp(),
                            'user_agent' => $this->getRandomUserAgent(),
                            'forced' => false,
                        ],
                        'created_at' => $user->password_changed_at ?? $user->created_at->addDays(rand(1, 10)),
                    ]);
                }

                // MFA activities
                if ($user->mfa_enabled) {
                    $this->createActivity([
                        'log_name' => 'security',
                        'description' => 'MFA enabled',
                        'subject_type' => User::class,
                        'subject_id' => $user->id,
                        'causer_type' => User::class,
                        'causer_id' => $user->id,
                        'properties' => [
                            'method' => 'totp',
                            'ip' => $this->getRandomIp(),
                            'user_agent' => $this->getRandomUserAgent(),
                        ],
                        'created_at' => $user->created_at->addDays(rand(1, 5)),
                    ]);

                    // MFA verification activities
                    for ($i = 0; $i < rand(2, 5); $i++) {
                        $this->createActivity([
                            'log_name' => 'authentication',
                            'description' => 'MFA verification',
                            'subject_type' => User::class,
                            'subject_id' => $user->id,
                            'causer_type' => User::class,
                            'causer_id' => $user->id,
                            'properties' => [
                                'method' => 'totp',
                                'success' => rand(0, 9) > 0,
                                'ip' => $this->getRandomIp(),
                                'user_agent' => $this->getRandomUserAgent(),
                            ],
                            'created_at' => $user->created_at->addDays(rand(5, 30)),
                        ]);
                    }
                }

                // Organization activities
                foreach ($organizations as $org) {
                    if (rand(0, 2) === 0) {
                        $this->createActivity([
                            'log_name' => 'organization',
                            'description' => 'User joined organization',
                            'subject_type' => Organization::class,
                            'subject_id' => $org->id,
                            'causer_type' => User::class,
                            'causer_id' => $user->id,
                            'properties' => [
                                'organization_name' => $org->name,
                                'role' => 'member',
                                'invited_by' => 'admin@' . strtolower(str_replace(' ', '', $org->name)) . '.local',
                            ],
                            'created_at' => $user->created_at->addDays(rand(1, 15)),
                        ]);
                    }
                }
            }

            // Admin activities - get users who have admin or owner roles
            // In single organization mode, we check roles directly via Spatie Permission
            try {
                $adminUsers = User::role(['admin', 'owner', 'super-admin'])
                    ->limit(5)
                    ->get();
            } catch (\Exception $e) {
                // If roles don't exist yet, use super admin users or skip
                $adminUsers = User::where('is_super_admin', true)->limit(5)->get();
            }

            foreach ($adminUsers as $admin) {
                // User management activities
                for ($i = 0; $i < rand(5, 15); $i++) {
                    $targetUser = $users->random();
                    
                    $activities = [
                        'User account locked',
                        'User account unlocked',
                        'User role assigned',
                        'User role removed',
                        'User password reset',
                        'User account activated',
                        'User account deactivated',
                    ];

                    $this->createActivity([
                        'log_name' => 'admin',
                        'description' => $activities[array_rand($activities)],
                        'subject_type' => User::class,
                        'subject_id' => $targetUser->id,
                        'causer_type' => User::class,
                        'causer_id' => $admin->id,
                        'properties' => [
                            'target_user' => $targetUser->email,
                            'admin_user' => $admin->email,
                            'ip' => $this->getRandomIp(),
                            'user_agent' => $this->getRandomUserAgent(),
                        ],
                        'created_at' => now()->subDays(rand(0, 30)),
                    ]);
                }

                // Organization management activities
                foreach ($organizations as $org) {
                    if (rand(0, 1)) {
                        $this->createActivity([
                            'log_name' => 'organization',
                            'description' => 'Organization settings updated',
                            'subject_type' => Organization::class,
                            'subject_id' => $org->id,
                            'causer_type' => User::class,
                            'causer_id' => $admin->id,
                            'properties' => [
                                'organization_name' => $org->name,
                                'changes' => [
                                    'mfa_required' => $org->settings['features']['mfa_required'] ?? false,
                                    'password_expiry_days' => $org->settings['features']['password_expiry_days'] ?? 90,
                                ],
                                'ip' => $this->getRandomIp(),
                                'user_agent' => $this->getRandomUserAgent(),
                            ],
                            'created_at' => now()->subDays(rand(0, 15)),
                        ]);
                    }
                }
            }

            // System activities
            $systemActivities = [
                'System backup completed',
                'Database maintenance performed',
                'Security scan completed',
                'Log rotation performed',
                'Cache cleared',
                'Configuration updated',
            ];

            foreach ($systemActivities as $activity) {
                $this->createActivity([
                    'log_name' => 'system',
                    'description' => $activity,
                    'subject_type' => null,
                    'subject_id' => null,
                    'causer_type' => null,
                    'causer_id' => null,
                    'properties' => [
                        'automated' => true,
                        'timestamp' => now()->subDays(rand(0, 7))->toISOString(),
                    ],
                    'created_at' => now()->subDays(rand(0, 7)),
                ]);
            }

            $activityCount = Activity::count();
            $userActivities = Activity::where('log_name', 'user')->count();
            $authActivities = Activity::where('log_name', 'authentication')->count();
            $securityActivities = Activity::where('log_name', 'security')->count();
            $adminActivities = Activity::where('log_name', 'admin')->count();
            $orgActivities = Activity::where('log_name', 'organization')->count();
            $systemActivities = Activity::where('log_name', 'system')->count();

            $this->command->info("✅ Created activity logs:");
            $this->command->line("   - Total activities: {$activityCount}");
            $this->command->line("   - User activities: {$userActivities}");
            $this->command->line("   - Authentication activities: {$authActivities}");
            $this->command->line("   - Security activities: {$securityActivities}");
            $this->command->line("   - Admin activities: {$adminActivities}");
            $this->command->line("   - Organization activities: {$orgActivities}");
            $this->command->line("   - System activities: {$systemActivities}");
        });
    }

    /**
     * Create an activity log entry
     */
    private function createActivity(array $data): void
    {
        Activity::create([
            'log_name' => $data['log_name'],
            'description' => $data['description'],
            'subject_type' => $data['subject_type'],
            'subject_id' => $data['subject_id'],
            'causer_type' => $data['causer_type'],
            'causer_id' => $data['causer_id'],
            'properties' => $data['properties'],
            'created_at' => $data['created_at'],
            'updated_at' => $data['created_at'],
        ]);
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
     * Get random user agent
     */
    private function getRandomUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Android 11; Mobile; rv:89.0) Gecko/89.0 Firefox/89.0',
            'IAM-Mobile-App/1.0.0 (iOS 14.6; iPhone13,2)',
            'IAM-Mobile-App/1.0.0 (Android 11; SM-G991B)',
        ];

        return $userAgents[array_rand($userAgents)];
    }
}
