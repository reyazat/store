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
                'icon' => '📱',
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
                'icon' => '👕',
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
                'icon' => '🏠',
                'children' => [
                    [
                        'name' => 'لوازم آشپزخانه',
                        'slug' => 'kitchenware',
                        'description' => 'لوازم آشپزخانه',
                        'meta_title' => 'لوازم آشپزخانه',
                        'meta_description' => 'لوازم آشپزخانه باکیفیت',
                        'status' => 1,
                    ],
                    [
                        'name' => 'لوازم خانه',
                        'slug' => 'home-accessories',
                        'description' => 'لوازم منزل',
                        'meta_title' => 'لوازم خانه',
                        'meta_description' => 'لوازم کاربردی خانه',
                        'status' => 1,
                    ],
                    [
                        'name' => 'مبلمان',
                        'slug' => 'furniture',
                        'description' => 'مبلمان و دکوراسیون',
                        'meta_title' => 'مبلمان',
                        'meta_description' => 'مبلمان شیک و مدرن',
                        'status' => 1,
                    ],
                ]
            ],
            [
                'name' => 'زیبایی و سلامت',
                'slug' => 'beauty-health',
                'description' => 'محصولات آرایشی، بهداشتی و سلامت',
                'meta_title' => 'زیبایی و سلامت - فروشگاه',
                'meta_description' => 'محصولات آرایشی و بهداشتی اصل',
                'status' => 1,
                'icon' => '💄',
                'children' => [
                    [
                        'name' => 'آرایشی',
                        'slug' => 'makeup',
                        'description' => 'محصولات آرایشی',
                        'meta_title' => 'آرایشی',
                        'meta_description' => 'محصولات آرایشی با کیفیت',
                        'status' => 1,
                    ],
                    [
                        'name' => 'مراقبت پوست',
                        'slug' => 'skincare',
                        'description' => 'محصولات مراقبت از پوست',
                        'meta_title' => 'مراقبت پوست',
                        'meta_description' => 'محصولات مراقبت از پوست',
                        'status' => 1,
                    ],
                    [
                        'name' => 'عطر و ادکلن',
                        'slug' => 'perfumes',
                        'description' => 'عطر و ادکلن اصل',
                        'meta_title' => 'عطر و ادکلن',
                        'meta_description' => 'عطر و ادکلن اصل با ضمانت',
                        'status' => 1,
                    ],
                ]
            ],
            [
                'name' => 'ورزش و سفر',
                'slug' => 'sports-travel',
                'description' => 'لوازم ورزشی و مسافرتی',
                'meta_title' => 'ورزش و سفر - فروشگاه',
                'meta_description' => 'لوازم ورزشی و مسافرتی',
                'status' => 1,
                'icon' => '⚽',
                'children' => [
                    [
                        'name' => 'تجهیزات ورزشی',
                        'slug' => 'sports-equipment',
                        'description' => 'تجهیزات ورزشی',
                        'meta_title' => 'تجهیزات ورزشی',
                        'meta_description' => 'تجهیزات ورزشی باکیفیت',
                        'status' => 1,
                    ],
                    [
                        'name' => 'لباس ورزشی',
                        'slug' => 'sportswear',
                        'description' => 'لباس ورزشی',
                        'meta_title' => 'لباس ورزشی',
                        'meta_description' => 'لباس ورزشی با کیفیت',
                        'status' => 1,
                    ],
                    [
                        'name' => 'چمدان و کیف مسافرتی',
                        'slug' => 'luggage',
                        'description' => 'چمدان و کیف مسافرتی',
                        'meta_title' => 'چمدان و کیف مسافرتی',
                        'meta_description' => 'چمدان و کیف مسافرتی',
                        'status' => 1,
                    ],
                ]
            ],
            [
                'name' => 'کتاب و لوازم تحریر',
                'slug' => 'books-stationery',
                'description' => 'کتاب، لوازم تحریر و هنر',
                'meta_title' => 'کتاب و لوازم تحریر - فروشگاه',
                'meta_description' => 'کتاب و لوازم تحریر',
                'status' => 1,
                'icon' => '📚',
                'children' => [
                    [
                        'name' => 'کتاب',
                        'slug' => 'books',
                        'description' => 'کتاب‌های مختلف',
                        'meta_title' => 'کتاب',
                        'meta_description' => 'کتاب‌های متنوع',
                        'status' => 1,
                    ],
                    [
                        'name' => 'لوازم تحریر',
                        'slug' => 'stationery',
                        'description' => 'لوازم تحریر',
                        'meta_title' => 'لوازم تحریر',
                        'meta_description' => 'لوازم تحریر اداری و مدرسه',
                        'status' => 1,
                    ],
                    [
                        'name' => 'صنایع دستی',
                        'slug' => 'handicrafts',
                        'description' => 'صنایع دستی',
                        'meta_title' => 'صنایع دستی',
                        'meta_description' => 'صنایع دستی ایرانی',
                        'status' => 1,
                    ],
                ]
            ],
            [
                'name' => 'اسباب‌بازی و کودک',
                'slug' => 'toys-kids',
                'description' => 'اسباب‌بازی، لوازم کودک و نوزاد',
                'meta_title' => 'اسباب‌بازی و کودک - فروشگاه',
                'meta_description' => 'اسباب‌بازی و لوازم کودک',
                'status' => 1,
                'icon' => '🧸',
                'children' => [
                    [
                        'name' => 'اسباب‌بازی',
                        'slug' => 'toys',
                        'description' => 'اسباب‌بازی',
                        'meta_title' => 'اسباب‌بازی',
                        'meta_description' => 'اسباب‌بازی‌های فکری و آموزشی',
                        'status' => 1,
                    ],
                    [
                        'name' => 'لوازم نوزاد',
                        'slug' => 'baby-essentials',
                        'description' => 'لوازم نوزاد',
                        'meta_title' => 'لوازم نوزاد',
                        'meta_description' => 'لوازم ضروری نوزاد',
                        'status' => 1,
                    ],
                ]
            ],
            [
                'name' => 'ابزار و خودرو',
                'slug' => 'tools-automotive',
                'description' => 'ابزارآلات، لوازم خودرو و صنعتی',
                'meta_title' => 'ابزار و خودرو - فروشگاه',
                'meta_description' => 'ابزارآلات و لوازم خودرو',
                'status' => 1,
                'icon' => '🔧',
                'children' => [
                    [
                        'name' => 'ابزار دستی',
                        'slug' => 'hand-tools',
                        'description' => 'ابزار دستی',
                        'meta_title' => 'ابزار دستی',
                        'meta_description' => 'ابزار دستی باکیفیت',
                        'status' => 1,
                    ],
                    [
                        'name' => 'لوازم خودرو',
                        'slug' => 'car-accessories',
                        'description' => 'لوازم خودرو',
                        'meta_title' => 'لوازم خودرو',
                        'meta_description' => 'لوازم جانبی خودرو',
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
