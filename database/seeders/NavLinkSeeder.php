<?php

namespace Database\Seeders;

use App\Models\NavLink;
use Illuminate\Database\Seeder;

class NavLinkSeeder extends Seeder
{
    public function run(): void
    {
        $links = [
            ['label' => 'صفحه اصلی',    'url' => '/',           'group' => 'header',         'sort_order' => 1],
            ['label' => 'محصولات',       'url' => '/products',   'group' => 'header',         'sort_order' => 2],
            ['label' => '🔥 تخفیف‌ها',  'url' => '/discounts',  'group' => 'header',         'sort_order' => 3],
            ['label' => 'برندها',        'url' => '/brands',     'group' => 'header',         'sort_order' => 4],

            ['label' => 'محصولات',       'url' => '/products',   'group' => 'footer_quick',   'sort_order' => 1],
            ['label' => 'دسته‌بندی‌ها', 'url' => '/categories', 'group' => 'footer_quick',   'sort_order' => 2],
            ['label' => 'برندها',        'url' => '/brands',     'group' => 'footer_quick',   'sort_order' => 3],
            ['label' => 'تخفیف‌ها',     'url' => '/discounts',  'group' => 'footer_quick',   'sort_order' => 4],

            ['label' => 'پیگیری سفارش',       'url' => '/user/orders',    'group' => 'footer_service', 'sort_order' => 1],
            ['label' => 'آدرس‌های من',        'url' => '/user/addresses', 'group' => 'footer_service', 'sort_order' => 2],
            ['label' => 'لیست علاقه‌مندی‌ها', 'url' => '/user/wishlist',  'group' => 'footer_service', 'sort_order' => 3],
            ['label' => 'سوالات متداول',      'url' => '/faq',            'group' => 'footer_service', 'sort_order' => 4],
            ['label' => 'قوانین و مقررات',    'url' => '/terms',          'group' => 'footer_service', 'sort_order' => 5],
        ];

        foreach ($links as $link) {
            NavLink::firstOrCreate(
                ['label' => $link['label'], 'group' => $link['group']],
                array_merge($link, ['is_active' => true, 'target' => '_self'])
            );
        }
    }
}
