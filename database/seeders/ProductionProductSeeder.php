<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\TaxClass;
use App\Models\Listing;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Production Product Seeder - Real products with Unsplash images
 */
class ProductionProductSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📦 Seeding production products...');

        $electronics = Category::where('slug', 'electronics')->first();
        $mobileCat = Category::where('slug', 'mobile-phones')->first();
        $laptopCat = Category::where('slug', 'laptops')->first();
        $clothing = Category::where('slug', 'clothing')->first();

        $apple = Manufacturer::where('slug', 'apple')->first();
        $samsung = Manufacturer::where('slug', 'samsung')->first();
        $xiaomi = Manufacturer::where('slug', 'xiaomi')->first();

        $standardTax = TaxClass::firstOrCreate(['name' => 'استاندارد'], ['rate' => 9]);

        $products = [
            [
                'name' => 'آیفون ۱۵ پرو',
                'slug' => 'iphone-15-pro',
                'base_sku' => 'APL-IP15P-128',
                'short_description' => 'گوشی هوشمند پرچمدار اپل با تراشه A17 Pro',
                'description' => 'آیفون ۱۵ پرو با تراشه A17 Pro، دوربین پیشرفته ۴۸ مگاپیکسلی، بدنه تیتانیومی و پشتیبانی از USB-C. بهترین انتخاب برای کاربران حرفه‌ای.',
                'main_image' => 'https://images.unsplash.com/photo-1696446701796-da61225697cc?w=800&q=80',
                'manufacturer_id' => $apple?->id,
                'tax_class_id' => $standardTax->id,
                'categories' => [$electronics?->id, $mobileCat?->id],
                'weight' => 187,
                'status' => 1,
                'price' => 65000000,
                'stock' => 10,
                'images' => [
                    'https://images.unsplash.com/photo-1696446701796-da61225697cc?w=800&q=80',
                    'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=800&q=80',
                ],
            ],
            [
                'name' => 'سامسونگ گلکسی S24 اولترا',
                'slug' => 'samsung-galaxy-s24-ultra',
                'base_sku' => 'SAM-S24U-256',
                'short_description' => 'قدرتمندترین گوشی سامسونگ با قلم S Pen',
                'description' => 'گلکسی S24 اولترا با دوربین ۲۰۰ مگاپیکسلی، پردازنده اسنپدراگون ۸ نسل ۳، صفحه نمایش ۶.۸ اینچی و قلم S Pen.',
                'main_image' => 'https://images.unsplash.com/photo-1610945265078-3858a0828671?w=800&q=80',
                'manufacturer_id' => $samsung?->id,
                'tax_class_id' => $standardTax->id,
                'categories' => [$electronics?->id, $mobileCat?->id],
                'weight' => 234,
                'status' => 1,
                'price' => 58000000,
                'stock' => 15,
                'images' => [
                    'https://images.unsplash.com/photo-1610945265078-3858a0828671?w=800&q=80',
                ],
            ],
            [
                'name' => 'شیائومی ۱۴',
                'slug' => 'xiaomi-14',
                'base_sku' => 'XIA-14-256',
                'short_description' => 'پرچمدار مقرون‌به‌صرفه با دوربین لایکا',
                'description' => 'شیائومی ۱۴ با دوربین همکاری لایکا، شارژ سریع ۹۰ واتی، و عملکرد عالی با قیمت مناسب.',
                'main_image' => 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=800&q=80',
                'manufacturer_id' => $xiaomi?->id,
                'tax_class_id' => $standardTax->id,
                'categories' => [$electronics?->id, $mobileCat?->id],
                'weight' => 193,
                'status' => 1,
                'price' => 28000000,
                'stock' => 20,
                'images' => [],
            ],
            [
                'name' => 'مک‌بوک پرو ۱۴',
                'slug' => 'macbook-pro-14',
                'base_sku' => 'APL-MBP14-M3',
                'short_description' => 'لپ‌تاپ حرفه‌ای اپل با تراشه M3',
                'description' => 'مک‌بوک پرو ۱۴ اینچی با تراشه M3، صفحه نمایش Liquid Retina XDR، عمر باتری تا ۱۸ ساعت.',
                'main_image' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800&q=80',
                'manufacturer_id' => $apple?->id,
                'tax_class_id' => $standardTax->id,
                'categories' => [$electronics?->id, $laptopCat?->id],
                'weight' => 1600,
                'status' => 1,
                'price' => 85000000,
                'stock' => 5,
                'images' => [
                    'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800&q=80',
                ],
            ],
        ];

        foreach ($products as $productData) {
            $categories = $productData['categories'] ?? [];
            $images = $productData['images'] ?? [];
            $price = $productData['price'] ?? 0;
            $stock = $productData['stock'] ?? 0;
            unset($productData['categories'], $productData['images'], $productData['price'], $productData['stock']);

            // Remove null manufacturer
            if (empty($productData['manufacturer_id'])) {
                unset($productData['manufacturer_id']);
            }

            $product = Product::firstOrCreate(
                ['slug' => $productData['slug']],
                $productData
            );

            // Attach categories
            if (!empty($categories)) {
                $validCategories = array_filter($categories);
                if (!$product->categories()->exists() && !empty($validCategories)) {
                    $product->categories()->sync($validCategories);
                }
            }

            // Add images
            if (!empty($images)) {
                if (!$product->images()->exists()) {
                    foreach ($images as $index => $imageUrl) {
                        ProductImage::firstOrCreate(
                            [
                                'product_id' => $product->id,
                                'file_name' => $imageUrl,
                            ],
                            ['sorted' => $index + 1]
                        );
                    }
                }
            }

            // Create listing for product (needs a vendor)
            $defaultVendor = Vendor::firstOrCreate(
                ['slug' => 'default'],
                [
                    'name' => 'فروشگاه اصلی',
                    'is_platform' => 1,
                    'commission_rate' => 0,
                    'approval_status' => 'approved',
                    'status' => 1,
                ]
            );

            Listing::firstOrCreate(
                ['product_id' => $product->id, 'vendor_id' => $defaultVendor->id],
                [
                    'base_price' => $price,
                    'min_order_qty' => 1,
                    'status' => 'active',
                ]
            );

            $this->command->line("   ✓ {$product->name} - " . number_format($price) . " تومان");
        }

        $this->command->info('✅ Production products seeded!');
    }
}
