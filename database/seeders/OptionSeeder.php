<?php

namespace Database\Seeders;

use App\Models\Option;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎨 Seeding options...');

        $options = [
            [
                'name' => 'Color',
                'slug' => 'color',
                'type' => 'radio',
                'sorted' => 1,
            ],
            [
                'name' => 'Size',
                'slug' => 'size',
                'type' => 'radio',
                'sorted' => 2,
            ],
            [
                'name' => 'Storage',
                'slug' => 'storage',
                'type' => 'radio',
                'sorted' => 3,
            ],
            [
                'name' => 'Material',
                'slug' => 'material',
                'type' => 'radio',
                'sorted' => 4,
            ],
            [
                'name' => 'Style',
                'slug' => 'style',
                'type' => 'radio',
                'sorted' => 5,
            ],
        ];

        foreach ($options as $optionData) {
            $option = Option::firstOrCreate(['slug' => $optionData['slug']], $optionData);
            $this->command->line("   ✓ Created option: {$option->name}");
        }

        $this->command->info('✅ Options seeded successfully!');
    }
}
