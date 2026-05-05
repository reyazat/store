<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $environment = app()->environment();
        
        $this->command->info("🌱 Seeding database for environment: {$environment}");
        $this->command->newLine();

        // Always seed IAM core components
        $this->command->info('📦 Seeding IAM core components...');
        $this->call([
            IamPermissionsSeeder::class,  // IAM built-in permissions
            SuperAdminSeeder::class,       // Super Admin user
        ]);
        $this->command->newLine();

        if ($environment === 'local' || $environment === 'development') {
            // Development environment - comprehensive seeding
            $this->command->info('🔧 Development mode: Seeding test data...');
            $this->call([
                DevelopmentSeeder::class,
                EcommerceSeeder::class,      // E-commerce demo data
            ]);
        } else {
            // Production/staging environment - minimal seeding
            $this->command->info('🏭 Production mode: Minimal seeding...');
            $this->call([
                OrgRolePermissionSeeder::class,
                UserSeeder::class,
                ProductionSeeder::class,     // Production-ready data
            ]);
        }

        $this->command->newLine();
        $this->command->info('✅ Database seeding completed!');
    }
}
