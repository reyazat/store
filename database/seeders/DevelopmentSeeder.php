<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    /**
     * Seed the application's database for development environment.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding development data...');

        // Core system data (always needed)
        $this->call([
            OrgRolePermissionSeeder::class,
        ]);

        // Development-specific data
        $this->call([
            OrganizationSeeder::class,
            TestUsersSeeder::class,
            // RbacDemoSeeder::class,  // Disabled - has guard mismatch issues
            PasswordHistorySeeder::class,
            JwtTokenSeeder::class,
            OtpSeeder::class,
            MfaSeeder::class,
            ActivityLogSeeder::class,
        ]);

        $this->command->info('✅ Development seeding completed!');
    }
}
