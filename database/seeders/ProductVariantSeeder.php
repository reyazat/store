<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📦 Seeding product variants...');

        // Get products
        $iphone = Product::where('slug', 'iphone-15-pro')->first();
        $samsung = Product::where('slug', 'samsung-galaxy-s24-ultra')->first();
        $macbook = Product::where('slug', 'macbook-pro-14')->first();
        $nike = Product::where('slug', 'nike-air-max-90')->first();
        $adidas = Product::where('slug', 'adidas-ultraboost-22')->first();
        $sony = Product::where('slug', 'sony-wh-1000xm5')->first();

        $variants = [];

        // iPhone variants
        if ($iphone) {
            $variants = array_merge($variants, [
                [
                    'product_id' => $iphone->id,
                    'sku' => 'IPHONE15PRO128BK',
                    'barcode' => '194253841234',
                    'weight' => 187,
                    'length' => 146.6,
                    'width' => 70.6,
                    'height' => 8.25,
                    'status' => 1,
                ],
                [
                    'product_id' => $iphone->id,
                    'sku' => 'IPHONE15PRO256BK',
                    'barcode' => '194253841235',
                    'weight' => 187,
                    'length' => 146.6,
                    'width' => 70.6,
                    'height' => 8.25,
                    'status' => 1,
                ],
                [
                    'product_id' => $iphone->id,
                    'sku' => 'IPHONE15PRO512BK',
                    'barcode' => '194253841236',
                    'weight' => 187,
                    'length' => 146.6,
                    'width' => 70.6,
                    'height' => 8.25,
                    'status' => 1,
                ],
                [
                    'product_id' => $iphone->id,
                    'sku' => 'IPHONE15PRO128WH',
                    'barcode' => '194253841237',
                    'weight' => 187,
                    'length' => 146.6,
                    'width' => 70.6,
                    'height' => 8.25,
                    'status' => 1,
                ],
                [
                    'product_id' => $iphone->id,
                    'sku' => 'IPHONE15PRO128BL',
                    'barcode' => '194253841238',
                    'weight' => 187,
                    'length' => 146.6,
                    'width' => 70.6,
                    'height' => 8.25,
                    'status' => 1,
                ],
            ]);
        }

        // Samsung variants
        if ($samsung) {
            $variants = array_merge($variants, [
                [
                    'product_id' => $samsung->id,
                    'sku' => 'S24U256BK',
                    'barcode' => '8801643871234',
                    'weight' => 232,
                    'length' => 162.3,
                    'width' => 79.0,
                    'height' => 8.9,
                    'status' => 1,
                ],
                [
                    'product_id' => $samsung->id,
                    'sku' => 'S24U512BK',
                    'barcode' => '8801643871235',
                    'weight' => 232,
                    'length' => 162.3,
                    'width' => 79.0,
                    'height' => 8.9,
                    'status' => 1,
                ],
                [
                    'product_id' => $samsung->id,
                    'sku' => 'S24U1TBBK',
                    'barcode' => '8801643871236',
                    'weight' => 232,
                    'length' => 162.3,
                    'width' => 79.0,
                    'height' => 8.9,
                    'status' => 1,
                ],
                [
                    'product_id' => $samsung->id,
                    'sku' => 'S24U256WH',
                    'barcode' => '8801643871237',
                    'weight' => 232,
                    'length' => 162.3,
                    'width' => 79.0,
                    'height' => 8.9,
                    'status' => 1,
                ],
            ]);
        }

        // MacBook variants
        if ($macbook) {
            $variants = array_merge($variants, [
                [
                    'product_id' => $macbook->id,
                    'sku' => 'MBP14M3SSD512',
                    'barcode' => '194253812345',
                    'weight' => 1550,
                    'length' => 311.5,
                    'width' => 220.4,
                    'height' => 15.5,
                    'status' => 1,
                ],
                [
                    'product_id' => $macbook->id,
                    'sku' => 'MBP14M3SSD1TB',
                    'barcode' => '194253812346',
                    'weight' => 1550,
                    'length' => 311.5,
                    'width' => 220.4,
                    'height' => 15.5,
                    'status' => 1,
                ],
                [
                    'product_id' => $macbook->id,
                    'sku' => 'MBP14M3PROSSD1TB',
                    'barcode' => '194253812347',
                    'weight' => 1550,
                    'length' => 311.5,
                    'width' => 220.4,
                    'height' => 15.5,
                    'status' => 1,
                ],
                [
                    'product_id' => $macbook->id,
                    'sku' => 'MBP14M3PROSSD2TB',
                    'barcode' => '194253812348',
                    'weight' => 1550,
                    'length' => 311.5,
                    'width' => 220.4,
                    'height' => 15.5,
                    'status' => 1,
                ],
            ]);
        }

        // Nike variants
        if ($nike) {
            $variants = array_merge($variants, [
                [
                    'product_id' => $nike->id,
                    'sku' => 'NIKEAM90S7WH',
                    'barcode' => '886611312345',
                    'weight' => 325,
                    'status' => 1,
                ],
                [
                    'product_id' => $nike->id,
                    'sku' => 'NIKEAM90S8WH',
                    'barcode' => '886611312346',
                    'weight' => 340,
                    'status' => 1,
                ],
                [
                    'product_id' => $nike->id,
                    'sku' => 'NIKEAM90S9WH',
                    'barcode' => '886611312347',
                    'weight' => 355,
                    'status' => 1,
                ],
                [
                    'product_id' => $nike->id,
                    'sku' => 'NIKEAM90S10WH',
                    'barcode' => '886611312348',
                    'weight' => 370,
                    'status' => 1,
                ],
                [
                    'product_id' => $nike->id,
                    'sku' => 'NIKEAM90S11WH',
                    'barcode' => '886611312349',
                    'weight' => 385,
                    'status' => 1,
                ],
                [
                    'product_id' => $nike->id,
                    'sku' => 'NIKEAM90S7BK',
                    'barcode' => '886611312350',
                    'weight' => 325,
                    'status' => 1,
                ],
                [
                    'product_id' => $nike->id,
                    'sku' => 'NIKEAM90S8BK',
                    'barcode' => '886611312351',
                    'weight' => 340,
                    'status' => 1,
                ],
                [
                    'product_id' => $nike->id,
                    'sku' => 'NIKEAM90S9BK',
                    'barcode' => '886611312352',
                    'weight' => 355,
                    'status' => 1,
                ],
                [
                    'product_id' => $nike->id,
                    'sku' => 'NIKEAM90S10BK',
                    'barcode' => '886611312353',
                    'weight' => 370,
                    'status' => 1,
                ],
            ]);
        }

        // Adidas variants
        if ($adidas) {
            $variants = array_merge($variants, [
                [
                    'product_id' => $adidas->id,
                    'sku' => 'ADIDASUB22S7BK',
                    'barcode' => '406424812345',
                    'weight' => 311,
                    'status' => 1,
                ],
                [
                    'product_id' => $adidas->id,
                    'sku' => 'ADIDASUB22S8BK',
                    'barcode' => '406424812346',
                    'weight' => 322,
                    'status' => 1,
                ],
                [
                    'product_id' => $adidas->id,
                    'sku' => 'ADIDASUB22S9BK',
                    'barcode' => '406424812347',
                    'weight' => 340,
                    'status' => 1,
                ],
                [
                    'product_id' => $adidas->id,
                    'sku' => 'ADIDASUB22S10BK',
                    'barcode' => '406424812348',
                    'weight' => 356,
                    'status' => 1,
                ],
                [
                    'product_id' => $adidas->id,
                    'sku' => 'ADIDASUB22S11BK',
                    'barcode' => '406424812349',
                    'weight' => 371,
                    'status' => 1,
                ],
                [
                    'product_id' => $adidas->id,
                    'sku' => 'ADIDASUB22S7WH',
                    'barcode' => '406424812350',
                    'weight' => 311,
                    'status' => 1,
                ],
                [
                    'product_id' => $adidas->id,
                    'sku' => 'ADIDASUB22S8WH',
                    'barcode' => '406424812351',
                    'weight' => 322,
                    'status' => 1,
                ],
                [
                    'product_id' => $adidas->id,
                    'sku' => 'ADIDASUB22S9WH',
                    'barcode' => '406424812352',
                    'weight' => 340,
                    'status' => 1,
                ],
            ]);
        }

        // Sony variants
        if ($sony) {
            $variants = array_merge($variants, [
                [
                    'product_id' => $sony->id,
                    'sku' => 'SONYWH1000XM5BK',
                    'barcode' => '4548736012345',
                    'weight' => 250,
                    'status' => 1,
                ],
                [
                    'product_id' => $sony->id,
                    'sku' => 'SONYWH1000XM5WH',
                    'barcode' => '4548736012346',
                    'weight' => 250,
                    'status' => 1,
                ],
                [
                    'product_id' => $sony->id,
                    'sku' => 'SONYWH1000XM5BL',
                    'barcode' => '4548736012347',
                    'weight' => 250,
                    'status' => 1,
                ],
            ]);
        }

        foreach ($variants as $variantData) {
            $variant = ProductVariant::firstOrCreate(['sku' => $variantData['sku']], $variantData);
            
            // Get product name for display
            $productName = Product::find($variantData['product_id'])->name;
            $this->command->line("   ✓ Created variant: {$variant->sku} for {$productName}");
        }

        $this->command->info('✅ Product variants seeded successfully!');
    }
}
