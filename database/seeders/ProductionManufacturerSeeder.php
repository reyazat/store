<?php

namespace Database\Seeders;

use App\Models\Manufacturer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Production Manufacturer Seeder - Well-known brands
 */
class ProductionManufacturerSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏭 Seeding manufacturers...');

        $manufacturers = [
            [
                'name' => 'اپل',
                'slug' => 'apple',
                'description' => 'محصولات اورجینال اپل با گارانتی معتبر',
                'logo' => 'https://images.unsplash.com/photo-1621768212002-6ad16e8a5d4a?w=200&q=80',
                'website' => 'https://www.apple.com',
                'status' => 1,
                'sort_order' => 1,
            ],
            [
                'name' => 'سامسونگ',
                'slug' => 'samsung',
                'description' => 'محصولات سامسونگ با گارانتی اصلی',
                'logo' => 'https://images.unsplash.com/photo-1610945265078-3858a0828671?w=200&q=80',
                'website' => 'https://www.samsung.com',
                'status' => 1,
                'sort_order' => 2,
            ],
            [
                'name' => 'شیائومی',
                'slug' => 'xiaomi',
                'description' => 'محصولات شیائومی با بهترین قیمت',
                'logo' => null,
                'website' => 'https://www.mi.com',
                'status' => 1,
                'sort_order' => 3,
            ],
            [
                'name' => 'نایک',
                'slug' => 'nike',
                'description' => 'پوشاک و کفش ورزشی نایک',
                'logo' => null,
                'website' => 'https://www.nike.com',
                'status' => 1,
                'sort_order' => 4,
            ],
            [
                'name' => 'آدیداس',
                'slug' => 'adidas',
                'description' => 'پوشاک و کفش ورزشی آدیداس',
                'logo' => null,
                'website' => 'https://www.adidas.com',
                'status' => 1,
                'sort_order' => 5,
            ],
        ];

        foreach ($manufacturers as $data) {
            $manufacturer = Manufacturer::firstOrCreate(
                ['slug' => $data['slug']],
                $data
            );
            $this->command->line("   ✓ {$manufacturer->name}");
        }

        $this->command->info('✅ Manufacturers seeded!');
    }
}
