<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'driver'               => 'card_to_card',
                'label'                => 'کارت به کارت',
                'description'          => 'انتقال وجه مستقیم به شماره کارت فروشگاه',
                'is_active'            => false,
                'sort_order'           => 10,
                'config'               => json_encode([
                    'card_number'     => '',
                    'card_holder'     => '',
                    'bank_name'       => '',
                    'instructions'    => '',
                ]),
                'success_order_status' => 'pending_confirmation',
                'failure_order_status' => 'payment_failed',
                'cancel_order_status'  => 'cancelled',
            ],
            [
                'driver'               => 'zarinpal',
                'label'                => 'درگاه پرداخت زرین‌پال',
                'description'          => 'پرداخت آنلاین امن با کارت‌های عضو شبکه شتاب',
                'is_active'            => false,
                'sort_order'           => 20,
                'config'               => json_encode([
                    'merchant_id' => '',
                    'sandbox'     => true,
                ]),
                'success_order_status' => 'processing',
                'failure_order_status' => 'payment_failed',
                'cancel_order_status'  => 'cancelled',
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(['driver' => $method['driver']], $method);
        }
    }
}
