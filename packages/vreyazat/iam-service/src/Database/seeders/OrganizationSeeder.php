<?php

namespace Database\Seeders;

use IamApi\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates test organizations for development environment.
     * Owners will be assigned by TestUsersSeeder.
     */
    public function run(): void
    {
        $this->command->info('🏢 Seeding test organizations...');

        DB::transaction(function () {
            // Create main development organization
            $mainOrg = Organization::query()->create([
                'name' => 'Acme Corporation',
            ]);

            // Create subsidiary organization
            $subsidiaryOrg = Organization::query()->create([
                'name' => 'Tech Innovations Ltd',
            ]);

            // Create small startup organization
            $startupOrg = Organization::query()->create([
                'name' => 'StartupXYZ',
            ]);

            // Create inactive organization for testing
            $inactiveOrg = Organization::query()->create([
                'name' => 'Inactive Corp',
            ]);

            $this->command->info("✅ Created test organizations:");
            $this->command->line("   - $mainOrg->name (ID: $mainOrg->id)");
            $this->command->line("   - $subsidiaryOrg->name (ID: $subsidiaryOrg->id)");
            $this->command->line("   - $startupOrg->name (ID: $startupOrg->id)");
            $this->command->line("   - $inactiveOrg->name (ID: $inactiveOrg->id)");
            $this->command->newLine();
            $this->command->line("   Note: Users will be assigned to these organizations by TestUsersSeeder");
        });
    }
}
