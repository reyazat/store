<?php

namespace Database\Seeders;

use App\Models\SiteFeature;
use App\Models\PromoBanner;
use Illuminate\Database\Seeder;

/**
 * Production Site Content Seeder - Homepage features & banners
 */
class ProductionSiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🎨 Seeding site content...');

        // Site Features (service strip on homepage)
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
        $this->command->line('   ✓ Site features seeded');

        // Promo Banners
        $banners = [
            [
                'title'         => 'تخفیف‌های ویژه',
                'subtitle'      => 'تا ۵۰٪ تخفیف محصولات منتخب',
                'icon'          => 'percent',
                'link'          => '/products?has_discount=1',
                'cta_text'      => 'مشاهده تخفیف‌ها',
                'gradient_from' => 'primary-500',
                'gradient_to'   => 'primary-700',
                'placement'     => 'home',
                'sort_order'    => 1,
            ],
            [
                'title'         => 'ارسال رایگان',
                'subtitle'      => 'برای خریدهای بالای ۵۰۰ هزار تومان',
                'icon'          => 'truck',
                'link'          => '/products',
                'cta_text'      => 'شروع خرید',
                'gradient_from' => 'emerald-500',
                'gradient_to'   => 'teal-600',
                'placement'     => 'home',
                'sort_order'    => 2,
            ],
            [
                'title'         => 'ضمانت بازگشت',
                'subtitle'      => '۷ روز ضمانت بازگشت کالا',
                'icon'          => 'shield',
                'link'          => '/about',
                'cta_text'      => 'اطلاعات بیشتر',
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
        $this->command->line('   ✓ Promo banners seeded');

        $this->command->info('✅ Site content seeded!');
    }
}
