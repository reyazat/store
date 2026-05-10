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
                'image' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=300&h=300&fit=crop&auto=format',
                'children' => [
                    [
                        'name' => 'موبایل و تبلت',
                        'slug' => 'mobile-tablets',
                        'description' => 'موبایل، تبلت و لوازم جانبی',
                        'meta_title' => 'موبایل و تبلت',
                        'meta_description' => 'جدیدترین موبایل و تبلت‌ها',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=300&h=300&fit=crop&auto=format',
                    ],
                    [
                        'name' => 'لپ‌تاپ و کامپیوتر',
                        'slug' => 'laptops-computers',
                        'description' => 'لپ‌تاپ، کامپیوتر و لوازم جانبی',
                        'meta_title' => 'لپ‌تاپ و کامپیوتر',
                        'meta_description' => 'لپ‌تاپ و کامپیوتر با بهترین قیمت',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=300&h=300&fit=crop&auto=format',
                    ],
                    [
                        'name' => 'صوتی و تصویری',
                        'slug' => 'audio-video',
                        'description' => 'سیستم‌های صوتی و تصویری',
                        'meta_title' => 'صوتی و تصویری',
                        'meta_description' => 'بهترین سیستم‌های صوتی و تصویری',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=300&h=300&fit=crop&auto=format',
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
                'image' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=300&h=300&fit=crop&auto=format',
                'children' => [
                    [
                        'name' => 'مردانه',
                        'slug' => 'mens',
                        'description' => 'پوشاک مردانه',
                        'meta_title' => 'پوشاک مردانه',
                        'meta_description' => 'پوشاک مردانه با کیفیت',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1617137968427-85924c800a22?w=300&h=300&fit=crop&auto=format',
                    ],
                    [
                        'name' => 'زنانه',
                        'slug' => 'womens',
                        'description' => 'پوشاک زنانه',
                        'meta_title' => 'پوشاک زنانه',
                        'meta_description' => 'پوشاک زنانه شیک و مدرن',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1567401893414-76b7b1e5a7a5?w=300&h=300&fit=crop&auto=format',
                    ],
                    [
                        'name' => 'بچگانه',
                        'slug' => 'kids',
                        'description' => 'پوشاک بچگانه',
                        'meta_title' => 'پوشاک بچگانه',
                        'meta_description' => 'پوشاک باکیفیت برای کودکان',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1519457431-44ccd64a579b?w=300&h=300&fit=crop&auto=format',
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
                'image' => 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=300&h=300&fit=crop&auto=format',
                'children' => [
                    [
                        'name' => 'لوازم آشپزخانه',
                        'slug' => 'kitchenware',
                        'description' => 'لوازم آشپزخانه',
                        'meta_title' => 'لوازم آشپزخانه',
                        'meta_description' => 'لوازم آشپزخانه باکیفیت',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1584990347449-a9b9a7a5e8c5?w=300&h=300&fit=crop&auto=format',
                    ],
                    [
                        'name' => 'لوازم خانه',
                        'slug' => 'home-accessories',
                        'description' => 'لوازم منزل',
                        'meta_title' => 'لوازم خانه',
                        'meta_description' => 'لوازم کاربردی خانه',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1524758631624-e2822e304c36?w=300&h=300&fit=crop&auto=format',
                    ],
                    [
                        'name' => 'مبلمان',
                        'slug' => 'furniture',
                        'description' => 'مبلمان و دکوراسیون',
                        'meta_title' => 'مبلمان',
                        'meta_description' => 'مبلمان شیک و مدرن',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=300&h=300&fit=crop&auto=format',
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
                'image' => 'https://images.unsplash.com/photo-1522338242992-e1a54906a8da?w=300&h=300&fit=crop&auto=format',
                'children' => [
                    [
                        'name' => 'آرایشی',
                        'slug' => 'makeup',
                        'description' => 'محصولات آرایشی',
                        'meta_title' => 'آرایشی',
                        'meta_description' => 'محصولات آرایشی با کیفیت',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?w=300&h=300&fit=crop&auto=format',
                    ],
                    [
                        'name' => 'مراقبت پوست',
                        'slug' => 'skincare',
                        'description' => 'محصولات مراقبت از پوست',
                        'meta_title' => 'مراقبت پوست',
                        'meta_description' => 'محصولات مراقبت از پوست',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1556228578-8c89e6adf883?w=300&h=300&fit=crop&auto=format',
                    ],
                    [
                        'name' => 'عطر و ادکلن',
                        'slug' => 'perfumes',
                        'description' => 'عطر و ادکلن اصل',
                        'meta_title' => 'عطر و ادکلن',
                        'meta_description' => 'عطر و ادکلن اصل با ضمانت',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1541643600914-78b084683702?w=300&h=300&fit=crop&auto=format',
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
                'image' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=300&h=300&fit=crop&auto=format',
                'children' => [
                    [
                        'name' => 'تجهیزات ورزشی',
                        'slug' => 'sports-equipment',
                        'description' => 'تجهیزات ورزشی',
                        'meta_title' => 'تجهیزات ورزشی',
                        'meta_description' => 'تجهیزات ورزشی باکیفیت',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1517649763962-0c623066013b?w=300&h=300&fit=crop&auto=format',
                    ],
                    [
                        'name' => 'لباس ورزشی',
                        'slug' => 'sportswear',
                        'description' => 'لباس ورزشی',
                        'meta_title' => 'لباس ورزشی',
                        'meta_description' => 'لباس ورزشی با کیفیت',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1538805060514-97d9cc17730c?w=300&h=300&fit=crop&auto=format',
                    ],
                    [
                        'name' => 'چمدان و کیف مسافرتی',
                        'slug' => 'luggage',
                        'description' => 'چمدان و کیف مسافرتی',
                        'meta_title' => 'چمدان و کیف مسافرتی',
                        'meta_description' => 'چمدان و کیف مسافرتی',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=300&h=300&fit=crop&auto=format',
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
                'image' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=300&h=300&fit=crop&auto=format',
                'children' => [
                    [
                        'name' => 'کتاب',
                        'slug' => 'books',
                        'description' => 'کتاب‌های مختلف',
                        'meta_title' => 'کتاب',
                        'meta_description' => 'کتاب‌های متنوع',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=300&h=300&fit=crop&auto=format',
                    ],
                    [
                        'name' => 'لوازم تحریر',
                        'slug' => 'stationery',
                        'description' => 'لوازم تحریر',
                        'meta_title' => 'لوازم تحریر',
                        'meta_description' => 'لوازم تحریر اداری و مدرسه',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=300&h=300&fit=crop&auto=format',
                    ],
                    [
                        'name' => 'صنایع دستی',
                        'slug' => 'handicrafts',
                        'description' => 'صنایع دستی',
                        'meta_title' => 'صنایع دستی',
                        'meta_description' => 'صنایع دستی ایرانی',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=300&h=300&fit=crop&auto=format',
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
                'image' => 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=300&h=300&fit=crop&auto=format',
                'children' => [
                    [
                        'name' => 'اسباب‌بازی',
                        'slug' => 'toys',
                        'description' => 'اسباب‌بازی',
                        'meta_title' => 'اسباب‌بازی',
                        'meta_description' => 'اسباب‌بازی‌های فکری و آموزشی',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1558060370-d644479cb6f7?w=300&h=300&fit=crop&auto=format',
                    ],
                    [
                        'name' => 'لوازم نوزاد',
                        'slug' => 'baby-essentials',
                        'description' => 'لوازم نوزاد',
                        'meta_title' => 'لوازم نوزاد',
                        'meta_description' => 'لوازم ضروری نوزاد',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=300&h=300&fit=crop&auto=format',
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
                'image' => 'https://images.unsplash.com/photo-1530046339160-ce3e530c7d2f?w=300&h=300&fit=crop&auto=format',
                'children' => [
                    [
                        'name' => 'ابزار دستی',
                        'slug' => 'hand-tools',
                        'description' => 'ابزار دستی',
                        'meta_title' => 'ابزار دستی',
                        'meta_description' => 'ابزار دستی باکیفیت',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1572981779307-38b8cabb2407?w=300&h=300&fit=crop&auto=format',
                    ],
                    [
                        'name' => 'لوازم خودرو',
                        'slug' => 'car-accessories',
                        'description' => 'لوازم خودرو',
                        'meta_title' => 'لوازم خودرو',
                        'meta_description' => 'لوازم جانبی خودرو',
                        'status' => 1,
                        'image' => 'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?w=300&h=300&fit=crop&auto=format',
                    ],
                ]
            ],
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            $category = Category::firstOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
            $category->fill($categoryData)->save();
            $this->command->line("   ✓ {$category->name}");

            foreach ($children as $childData) {
                $childData['parent_id'] = $category->id;
                $child = Category::firstOrCreate(
                    ['slug' => $childData['slug']],
                    $childData
                );
                $child->fill($childData)->save();
                $this->command->line("     ✓ {$child->name}");
            }
        }

        $this->command->info('✅ Categories seeded successfully!');
    }
}
