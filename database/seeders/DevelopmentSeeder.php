<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    /**
     * Seed the application's database for development environment.
     * 
     * Order:
     * 1. NavLinkSeeder — site navigation content
     * 2. OrganizationSeeder — creates orgs + owners (triggers OrganizationCreated → roles/permissions)
     * 3. TestUsersSeeder — adds store members & buyers via OrganizationService
     * 4. RbacDemoSeeder — dedicated demo organization for RBAC testing
     * 5. Auxiliary seeders — password history, JWT, OTP, MFA, activity logs
     * 
     * Note: OrgRolePermissionSeeder is NOT called because OrganizationCreated event
     * auto-creates roles/permissions for each new organization.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding development data...');

        // Site content
        $this->call([
            NavLinkSeeder::class,
        ]);

        // IAM: Organizations + Users (Layer 1 + Layer 2 sync via OrganizationService)
        $this->call([
            OrganizationSeeder::class,
            TestUsersSeeder::class,
            RbacDemoSeeder::class,
        ]);

        // Auxiliary IAM test data
        $this->call([
            PasswordHistorySeeder::class,
            JwtTokenSeeder::class,
            OtpSeeder::class,
            MfaSeeder::class,
            ActivityLogSeeder::class,
        ]);

        $this->command->info('✅ Development seeding completed!');
    }
}
