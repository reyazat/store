<?php

namespace Database\Seeders;

use App\Models\Option;
use App\Models\OptionValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OptionValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎯 Seeding option values...');

        // Get options
        $colorOption = Option::where('slug', 'color')->first();
        $sizeOption = Option::where('slug', 'size')->first();
        $storageOption = Option::where('slug', 'storage')->first();
        $materialOption = Option::where('slug', 'material')->first();
        $styleOption = Option::where('slug', 'style')->first();

        $optionValues = [];

        // Color values
        if ($colorOption) {
            $optionValues = array_merge($optionValues, [
                ['option_id' => $colorOption->id, 'value' => 'Black', 'sorted' => 1],
                ['option_id' => $colorOption->id, 'value' => 'White', 'sorted' => 2],
                ['option_id' => $colorOption->id, 'value' => 'Red', 'sorted' => 3],
                ['option_id' => $colorOption->id, 'value' => 'Blue', 'sorted' => 4],
                ['option_id' => $colorOption->id, 'value' => 'Green', 'sorted' => 5],
                ['option_id' => $colorOption->id, 'value' => 'Yellow', 'sorted' => 6],
                ['option_id' => $colorOption->id, 'value' => 'Pink', 'sorted' => 7],
                ['option_id' => $colorOption->id, 'value' => 'Purple', 'sorted' => 8],
                ['option_id' => $colorOption->id, 'value' => 'Gray', 'sorted' => 9],
                ['option_id' => $colorOption->id, 'value' => 'Silver', 'sorted' => 10],
            ]);
        }

        // Size values
        if ($sizeOption) {
            $optionValues = array_merge($optionValues, [
                ['option_id' => $sizeOption->id, 'value' => 'XS', 'sorted' => 1],
                ['option_id' => $sizeOption->id, 'value' => 'S', 'sorted' => 2],
                ['option_id' => $sizeOption->id, 'value' => 'M', 'sorted' => 3],
                ['option_id' => $sizeOption->id, 'value' => 'L', 'sorted' => 4],
                ['option_id' => $sizeOption->id, 'value' => 'XL', 'sorted' => 5],
                ['option_id' => $sizeOption->id, 'value' => 'XXL', 'sorted' => 6],
                ['option_id' => $sizeOption->id, 'value' => '3XL', 'sorted' => 7],
                ['option_id' => $sizeOption->id, 'value' => '4XL', 'sorted' => 8],
                ['option_id' => $sizeOption->id, 'value' => '5XL', 'sorted' => 9],
            ]);
        }

        // Storage values
        if ($storageOption) {
            $optionValues = array_merge($optionValues, [
                ['option_id' => $storageOption->id, 'value' => '64GB', 'sorted' => 1],
                ['option_id' => $storageOption->id, 'value' => '128GB', 'sorted' => 2],
                ['option_id' => $storageOption->id, 'value' => '256GB', 'sorted' => 3],
                ['option_id' => $storageOption->id, 'value' => '512GB', 'sorted' => 4],
                ['option_id' => $storageOption->id, 'value' => '1TB', 'sorted' => 5],
                ['option_id' => $storageOption->id, 'value' => '2TB', 'sorted' => 6],
            ]);
        }

        // Material values
        if ($materialOption) {
            $optionValues = array_merge($optionValues, [
                ['option_id' => $materialOption->id, 'value' => 'Cotton', 'sorted' => 1],
                ['option_id' => $materialOption->id, 'value' => 'Polyester', 'sorted' => 2],
                ['option_id' => $materialOption->id, 'value' => 'Leather', 'sorted' => 3],
                ['option_id' => $materialOption->id, 'value' => 'Denim', 'sorted' => 4],
                ['option_id' => $materialOption->id, 'value' => 'Wool', 'sorted' => 5],
                ['option_id' => $materialOption->id, 'value' => 'Silk', 'sorted' => 6],
                ['option_id' => $materialOption->id, 'value' => 'Nylon', 'sorted' => 7],
                ['option_id' => $materialOption->id, 'value' => 'Canvas', 'sorted' => 8],
            ]);
        }

        // Style values
        if ($styleOption) {
            $optionValues = array_merge($optionValues, [
                ['option_id' => $styleOption->id, 'value' => 'Regular', 'sorted' => 1],
                ['option_id' => $styleOption->id, 'value' => 'Slim', 'sorted' => 2],
                ['option_id' => $styleOption->id, 'value' => 'Loose', 'sorted' => 3],
                ['option_id' => $styleOption->id, 'value' => 'Athletic', 'sorted' => 4],
                ['option_id' => $styleOption->id, 'value' => 'Classic', 'sorted' => 5],
                ['option_id' => $styleOption->id, 'value' => 'Modern', 'sorted' => 6],
            ]);
        }

        foreach ($optionValues as $valueData) {
            $value = OptionValue::firstOrCreate(
                ['option_id' => $valueData['option_id'], 'value' => $valueData['value']],
                ['sorted' => $valueData['sorted']]
            );
            
            // Get option name for display
            $optionName = Option::find($valueData['option_id'])->name;
            $this->command->line("   ✓ Created {$optionName} value: {$value->value}");
        }

        $this->command->info('✅ Option values seeded successfully!');
    }
}
