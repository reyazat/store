<?php

namespace Database\Seeders;

use App\Models\Slider;
use Illuminate\Database\Seeder;

class SliderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎨 Seeding homepage sliders...');

        $sliders = [
            [
                'title' => 'تخفیف ویژه فصل',
                'subtitle' => 'تا ۵۰٪ تخفیف روی محصولات منتخب',
                'image' => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1920&q=80',
                'link' => '/products?has_discount=true',
                'button_text' => 'مشاهده محصولات',
                'button_link' => '/products?has_discount=true',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'مجموعه جدید گوشی‌های هوشمند',
                'subtitle' => 'جدیدترین مدل‌ها با بهترین قیمت',
                'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=1920&q=80',
                'link' => '/categories/mobile-phones',
                'button_text' => 'مشاهده گوشی‌ها',
                'button_link' => '/categories/mobile-phones',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'لپ‌تاپ‌های حرفه‌ای',
                'subtitle' => 'برای کار و بازی با بهترین عملکرد',
                'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=1920&q=80',
                'link' => '/categories/laptops',
                'button_text' => 'مشاهده لپ‌تاپ‌ها',
                'button_link' => '/categories/laptops',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'مد و استایل',
                'subtitle' => 'جدیدترین ترندهای فصل مد',
                'image' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1920&q=80',
                'link' => '/categories/fashion',
                'button_text' => 'مشاهده محصولات',
                'button_link' => '/categories/fashion',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'title' => 'تجهیزات صوتی و تصویری',
                'subtitle' => 'تجربه صدای بی‌نقص و تصویر شفاف',
                'image' => 'https://images.unsplash.com/photo-1596462502278-27d52b40f0be?w=1920&q=80',
                'link' => '/categories/electronics',
                'button_text' => 'مشاهده محصولات',
                'button_link' => '/categories/electronics',
                'sort_order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($sliders as $sliderData) {
            Slider::firstOrCreate(
                ['title' => $sliderData['title']],
                $sliderData
            );
        }

        $this->command->info('✅ Homepage sliders seeded successfully!');
    }
}
