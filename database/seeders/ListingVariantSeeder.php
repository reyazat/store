<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ListingVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📦 Seeding listing variants...');

        // Get listings by product-vendor combinations
        $listings = Listing::with(['product', 'vendor'])->get();
        
        foreach ($listings as $listing) {
            // Get all variants for this product
            $variants = ProductVariant::where('product_id', $listing->product_id)->get();
            
            foreach ($variants as $variant) {
                // Determine price and stock based on product type and vendor
                $priceOverride = $this->calculateVariantPrice($listing, $variant);
                $stockQuantity = $this->generateStockQuantity($listing->vendor->slug, $variant->sku);
                
                // Check if this listing variant already exists
                $exists = DB::table('listing_variants')
                    ->where('listing_id', $listing->id)
                    ->where('variant_id', $variant->id)
                    ->exists();
                
                if (!$exists) {
                    DB::table('listing_variants')->insert([
                        'listing_id' => $listing->id,
                        'variant_id' => $variant->id,
                        'price_override' => $priceOverride,
                        'stock_quantity' => $stockQuantity,
                        'status' => 1, // Active
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    $this->command->line("   ✓ Added variant {$variant->sku} to {$listing->vendor->name} (Stock: {$stockQuantity}, Price: " . number_format($priceOverride) . ")");
                }
            }
        }

        $this->command->info('✅ Listing variants seeded successfully!');
    }
    
    /**
     * Calculate variant price based on base price and variant attributes
     */
    private function calculateVariantPrice($listing, $variant): float
    {
        $basePrice = $listing->base_price;
        $productName = strtolower($listing->product->name);
        
        // iPhone variants - price varies by storage
        if (str_contains($productName, 'iphone')) {
            if (str_contains($variant->sku, '256')) {
                return $basePrice * 1.15; // 256GB is 15% more
            } elseif (str_contains($variant->sku, '512')) {
                return $basePrice * 1.35; // 512GB is 35% more
            }
            return $basePrice; // 128GB base price
        }
        
        // Samsung variants - price varies by storage
        if (str_contains($productName, 'samsung')) {
            if (str_contains($variant->sku, '512')) {
                return $basePrice * 1.20;
            } elseif (str_contains($variant->sku, '1TB')) {
                return $basePrice * 1.40;
            }
            return $basePrice; // 256GB base price
        }
        
        // MacBook variants - price varies by storage and processor
        if (str_contains($productName, 'macbook')) {
            if (str_contains($variant->sku, '1TB')) {
                return $basePrice * 1.15;
            } elseif (str_contains($variant->sku, '2TB')) {
                return $basePrice * 1.30;
            } elseif (str_contains($variant->sku, 'PRO')) {
                return $basePrice * 1.25; // M3 Pro is 25% more than M3
            }
            return $basePrice; // 512GB M3 base price
        }
        
        // Shoe variants - price varies by size (larger sizes slightly more expensive)
        if (str_contains($productName, 'nike') || str_contains($productName, 'adidas')) {
            if (str_contains($variant->sku, '11')) {
                return $basePrice * 1.10;
            } elseif (str_contains($variant->sku, '10')) {
                return $basePrice * 1.05;
            }
            return $basePrice; // Size 7-9 base price
        }
        
        // Sony headphones - same price for all colors
        if (str_contains($productName, 'sony')) {
            return $basePrice;
        }
        
        return $basePrice;
    }
    
    /**
     * Generate realistic stock quantities based on vendor and variant
     */
    private function generateStockQuantity($vendorSlug, $variantSku): int
    {
        // Different vendors have different inventory levels
        switch ($vendorSlug) {
            case 'digikala':
                // Largest retailer - high stock
                return rand(50, 200);
                
            case 'technolife':
                // Tech specialist - medium stock
                return rand(20, 80);
                
            case 'mihan-store':
                // Medium retailer - variable stock
                return rand(10, 60);
                
            case 'snapp-market':
                // Online marketplace - lower stock
                return rand(5, 30);
                
            case 'bamilo':
                // Smaller retailer - low stock
                return rand(3, 20);
                
            default:
                return rand(10, 50);
        }
    }
}
