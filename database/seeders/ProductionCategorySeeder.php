<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Production Category Seeder - Minimal professional categories with Unsplash images
 */
class ProductionCategorySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏷️  Seeding production categories...');

        $categories = [
            [
                'name' => 'الکترونیک',
                'slug' => 'electronics',
                'description' => 'محصولات الکترونیکی و دیجیتال با گارانتی اصلی',
                'meta_title' => 'الکترونیک | خرید آنلاین',
                'meta_description' => 'خرید محصولات الکترونیکی اورجینال با بهترین قیمت',
                'status' => 1,
                'icon' => 'laptop',
                'image' => 'https://images.unsplash.com/photo-1498049860654-af1a5c5668ba?w=800&q=80',
                'children' => [
                    [
                        'name' => 'موبایل',
                        'slug' => 'mobile-phones',
                        'description' => 'گوشی‌های هوشمند برندهای معتبر',
                        'meta_title' => 'موبایل | خرید گوشی',
                        'meta_description' => 'خرید گوشی موبایل سامسونگ، اپل، شیائومی',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800&q=80',
                    ],
                    [
                        'name' => 'لپ‌تاپ',
                        'slug' => 'laptops',
                        'description' => 'لپ‌تاپ‌های حرفه‌ای و اداری',
                        'meta_title' => 'لپ‌تاپ | خرید آنلاین',
                        'meta_description' => 'خرید لپ‌تاپ ایسوس، لنوو، اپل، اچ‌پی',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800&q=80',
                    ],
                ]
            ],
            [
                'name' => 'پوشاک',
                'slug' => 'clothing',
                'description' => 'پوشاک باکیفیت با قیمت مناسب',
                'meta_title' => 'پوشاک | خرید آنلاین',
                'meta_description' => 'خرید لباس مردانه و زنانه',
                'status' => 1,
                'icon' => 'shirt',
                'image' => 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?w=800&q=80',
                'children' => [
                    [
                        'name' => 'مردانه',
                        'slug' => 'mens-clothing',
                        'description' => 'پوشاک مردانه',
                        'meta_title' => 'پوشاک مردانه',
                        'meta_description' => 'خرید لباس مردانه',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1617137968427-85924c800a22?w=800&q=80',
                    ],
                    [
                        'name' => 'زنانه',
                        'slug' => 'womens-clothing',
                        'description' => 'پوشاک زنانه',
                        'meta_title' => 'پوشاک زنانه',
                        'meta_description' => 'خرید لباس زنانه',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?w=800&q=80',
                    ],
                ]
            ],
            [
                'name' => 'خانه و آشپزخانه',
                'slug' => 'home-kitchen',
                'description' => 'لوازم خانگی و آشپزخانه',
                'meta_title' => 'خانه و آشپزخانه',
                'meta_description' => 'خرید لوازم خانگی',
                'status' => 1,
                'icon' => 'home',
                'image' => 'https://images.unsplash.com/photo-1556911220-bff31c812dba?w=800&q=80',
            ],
            [
                'name' => 'کتاب و رسانه',
                'slug' => 'books-media',
                'description' => 'کتاب، فیلم و موسیقی',
                'meta_title' => 'کتاب و رسانه',
                'meta_description' => 'خرید کتاب و محصولات فرهنگی',
                'status' => 1,
                'icon' => 'book',
                'image' => 'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?w=800&q=80',
            ],
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $category = Category::firstOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );

            $this->command->line("   ✓ Category: {$category->name}");

            foreach ($children as $childData) {
                $childData['parent_id'] = $category->id;
                $child = Category::firstOrCreate(
                    ['slug' => $childData['slug']],
                    $childData
                );
                $this->command->line("      └─ {$child->name}");
            }
        }

        $this->command->info('✅ Production categories seeded!');
    }
}
