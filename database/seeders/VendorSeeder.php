<?php

namespace Database\Seeders;

use App\Models\Vendor;
use IamApi\Models\User;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Creates vendors (marketplace sellers) for e-commerce demo.
     * 
     * Vendors are product suppliers in the marketplace.
     * The platform vendor (دیجی‌کالا) is the main store.
     * Other vendors are third-party sellers.
     */
    public function run(): void
    {
        $this->command->info('🏪 Seeding vendors...');

        $vendors = [
            [
                'name' => 'دیجی‌کالا',
                'slug' => 'digikala',
                'description' => 'بزرگترین فروشگاه آنلاین ایران',
                'is_platform' => 1,
                'commission_rate' => 0.00,
                'approval_status' => 'approved',
                'status' => 1,
            ],
            [
                'name' => 'میهن استور',
                'slug' => 'mihan-store',
                'description' => 'فروشگاه لوازم الکترونیکی و دیجیتال',
                'is_platform' => 0,
                'commission_rate' => 10.00,
                'approval_status' => 'approved',
                'status' => 1,
            ],
            [
                'name' => 'اسنپ مارکت',
                'slug' => 'snapp-market',
                'description' => 'فروشگاه آنلاین کالاهای روزمره',
                'is_platform' => 0,
                'commission_rate' => 8.00,
                'approval_status' => 'approved',
                'status' => 1,
            ],
            [
                'name' => 'تکنولایف',
                'slug' => 'technolife',
                'description' => 'متخصص در فروش محصولات تکنولوژی',
                'is_platform' => 0,
                'commission_rate' => 12.00,
                'approval_status' => 'approved',
                'status' => 1,
            ],
            [
                'name' => 'بامیلو',
                'slug' => 'bamilo',
                'description' => 'سوپرمارکت آنلاین',
                'is_platform' => 0,
                'commission_rate' => 5.00,
                'approval_status' => 'approved',
                'status' => 1,
            ],
        ];

        foreach ($vendors as $vendorData) {
            $vendor = Vendor::firstOrCreate(['slug' => $vendorData['slug']], $vendorData);
            $this->command->line("   ✓ Created vendor: {$vendor->name}");
        }

        $this->command->info('✅ Vendors seeded successfully!');
    }
}
