<?php

namespace Database\Seeders;

use IamApi\Models\Organization;
use IamApi\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('👥 Seeding test users...');

        DB::transaction(function () {
            $acmeOrg = Organization::where('name', 'Acme Corporation')->first();
            $techOrg = Organization::where('name', 'Tech Innovations Ltd')->first();
            $startupOrg = Organization::where('name', 'StartupXYZ')->first();

            // Create regular users for different scenarios
            $users = [
                // Active users
                [
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com',
                    'password' => 'User123!',
                    'phone' => '+1-555-1001',
                    'status' => 'active',
                    'email_verified' => true,
                    'phone_verified' => true,
                    'mfa_enabled' => false,
                    'must_change_password' => false,
                    'last_login' => now()->subHours(3),
                    'organization' => $acmeOrg,
                    'org_role' => 'member',
                ],
                [
                    'name' => 'Jane Smith',
                    'email' => 'jane.smith@example.com',
                    'password' => 'User123!',
                    'phone' => '+1-555-1002',
                    'status' => 'active',
                    'email_verified' => true,
                    'phone_verified' => false,
                    'mfa_enabled' => false,
                    'must_change_password' => false,
                    'last_login' => now()->subHours(1),
                    'organization' => $acmeOrg,
                    'org_role' => 'admin',
                ],
                [
                    'name' => 'Bob Johnson',
                    'email' => 'bob.johnson@example.com',
                    'password' => 'User123!',
                    'phone' => '+1-555-1003',
                    'status' => 'active',
                    'email_verified' => true,
                    'phone_verified' => true,
                    'mfa_enabled' => true,
                    'must_change_password' => false,
                    'last_login' => now()->subMinutes(30),
                    'organization' => $techOrg,
                    'org_role' => 'admin',
                ],
                [
                    'name' => 'Alice Wilson',
                    'email' => 'alice.wilson@example.com',
                    'password' => 'User123!',
                    'phone' => '+1-555-1004',
                    'status' => 'active',
                    'email_verified' => true,
                    'phone_verified' => true,
                    'mfa_enabled' => false,
                    'must_change_password' => false,
                    'last_login' => now()->subMinutes(10),
                    'organization' => $startupOrg,
                    'org_role' => 'member',
                ],
                // User with expired password
                [
                    'name' => 'Charlie Brown',
                    'email' => 'charlie.brown@example.com',
                    'password' => 'User123!',
                    'phone' => '+1-555-1005',
                    'status' => 'active',
                    'email_verified' => true,
                    'phone_verified' => false,
                    'mfa_enabled' => false,
                    'must_change_password' => true,
                    'last_login' => now()->subDays(5),
                    'organization' => $acmeOrg,
                    'org_role' => 'member',
                    'password_expired' => true,
                ],
                // Unverified user
                [
                    'name' => 'Diana Prince',
                    'email' => 'diana.prince@example.com',
                    'password' => 'User123!',
                    'phone' => '+1-555-1006',
                    'status' => 'active',
                    'email_verified' => false,
                    'phone_verified' => false,
                    'mfa_enabled' => false,
                    'must_change_password' => false,
                    'last_login' => null,
                    'organization' => $techOrg,
                    'org_role' => 'member',
                ],
                // Locked user
                [
                    'name' => 'Eve Adams',
                    'email' => 'eve.adams@example.com',
                    'password' => 'User123!',
                    'phone' => '+1-555-1007',
                    'status' => 'active',
                    'email_verified' => true,
                    'phone_verified' => true,
                    'mfa_enabled' => false,
                    'must_change_password' => false,
                    'last_login' => now()->subDays(2),
                    'organization' => $acmeOrg,
                    'org_role' => 'member',
                    'locked' => true,
                ],
                // Inactive user
                [
                    'name' => 'Frank Miller',
                    'email' => 'frank.miller@example.com',
                    'password' => 'User123!',
                    'phone' => '+1-555-1008',
                    'status' => 'inactive',
                    'email_verified' => true,
                    'phone_verified' => true,
                    'mfa_enabled' => false,
                    'must_change_password' => false,
                    'last_login' => now()->subWeeks(2),
                    'organization' => $startupOrg,
                    'org_role' => 'member',
                ],
            ];

            foreach ($users as $userData) {
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'email_verified_at' => $userData['email_verified'] ? now() : null,
                    'password' => Hash::make($userData['password']),
                    'phone' => $userData['phone'],
                    'phone_verified_at' => $userData['phone_verified'] ? now() : null,
                    'status' => $userData['status'],
                    'must_change_password' => $userData['must_change_password'],
                    'password_changed_at' => now()->subDays($userData['password_expired'] ?? false ? 100 : 10),
                    'password_expires_at' => $userData['password_expired'] ?? false
                        ? now()->subDays(10)
                        : now()->addDays(90),
                    'last_login_at' => $userData['last_login'],
                    'last_login_ip' => $userData['last_login'] ? '192.168.1.' . rand(100, 200) : null,
                    'failed_login_attempts' => $userData['locked'] ?? false ? 10 : rand(0, 2),
                    'locked_until' => $userData['locked'] ?? false ? now()->addHours(2) : null,
                    'mfa_enabled' => $userData['mfa_enabled'],
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now()->subDays(rand(0, 5)),
                ]);

                // Add to organization (set organization_id directly)
                if ($userData['organization']) {
                    $user->update([
                        'organization_id' => $userData['organization']->id,
                    ]);
                }

                $this->command->line("   - Created user: {$userData['name']} ({$userData['email']})");
            }

            // Create some users without organizations
            $independentUsers = [
                [
                    'name' => 'Grace Hopper',
                    'email' => 'grace.hopper@example.com',
                    'password' => 'User123!',
                    'phone' => '+1-555-2001',
                    'status' => 'active',
                ],
                [
                    'name' => 'Alan Turing',
                    'email' => 'alan.turing@example.com',
                    'password' => 'User123!',
                    'phone' => '+1-555-2002',
                    'status' => 'active',
                ],
            ];

            foreach ($independentUsers as $userData) {
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'email_verified_at' => now(),
                    'password' => Hash::make($userData['password']),
                    'phone' => $userData['phone'],
                    'phone_verified_at' => now(),
                    'status' => $userData['status'],
                    'must_change_password' => false,
                    'password_changed_at' => now()->subDays(5),
                    'password_expires_at' => now()->addDays(90),
                    'last_login_at' => now()->subHours(rand(1, 24)),
                    'last_login_ip' => '192.168.1.' . rand(100, 200),
                    'failed_login_attempts' => 0,
                    'locked_until' => null,
                    'mfa_enabled' => false,
                    'created_at' => now()->subDays(rand(1, 15)),
                    'updated_at' => now()->subDays(rand(0, 3)),
                ]);

                $this->command->line("   - Created independent user: {$userData['name']} ({$userData['email']})");
            }

            $this->command->info("✅ Created test users with various scenarios:");
            $this->command->line("   - Active users with different MFA settings");
            $this->command->line("   - User with expired password (must change)");
            $this->command->line("   - Unverified user (email not verified)");
            $this->command->line("   - Locked user (failed login attempts)");
            $this->command->line("   - Inactive user");
            $this->command->line("   - Independent users (no organization)");
            $this->command->line("   - Default password for all test users: User123!");
        });
    }
}
