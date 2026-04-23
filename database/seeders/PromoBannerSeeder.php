<?php

namespace Database\Seeders;

use App\Models\PromoBanner;
use Illuminate\Database\Seeder;

class PromoBannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'title'         => 'تخفیف‌های روزانه',
                'subtitle'      => 'هر روز تخفیف‌های جدید',
                'icon'          => 'percent',
                'link'          => '/products?has_discount=1',
                'cta_text'      => 'مشاهده',
                'gradient_from' => 'primary-500',
                'gradient_to'   => 'primary-700',
                'placement'     => 'home',
                'sort_order'    => 1,
            ],
            [
                'title'         => 'ارسال رایگان',
                'subtitle'      => 'خرید بالای ۵۰۰ هزار تومان',
                'icon'          => 'truck',
                'link'          => '/products',
                'cta_text'      => 'شروع خرید',
                'gradient_from' => 'emerald-500',
                'gradient_to'   => 'teal-600',
                'placement'     => 'home',
                'sort_order'    => 2,
            ],
            [
                'title'         => 'ضمانت اصالت',
                'subtitle'      => '۱۰۰٪ اصل و اورجینال',
                'icon'          => 'shield',
                'link'          => '/about',
                'cta_text'      => 'بیشتر بدانید',
                'gradient_from' => 'violet-500',
                'gradient_to'   => 'purple-700',
                'placement'     => 'home',
                'sort_order'    => 3,
            ],
        ];

        foreach ($banners as $banner) {
            PromoBanner::updateOrCreate(
                ['title' => $banner['title'], 'placement' => $banner['placement']],
                array_merge($banner, ['is_active' => true])
            );
        }
    }
}
