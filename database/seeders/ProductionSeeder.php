<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Production Seeder - Only for PRODUCTION environment
 * Creates minimal, professional data with real product images from Unsplash
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏭 Running PRODUCTION seeders...');
        $this->command->newLine();

        // Run only essential seeders with minimal data
        $this->call([
            // System essentials (always needed)
            OrderStatusSeeder::class,        // Order statuses
            PaymentMethodSeeder::class,      // Payment methods (disabled by default)
            TaxClassSeeder::class,           // Tax classes
            SiteSettingSeeder::class,        // Site settings
            NavLinkSeeder::class,            // Navigation links

            // Minimal production data
            ProductionCategorySeeder::class,    // 5-6 main categories with real images
            ProductionManufacturerSeeder::class, // 4-5 known manufacturers
            ProductionProductSeeder::class,     // 8-12 real products with Unsplash images
            SliderSeeder::class,                // Homepage sliders with online images
            ProductionSiteContentSeeder::class, // Homepage banners/features
        ]);

        $this->command->newLine();
        $this->command->info('✅ Production seeding completed!');
        $this->command->warn('⚠️  Remember to:');
        $this->command->line('   1. Configure payment methods in admin panel');
        $this->command->line('   2. Update site settings (logo, contact info)');
        $this->command->line('   3. Set actual product prices and inventory');
        $this->command->line('   4. Review and activate payment methods');
    }
}
