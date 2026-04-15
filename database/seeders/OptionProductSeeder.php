<?php

namespace Database\Seeders;

use App\Models\Option;
use App\Models\Product;
use Illuminate\Database\Seeder;

class OptionProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔗 Attaching options to products...');

        $colorOption = Option::where('slug', 'color')->first();
        $sizeOption = Option::where('slug', 'size')->first();
        $storageOption = Option::where('slug', 'storage')->first();
        $materialOption = Option::where('slug', 'material')->first();
        $styleOption = Option::where('slug', 'style')->first();

        // iPhone - Color + Storage
        $iphone = Product::where('slug', 'iphone-15-pro')->first();
        if ($iphone && $colorOption && $storageOption) {
            $iphone->options()->syncWithoutDetaching([
                $colorOption->id => ['required' => true],
                $storageOption->id => ['required' => true],
            ]);
            $this->command->line("   ✓ Attached Color + Storage to iPhone 15 Pro");
        }

        // Samsung - Color + Storage
        $samsung = Product::where('slug', 'samsung-galaxy-s24-ultra')->first();
        if ($samsung && $colorOption && $storageOption) {
            $samsung->options()->syncWithoutDetaching([
                $colorOption->id => ['required' => true],
                $storageOption->id => ['required' => true],
            ]);
            $this->command->line("   ✓ Attached Color + Storage to Samsung Galaxy S24");
        }

        // MacBook - Color + Storage
        $macbook = Product::where('slug', 'macbook-pro-14')->first();
        if ($macbook && $colorOption && $storageOption) {
            $macbook->options()->syncWithoutDetaching([
                $colorOption->id => ['required' => true],
                $storageOption->id => ['required' => true],
            ]);
            $this->command->line("   ✓ Attached Color + Storage to MacBook Pro");
        }

        // Nike Shoes - Color + Size
        $nike = Product::where('slug', 'nike-air-max-90')->first();
        if ($nike && $colorOption && $sizeOption) {
            $nike->options()->syncWithoutDetaching([
                $colorOption->id => ['required' => true],
                $sizeOption->id => ['required' => true],
            ]);
            $this->command->line("   ✓ Attached Color + Size to Nike Air Max");
        }

        // Adidas Shoes - Color + Size
        $adidas = Product::where('slug', 'adidas-ultraboost-22')->first();
        if ($adidas && $colorOption && $sizeOption) {
            $adidas->options()->syncWithoutDetaching([
                $colorOption->id => ['required' => true],
                $sizeOption->id => ['required' => true],
            ]);
            $this->command->line("   ✓ Attached Color + Size to Adidas Ultraboost");
        }

        // Sony Headphones - Color
        $sony = Product::where('slug', 'sony-wh-1000xm5')->first();
        if ($sony && $colorOption) {
            $sony->options()->syncWithoutDetaching([
                $colorOption->id => ['required' => true],
            ]);
            $this->command->line("   ✓ Attached Color to Sony Headphones");
        }

        $this->command->info('✅ Options attached to products successfully!');
    }
}
