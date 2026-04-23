<?php

namespace Database\Seeders;

use App\Models\SiteFeature;
use Illuminate\Database\Seeder;

class SiteFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            ['title' => 'ارسال سریع', 'description' => 'ارسال به سراسر کشور', 'icon' => 'truck', 'sort_order' => 1],
            ['title' => 'ضمانت اصالت', 'description' => '۱۰۰٪ اصل و اورجینال', 'icon' => 'shield', 'sort_order' => 2],
            ['title' => '۷ روز بازگشت', 'description' => 'بازگشت آسان کالا', 'icon' => 'rotate-ccw', 'sort_order' => 3],
            ['title' => 'پشتیبانی ۲۴/۷', 'description' => 'پاسخگویی همیشگی', 'icon' => 'headphones', 'sort_order' => 4],
        ];

        foreach ($features as $feature) {
            SiteFeature::updateOrCreate(
                ['title' => $feature['title']],
                array_merge($feature, ['is_active' => true])
            );
        }
    }
}
