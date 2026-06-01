<?php

namespace Database\Seeders;

use App\Models\NavLink;
use Illuminate\Database\Seeder;

class NavLinkSeeder extends Seeder
{
    /**
     * Seed the application's navigation links.
     * Works in both development and production environments.
     */
    public function run(): void
    {
        $this->command->info('🔗 Seeding navigation links...');

        // Header navigation links (main menu)
        $headerLinks = [
            [
                'label' => 'صفحه اصلی',
                'url' => '/',
                'group' => 'header',
                'target' => '_self',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'label' => 'محصولات',
                'url' => '/products',
                'group' => 'header',
                'target' => '_self',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'label' => 'تخفیف‌ها',
                'url' => '/discounts',
                'group' => 'header',
                'target' => '_self',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'label' => 'برندها',
                'url' => '/brands',
                'group' => 'header',
                'target' => '_self',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'label' => 'سوالات متداول',
                'url' => '/faq',
                'group' => 'header',
                'target' => '_self',
                'sort_order' => 5,
                'is_active' => true,
            ],
        ];

        // Footer quick links column
        $footerQuickLinks = [
            [
                'label' => 'محصولات',
                'url' => '/products',
                'group' => 'footer_quick',
                'target' => '_self',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'label' => 'دسته‌بندی‌ها',
                'url' => '/categories',
                'group' => 'footer_quick',
                'target' => '_self',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'label' => 'برندها',
                'url' => '/brands',
                'group' => 'footer_quick',
                'target' => '_self',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'label' => 'تخفیف‌ها',
                'url' => '/discounts',
                'group' => 'footer_quick',
                'target' => '_self',
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        // Footer customer service links column
        $footerServiceLinks = [
            [
                'label' => 'پیگیری سفارش',
                'url' => '/order-tracking',
                'group' => 'footer_service',
                'target' => '_self',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'label' => 'آدرس‌های من',
                'url' => '/user/addresses',
                'group' => 'footer_service',
                'target' => '_self',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'label' => 'لیست علاقه‌مندی‌ها',
                'url' => '/user/wishlist',
                'group' => 'footer_service',
                'target' => '_self',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'label' => 'سوالات متداول',
                'url' => '/faq',
                'group' => 'footer_service',
                'target' => '_self',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'label' => 'قوانین و مقررات',
                'url' => '/terms',
                'group' => 'footer_service',
                'target' => '_self',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'label' => 'حریم خصوصی',
                'url' => '/privacy',
                'group' => 'footer_service',
                'target' => '_self',
                'sort_order' => 6,
                'is_active' => true,
            ],
        ];

        // Combine all links
        $allLinks = array_merge($headerLinks, $footerQuickLinks, $footerServiceLinks);

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($allLinks as $link) {
            // Check if a link with same URL and group already exists
            $existing = NavLink::where('url', $link['url'])
                ->where('group', $link['group'])
                ->first();

            if ($existing) {
                $skippedCount++;
                continue;
            }

            NavLink::create($link);
            $createdCount++;
        }

        $this->command->info("✅ Created {$createdCount} navigation links");

        if ($skippedCount > 0) {
            $this->command->warn("⏭️  Skipped {$skippedCount} duplicate links");
        }

        $this->command->newLine();
    }
}
