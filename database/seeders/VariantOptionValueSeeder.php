<?php

namespace Database\Seeders;

use App\Models\Option;
use App\Models\OptionValue;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VariantOptionValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔗 Seeding variant option values...');

        // Get options
        $colorOption = Option::where('slug', 'color')->first();
        $sizeOption = Option::where('slug', 'size')->first();
        $storageOption = Option::where('slug', 'storage')->first();

        // Get option values
        $blackColor = $colorOption ? OptionValue::where('option_id', $colorOption->id)->where('value', 'Black')->first() : null;
        $whiteColor = $colorOption ? OptionValue::where('option_id', $colorOption->id)->where('value', 'White')->first() : null;
        $blueColor = $colorOption ? OptionValue::where('option_id', $colorOption->id)->where('value', 'Blue')->first() : null;

        $size7 = $sizeOption ? OptionValue::where('option_id', $sizeOption->id)->where('value', '7')->first() : null;
        $size8 = $sizeOption ? OptionValue::where('option_id', $sizeOption->id)->where('value', '8')->first() : null;
        $size9 = $sizeOption ? OptionValue::where('option_id', $sizeOption->id)->where('value', '9')->first() : null;
        $size10 = $sizeOption ? OptionValue::where('option_id', $sizeOption->id)->where('value', '10')->first() : null;
        $size11 = $sizeOption ? OptionValue::where('option_id', $sizeOption->id)->where('value', '11')->first() : null;

        $storage128 = $storageOption ? OptionValue::where('option_id', $storageOption->id)->where('value', '128GB')->first() : null;
        $storage256 = $storageOption ? OptionValue::where('option_id', $storageOption->id)->where('value', '256GB')->first() : null;
        $storage512 = $storageOption ? OptionValue::where('option_id', $storageOption->id)->where('value', '512GB')->first() : null;
        $storage1TB = $storageOption ? OptionValue::where('option_id', $storageOption->id)->where('value', '1TB')->first() : null;
        $storage2TB = $storageOption ? OptionValue::where('option_id', $storageOption->id)->where('value', '2TB')->first() : null;

        // Get variants
        $variants = [
            'IPHONE15PRO128BK' => ['color' => $blackColor, 'storage' => $storage128],
            'IPHONE15PRO256BK' => ['color' => $blackColor, 'storage' => $storage256],
            'IPHONE15PRO512BK' => ['color' => $blackColor, 'storage' => $storage512],
            'IPHONE15PRO128WH' => ['color' => $whiteColor, 'storage' => $storage128],
            'IPHONE15PRO128BL' => ['color' => $blueColor, 'storage' => $storage128],

            'S24U256BK' => ['color' => $blackColor, 'storage' => $storage256],
            'S24U512BK' => ['color' => $blackColor, 'storage' => $storage512],
            'S24U1TBBK' => ['color' => $blackColor, 'storage' => $storage1TB],
            'S24U256WH' => ['color' => $whiteColor, 'storage' => $storage256],

            'MBP14M3SSD512' => ['storage' => $storage512],
            'MBP14M3SSD1TB' => ['storage' => $storage1TB],
            'MBP14M3PROSSD1TB' => ['storage' => $storage1TB],
            'MBP14M3PROSSD2TB' => ['storage' => $storage2TB],

            'NIKEAM90S7WH' => ['color' => $whiteColor, 'size' => $size7],
            'NIKEAM90S8WH' => ['color' => $whiteColor, 'size' => $size8],
            'NIKEAM90S9WH' => ['color' => $whiteColor, 'size' => $size9],
            'NIKEAM90S10WH' => ['color' => $whiteColor, 'size' => $size10],
            'NIKEAM90S11WH' => ['color' => $whiteColor, 'size' => $size11],
            'NIKEAM90S7BK' => ['color' => $blackColor, 'size' => $size7],
            'NIKEAM90S8BK' => ['color' => $blackColor, 'size' => $size8],
            'NIKEAM90S9BK' => ['color' => $blackColor, 'size' => $size9],
            'NIKEAM90S10BK' => ['color' => $blackColor, 'size' => $size10],

            'ADIDASUB22S7BK' => ['color' => $blackColor, 'size' => $size7],
            'ADIDASUB22S8BK' => ['color' => $blackColor, 'size' => $size8],
            'ADIDASUB22S9BK' => ['color' => $blackColor, 'size' => $size9],
            'ADIDASUB22S10BK' => ['color' => $blackColor, 'size' => $size10],
            'ADIDASUB22S11BK' => ['color' => $blackColor, 'size' => $size11],
            'ADIDASUB22S7WH' => ['color' => $whiteColor, 'size' => $size7],
            'ADIDASUB22S8WH' => ['color' => $whiteColor, 'size' => $size8],
            'ADIDASUB22S9WH' => ['color' => $whiteColor, 'size' => $size9],

            'SONYWH1000XM5BK' => ['color' => $blackColor],
            'SONYWH1000XM5WH' => ['color' => $whiteColor],
            'SONYWH1000XM5BL' => ['color' => $blueColor],
        ];

        foreach ($variants as $sku => $options) {
            $variant = ProductVariant::where('sku', $sku)->first();
            
            if ($variant) {
                foreach ($options as $optionType => $optionValue) {
                    // Skip if option value doesn't exist
                    if (!$optionValue) {
                        continue;
                    }
                    
                    // Check if this option value is already assigned to this variant
                    $exists = DB::table('variant_option_values')
                        ->where('variant_id', $variant->id)
                        ->where('option_value_id', $optionValue->id)
                        ->exists();

                    if (!$exists) {
                        DB::table('variant_option_values')->insert([
                            'variant_id' => $variant->id,
                            'option_id' => $optionValue->option_id,
                            'option_value_id' => $optionValue->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        
                        // Get option name for display
                        $optionName = Option::find($optionValue->option_id)->name;
                        $this->command->line("   ✓ Assigned {$optionName}: {$optionValue->value} to {$sku}");
                    }
                }
            }
        }

        $this->command->info('✅ Variant option values seeded successfully!');
    }
}
