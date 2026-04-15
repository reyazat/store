<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏷️  Seeding categories...');

        $categories = [
            [
                'name' => 'الکترونیک',
                'slug' => 'electronics',
                'description' => 'محصولات الکترونیکی و دیجیتال',
                'meta_title' => 'الکترونیک - فروشگاه',
                'meta_description' => 'بهترین محصولات الکترونیکی با قیمت مناسب',
                'status' => 1,
                'children' => [
                    [
                        'name' => 'موبایل و تبلت',
                        'slug' => 'mobile-tablets',
                        'description' => 'موبایل، تبلت و لوازم جانبی',
                        'meta_title' => 'موبایل و تبلت',
                        'meta_description' => 'جدیدترین موبایل و تبلت‌ها',
                        'status' => 1,
                    ],
                    [
                        'name' => 'لپ‌تاپ و کامپیوتر',
                        'slug' => 'laptops-computers',
                        'description' => 'لپ‌تاپ، کامپیوتر و لوازم جانبی',
                        'meta_title' => 'لپ‌تاپ و کامپیوتر',
                        'meta_description' => 'لپ‌تاپ و کامپیوتر با بهترین قیمت',
                        'status' => 1,
                    ],
                    [
                        'name' => 'صوتی و تصویری',
                        'slug' => 'audio-video',
                        'description' => 'سیستم‌های صوتی و تصویری',
                        'meta_title' => 'صوتی و تصویری',
                        'meta_description' => 'بهترین سیستم‌های صوتی و تصویری',
                        'status' => 1,
                    ],
                ]
            ],
            [
                'name' => 'پوشاک و مد',
                'slug' => 'fashion',
                'description' => 'پوشاک، کیف و کفش',
                'meta_title' => 'پوشاک و مد',
                'meta_description' => 'جدیدترین مدل‌های پوشاک و مد',
                'status' => 1,
                'children' => [
                    [
                        'name' => 'مردانه',
                        'slug' => 'mens',
                        'description' => 'پوشاک مردانه',
                        'meta_title' => 'پوشاک مردانه',
                        'meta_description' => 'پوشاک مردانه با کیفیت',
                        'status' => 1,
                    ],
                    [
                        'name' => 'زنانه',
                        'slug' => 'womens',
                        'description' => 'پوشاک زنانه',
                        'meta_title' => 'پوشاک زنانه',
                        'meta_description' => 'پوشاک زنانه شیک و مدرن',
                        'status' => 1,
                    ],
                    [
                        'name' => 'بچگانه',
                        'slug' => 'kids',
                        'description' => 'پوشاک بچگانه',
                        'meta_title' => 'پوشاک بچگانه',
                        'meta_description' => 'پوشاک باکیفیت برای کودکان',
                        'status' => 1,
                    ],
                ]
            ],
            [
                'name' => 'خانه و آشپزخانه',
                'slug' => 'home-kitchen',
                'description' => 'لوازم خانه و آشپزخانه',
                'meta_title' => 'خانه و آشپزخانه',
                'meta_description' => 'لوازم ضروری خانه و آشپزخانه',
                'status' => 1,
                'children' => [
                    [
                        'name' => 'لوازم آشپزخانه',
                        'slug' => 'kware',
                        'description' => 'لوازم آشپزخانه',
                        'meta_title' => 'لوازم آشپزخانه',
                        'meta_description' => 'لوازم آشپزخانه باکیفیت',
                        'status' => 1,
                    ],
                    [
                        'name' => 'لوازم خانه',
                        'slug' => 'home',
                        'description' => 'لوازم منزل',
                        'meta_title' => 'لوازم خانه',
                        'meta_description' => 'لوازم کاربردی خانه',
                        'status' => 1,
                    ],
                ]
            ],
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $category = Category::firstOrCreate(['slug' => $categoryData['slug']], $categoryData);
            $this->command->line("   ✓ Created category: {$category->name}");

            foreach ($children as $childData) {
                $childData['parent_id'] = $category->id;
                $child = Category::firstOrCreate(['slug' => $childData['slug']], $childData);
                $this->command->line("     ✓ Created subcategory: {$child->name}");
            }
        }

        $this->command->info('✅ Categories seeded successfully!');
    }
}
