<?php

namespace Database\Seeders;

use App\Models\Vendor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏪 Seeding vendors...');

        // Get some users to assign as vendor owners
        $admin = User::where('email', 'admin@shop.com')->first();
        $john = User::where('email', 'john@example.com')->first();
        $jane = User::where('email', 'jane@example.com')->first();

        // If users don't exist, create them
        if (!$admin) {
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@shop.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }
        if (!$john) {
            $john = User::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }
        if (!$jane) {
            $jane = User::create([
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }

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
            
            // Add owner as vendor user if not already attached
            if (!$vendor->users()->where('user_id', $admin->id)->exists()) {
                $vendor->users()->attach($admin->id, [
                    'role' => 'owner',
                ]);
            }
            
            $this->command->line("   ✓ Created vendor: {$vendor->name}");
        }

        $this->command->info('✅ Vendors seeded successfully!');
    }
}
