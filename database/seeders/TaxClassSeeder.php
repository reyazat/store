<?php

namespace Database\Seeders;

use App\Models\TaxClass;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaxClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('💰 Seeding tax classes...');

        $taxClasses = [
            [
                'name' => 'استاندارد',
                'rate' => 9.0000,
                'type' => 'P', // Percent
                'status' => 1,
            ],
            [
                'name' => 'معاف از مالیات',
                'rate' => 0.0000,
                'type' => 'P', // Percent
                'status' => 1,
            ],
            [
                'name' => 'مالیات کاهش یافته',
                'rate' => 5.0000,
                'type' => 'P', // Percent
                'status' => 1,
            ],
            [
                'name' => 'مالیات افزایش یافته',
                'rate' => 15.0000,
                'type' => 'P', // Percent
                'status' => 1,
            ],
        ];

        foreach ($taxClasses as $taxClassData) {
            $taxClass = TaxClass::firstOrCreate(['name' => $taxClassData['name']], $taxClassData);
            $this->command->line("   ✓ Created tax class: {$taxClass->name} ({$taxClass->rate}%)");
        }

        $this->command->info('✅ Tax classes seeded successfully!');
    }
}
