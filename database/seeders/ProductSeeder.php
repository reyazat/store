<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\TaxClass;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📦 Seeding products...');

        $electronics = Category::where('slug', 'electronics')->first();
        $mobileCategory = Category::where('slug', 'mobile-tablets')->first();
        $laptopCategory = Category::where('slug', 'laptops-computers')->first();
        $fashion = Category::where('slug', 'fashion')->first();
        $mensCategory = Category::where('slug', 'mens')->first();
        $womensCategory = Category::where('slug', 'womens')->first();

        $apple = Manufacturer::where('slug', 'apple')->first();
        $samsung = Manufacturer::where('slug', 'samsung')->first();
        $sony = Manufacturer::where('slug', 'sony')->first();
        $nike = Manufacturer::where('slug', 'nike')->first();
        $adidas = Manufacturer::where('slug', 'adidas')->first();

        $standardTax = TaxClass::where('name', 'استاندارد')->first();

        $products = [
            [
                'name' => 'iPhone 15 Pro',
                'slug' => 'iphone-15-pro',
                'base_sku' => 'APL-IP15P-128',
                'short_description' => 'جدیدترین آیفون با تراشه A17 Pro',
                'description' => 'آیفون 15 پرو با تراشه A17 Pro، دوربین پیشرفته، و طراحی تیتانیومی. بهترین تجربه کاربری در دنیای موبایل.',
                'main_image' => 'https://images.unsplash.com/photo-1696446701796-da61225697cc?w=800&q=80',
                'manufacturer_id' => $apple->id,
                'tax_class_id' => $standardTax->id,
                'weight' => 187.0,
                'length' => 146.6,
                'width' => 70.6,
                'sorted' => 100,
                'height' => 8.25,
                'meta_title' => 'iPhone 15 Pro - فروشگاه',
                'meta_description' => 'خرید iPhone 15 Pro با بهترین قیمت و گارانتی',
                'status' => 1,
                'discount_percent' => 10,
                'discount_start_at' => now(),
                'discount_end_at' => now()->addDays(30),
                'categories' => [$electronics->id, $mobileCategory->id],
                'images' => [
                    'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=800&q=80',
                    'https://images.unsplash.com/photo-1696430284878-1c9b7f4e5c5a?w=800&q=80',
                    'https://images.unsplash.com/photo-1696430284878-1c9b7f4e5c5a?w=800&q=80',
                ],
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'slug' => 'samsung-galaxy-s24-ultra',
                'base_sku' => 'SAM-GS24U-256',
                'short_description' => 'قدرتمندترین گلکسی با S Pen',
                'description' => 'گلکسی S24 اولترا با قلم S Pen، دوربین 200 مگاپیکسلی، و پردازنده اسنپدراگون 8 نسل 3.',
                'main_image' => 'https://images.unsplash.com/photo-1610945265078-3858a0828671?w=800&q=80',
                'manufacturer_id' => $samsung->id,
                'tax_class_id' => $standardTax->id,
                'weight' => 234.0,
                'length' => 162.3,
                'width' => 79.0,
                'height' => 8.6,
                'sorted' => 90,
                'meta_title' => 'Samsung Galaxy S24 Ultra - فروشگاه',
                'meta_description' => 'خرید Samsung Galaxy S24 Ultra با بهترین قیمت',
                'status' => 1,
                'discount_percent' => 15,
                'discount_start_at' => now(),
                'discount_end_at' => now()->addDays(25),
                'categories' => [$electronics->id, $mobileCategory->id],
                'images' => [
                    'https://images.unsplash.com/photo-1610945415295-d9bbf067e59c?w=800&q=80',
                    'https://images.unsplash.com/photo-1610945264803-c22b62d2a7b3?w=800&q=80',
                ],
            ],
            [
                'name' => 'MacBook Pro 14"',
                'slug' => 'macbook-pro-14',
                'base_sku' => 'APL-MBP14-M3',
                'short_description' => 'لپ‌تاپ حرفه‌ای با تراشه M3',
                'description' => 'مک‌بوک پرو 14 اینچی با تراشه M3، صفحه نمایش Liquid Retina XDR، و عمر باتری فوق‌العاده.',
                'main_image' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800&q=80',
                'manufacturer_id' => $apple->id,
                'tax_class_id' => $standardTax->id,
                'weight' => 1600.0,
                'length' => 311.5,
                'width' => 220.0,
                'height' => 15.5,
                'sorted' => 95,
                'meta_title' => 'MacBook Pro 14" - فروشگاه',
                'meta_description' => 'خرید MacBook Pro 14 اینچ با تراشه M3',
                'status' => 1,
                'categories' => [$electronics->id, $laptopCategory->id],
                'images' => [
                    'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=800&q=80',
                    'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=800&q=80',
                ],
            ],
            [
                'name' => 'Nike Air Max 90',
                'slug' => 'nike-air-max-90',
                'base_sku' => 'NIKE-AM90-WHT',
                'short_description' => 'کلاسیک مدرن با طراحی خاص',
                'description' => 'نایک ایر مکس 90 با طراحی کلاسیک، راحتی بی‌نظیر، و کیفیت ساخت عالی. مناسب برای استفاده روزمره و ورزش.',
                'main_image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800&q=80',
                'manufacturer_id' => $nike->id,
                'tax_class_id' => $standardTax->id,
                'weight' => 320.0,
                'length' => 30.0,
                'width' => 20.0,
                'height' => 10.0,
                'sorted' => 80,
                'meta_title' => 'Nike Air Max 90 - فروشگاه',
                'meta_description' => 'خرید Nike Air Max 90 با بهترین قیمت',
                'status' => 1,
                'categories' => [$fashion->id, $mensCategory->id],
                'images' => [
                    'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=800&q=80',
                    'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=800&q=80',
                ],
            ],
            [
                'name' => 'Adidas Ultraboost 22',
                'slug' => 'adidas-ultraboost-22',
                'base_sku' => 'ADI-UB22-BLK',
                'short_description' => 'کفش دویدن با فناوری Boost',
                'description' => 'آدیداس اولترابوست 22 با فناوری Boost برای راحتی حداکثری، مناسب برای دویدن و استفاده روزمره.',
                'main_image' => 'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=800&q=80',
                'manufacturer_id' => $adidas->id,
                'tax_class_id' => $standardTax->id,
                'weight' => 340.0,
                'length' => 32.0,
                'width' => 22.0,
                'height' => 12.0,
                'meta_title' => 'Adidas Ultraboost 22 - فروشگاه',
                'meta_description' => 'خرید Adidas Ultraboost 22 با بهترین قیمت',
                'status' => 1,
                'categories' => [$fashion->id, $mensCategory->id],
                'images' => [
                    'https://images.unsplash.com/photo-1605408499391-6368c628ef42?w=800&q=80',
                ],
            ],
            [
                'name' => 'Sony WH-1000XM5',
                'slug' => 'sony-wh-1000xm5',
                'base_sku' => 'SONY-WH1KXM5-BLK',
                'short_description' => 'بهترین هدست نویز کنسلینگ',
                'description' => 'هدست سونی WH-1000XM5 با کیفیت صدای فوق‌العاده، نویز کنسلینگ پیشرفته، و عمر باتری 30 ساعته.',
                'main_image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&q=80',
                'manufacturer_id' => $sony->id,
                'tax_class_id' => $standardTax->id,
                'weight' => 250.0,
                'length' => 25.0,
                'width' => 20.0,
                'height' => 30.0,
                'meta_title' => 'Sony WH-1000XM5 - فروشگاه',
                'meta_description' => 'خرید Sony WH-1000XM5 با بهترین قیمت',
                'status' => 1,
                'categories' => [$electronics->id],
                'images' => [
                    'https://images.unsplash.com/photo-1583394838336-acd977736f90?w=800&q=80',
                    'https://images.unsplash.com/photo-1487215078519-e21cc028cb29?w=800&q=80',
                ],
            ],
        ];

        foreach ($products as $productData) {
            $categories = $productData['categories'];
            $images = $productData['images'] ?? [];
            unset($productData['categories'], $productData['images']);

            // Add random discount to 60% of products
            if (rand(1, 100) <= 60) {
                $productData['discount_percent'] = rand(10, 50); // 10% to 50% discount
                $productData['discount_start_at'] = Carbon::now()->subDays(rand(1, 7)); // Started 1-7 days ago
                $productData['discount_end_at'] = Carbon::now()->addDays(rand(7, 30)); // Ends in 7-30 days
            }

            $product = Product::firstOrCreate(['slug' => $productData['slug']], $productData);

            // Attach categories if not already attached
            if (!$product->categories()->exists()) {
                $product->categories()->attach($categories);
            }

            // Add product images if not already added
            if (!$product->images()->exists()) {
                foreach ($images as $index => $image) {
                    ProductImage::firstOrCreate([
                        'product_id' => $product->id,
                        'file_name' => $image,
                    ], [
                        'sorted' => $index + 1,
                    ]);
                }
            }

            $discountInfo = $product->discount_percent ? " ({$product->discount_percent}% off)" : '';
            $this->command->line("   ✓ Created product: {$product->name}{$discountInfo}");
        }

        $this->command->info('✅ Products seeded successfully!');
    }
}
