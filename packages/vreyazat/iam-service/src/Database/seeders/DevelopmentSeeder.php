<?php

namespace IamApi\Database\Seeders;

use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    /**
     * Seed development data (IAM package scope).
     * 
     * Order matters:
     * 1. OrganizationSeeder — creates orgs + owners (triggers OrganizationCreated event → roles/permissions)
     * 2. TestUsersSeeder — adds members to existing orgs via OrganizationService
     * 3. RbacDemoSeeder — creates dedicated demo org with all role types
     * 4. Auxiliary seeders — password history, JWT, OTP, MFA, activity logs
     * 
     * Note: OrgRolePermissionSeeder is NOT needed here because OrganizationCreated event
     * automatically creates roles/permissions for each new organization.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding IAM development data...');

        // Organizations + Owners (creates roles/permissions via event)
        $this->call([
            OrganizationSeeder::class,
            TestUsersSeeder::class,
            RbacDemoSeeder::class,
        ]);

        // Auxiliary test data
        $this->call([
            PasswordHistorySeeder::class,
            JwtTokenSeeder::class,
            OtpSeeder::class,
            MfaSeeder::class,
            ActivityLogSeeder::class,
        ]);

        $this->command->info('✅ IAM development seeding completed!');
    }
}
