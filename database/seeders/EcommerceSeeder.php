<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EcommerceSeeder extends Seeder
{
    /**
     * Seed the e-commerce data.
     */
    public function run(): void
    {
        $this->command->info('🛒 Seeding e-commerce data...');
        $this->command->newLine();

        // Run e-commerce seeders in correct order
        $this->call([
            OrderStatusSeeder::class,        // Order statuses (payment methods depend on these keys)
            PaymentMethodSeeder::class,      // Payment methods (card-to-card, Zarinpal)
            EcommerceUserSeeder::class,      // Users first (vendors depend on them)
            TaxClassSeeder::class,           // Tax classes first (products depend on them)
            CategorySeeder::class,           // Categories
            ManufacturerSeeder::class,       // Manufacturers
            VendorSeeder::class,             // Vendors
            ProductSeeder::class,            // Products (depend on categories, manufacturers, tax classes)
            OptionSeeder::class,             // Options (for variants)
            OptionValueSeeder::class,        // Option values
            OptionProductSeeder::class,      // Attach options to products
            ProductVariantSeeder::class,     // Product variants
            VariantOptionValueSeeder::class, // Variant option assignments
            ListingSeeder::class,            // Product listings (make products available)
            ListingVariantSeeder::class,     // Listing variants with stock and pricing
            SiteFeatureSeeder::class,        // Homepage services strip
            PromoBannerSeeder::class,        // Homepage promotional banners
        ]);

        $this->command->newLine();
        $this->command->info('✅ E-commerce data seeding completed!');
    }
}
