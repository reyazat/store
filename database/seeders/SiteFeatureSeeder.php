<?php

namespace Database\Seeders;

use App\Models\SiteFeature;
use Illuminate\Database\Seeder;

class SiteFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            ['title' => 'ارسال سریع', 'description' => 'ارسال به سراسر کشور', 'icon' => 'Truck', 'sort_order' => 1],
            ['title' => 'ضمانت اصالت', 'description' => '۱۰۰٪ اصل و اورجینال', 'icon' => 'Shield', 'sort_order' => 2],
            ['title' => '۷ روز بازگشت', 'description' => 'بازگشت آسان کالا', 'icon' => 'RotateCcw', 'sort_order' => 3],
            ['title' => 'پشتیبانی ۲۴/۷', 'description' => 'پاسخگویی همیشگی', 'icon' => 'Headphones', 'sort_order' => 4],
        ];

        foreach ($features as $feature) {
            SiteFeature::updateOrCreate(
                ['title' => $feature['title']],
                array_merge($feature, ['is_active' => true])
            );
        }
    }
}
