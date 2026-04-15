<?php

namespace Database\Seeders;

use App\Models\Manufacturer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ManufacturerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏭 Seeding manufacturers...');

        $manufacturers = [
            [
                'name' => 'Apple',
                'slug' => 'apple',
                'status' => 1,
            ],
            [
                'name' => 'Samsung',
                'slug' => 'samsung',
                'status' => 1,
            ],
            [
                'name' => 'Sony',
                'slug' => 'sony',
                'status' => 1,
            ],
            [
                'name' => 'LG',
                'slug' => 'lg',
                'status' => 1,
            ],
            [
                'name' => 'Xiaomi',
                'slug' => 'xiaomi',
                'status' => 1,
            ],
            [
                'name' => 'Nike',
                'slug' => 'nike',
                'status' => 1,
            ],
            [
                'name' => 'Adidas',
                'slug' => 'adidas',
                'status' => 1,
            ],
            [
                'name' => 'Canon',
                'slug' => 'canon',
                'status' => 1,
            ],
            [
                'name' => 'Dell',
                'slug' => 'dell',
                'status' => 1,
            ],
            [
                'name' => 'HP',
                'slug' => 'hp',
                'status' => 1,
            ],
        ];

        foreach ($manufacturers as $manufacturerData) {
            $manufacturer = Manufacturer::firstOrCreate(['slug' => $manufacturerData['slug']], $manufacturerData);
            $this->command->line("   ✓ Created manufacturer: {$manufacturer->name}");
        }

        $this->command->info('✅ Manufacturers seeded successfully!');
    }
}
