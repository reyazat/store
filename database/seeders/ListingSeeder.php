<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ListingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏪 Seeding product listings...');

        // Get vendors
        $digikala = Vendor::where('slug', 'digikala')->first();
        $mihanStore = Vendor::where('slug', 'mihan-store')->first();
        $snappMarket = Vendor::where('slug', 'snapp-market')->first();
        $technolife = Vendor::where('slug', 'technolife')->first();
        $bamilo = Vendor::where('slug', 'bamilo')->first();

        // Get products
        $iphone = Product::where('slug', 'iphone-15-pro')->first();
        $samsung = Product::where('slug', 'samsung-galaxy-s24-ultra')->first();
        $macbook = Product::where('slug', 'macbook-pro-14')->first();
        $nike = Product::where('slug', 'nike-air-max-90')->first();
        $adidas = Product::where('slug', 'adidas-ultraboost-22')->first();
        $sony = Product::where('slug', 'sony-wh-1000xm5')->first();

        $listings = [];

        // iPhone listings
        if ($iphone && $digikala && $mihanStore) {
            $listings = array_merge($listings, [
                [
                    'product_id' => $iphone->id,
                    'vendor_id' => $digikala->id,
                    'status' => 'active',
                    'base_price' => 45000000, // 45,000,000 Toman
                    'min_order_qty' => 1,
                ],
                [
                    'product_id' => $iphone->id,
                    'vendor_id' => $mihanStore->id,
                    'status' => 'active',
                    'base_price' => 44500000, // 44,500,000 Toman
                    'min_order_qty' => 1,
                ],
            ]);
        }

        // Samsung listings
        if ($samsung && $digikala && $technolife) {
            $listings = array_merge($listings, [
                [
                    'product_id' => $samsung->id,
                    'vendor_id' => $digikala->id,
                    'status' => 'active',
                    'base_price' => 38000000, // 38,000,000 Toman
                    'min_order_qty' => 1,
                ],
                [
                    'product_id' => $samsung->id,
                    'vendor_id' => $technolife->id,
                    'status' => 'active',
                    'base_price' => 37500000, // 37,500,000 Toman
                    'min_order_qty' => 1,
                ],
            ]);
        }

        // MacBook listings
        if ($macbook && $technolife && $mihanStore) {
            $listings = array_merge($listings, [
                [
                    'product_id' => $macbook->id,
                    'vendor_id' => $technolife->id,
                    'status' => 'active',
                    'base_price' => 125000000, // 125,000,000 Toman
                    'min_order_qty' => 1,
                ],
                [
                    'product_id' => $macbook->id,
                    'vendor_id' => $mihanStore->id,
                    'status' => 'active',
                    'base_price' => 123000000, // 123,000,000 Toman
                    'min_order_qty' => 1,
                ],
            ]);
        }

        // Nike listings
        if ($nike && $digikala && $snappMarket && $bamilo) {
            $listings = array_merge($listings, [
                [
                    'product_id' => $nike->id,
                    'vendor_id' => $digikala->id,
                    'status' => 'active',
                    'base_price' => 8500000, // 8,500,000 Toman
                    'min_order_qty' => 1,
                ],
                [
                    'product_id' => $nike->id,
                    'vendor_id' => $snappMarket->id,
                    'status' => 'active',
                    'base_price' => 8200000, // 8,200,000 Toman
                    'min_order_qty' => 1,
                ],
                [
                    'product_id' => $nike->id,
                    'vendor_id' => $bamilo->id,
                    'status' => 'active',
                    'base_price' => 8400000, // 8,400,000 Toman
                    'min_order_qty' => 1,
                ],
            ]);
        }

        // Adidas listings
        if ($adidas && $digikala && $snappMarket) {
            $listings = array_merge($listings, [
                [
                    'product_id' => $adidas->id,
                    'vendor_id' => $digikala->id,
                    'status' => 'active',
                    'base_price' => 9200000, // 9,200,000 Toman
                    'min_order_qty' => 1,
                ],
                [
                    'product_id' => $adidas->id,
                    'vendor_id' => $snappMarket->id,
                    'status' => 'active',
                    'base_price' => 8900000, // 8,900,000 Toman
                    'min_order_qty' => 1,
                ],
            ]);
        }

        // Sony listings
        if ($sony && $technolife && $digikala) {
            $listings = array_merge($listings, [
                [
                    'product_id' => $sony->id,
                    'vendor_id' => $technolife->id,
                    'status' => 'active',
                    'base_price' => 12500000, // 12,500,000 Toman
                    'min_order_qty' => 1,
                ],
                [
                    'product_id' => $sony->id,
                    'vendor_id' => $digikala->id,
                    'status' => 'active',
                    'base_price' => 12800000, // 12,800,000 Toman
                    'min_order_qty' => 1,
                ],
            ]);
        }

        foreach ($listings as $listingData) {
            $listing = Listing::firstOrCreate([
                'product_id' => $listingData['product_id'],
                'vendor_id' => $listingData['vendor_id']
            ], $listingData);
            
            // Get product and vendor names for display
            $productName = Product::find($listingData['product_id'])->name;
            $vendorName = Vendor::find($listingData['vendor_id'])->name;
            $this->command->line("   ✓ Created listing: {$productName} at {$vendorName}");
        }

        $this->command->info('✅ Product listings seeded successfully!');
    }
}
