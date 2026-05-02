<?php

namespace Database\Seeders;

use App\Models\OrderStatus;
use Illuminate\Database\Seeder;

class OrderStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['key' => 'pending',              'label' => 'در انتظار پرداخت',     'color' => '#f59e0b', 'sort_order' => 10, 'is_cancellation' => false, 'is_completion' => false, 'notify_customer' => false],
            ['key' => 'pending_confirmation', 'label' => 'در انتظار تایید',      'color' => '#8b5cf6', 'sort_order' => 20, 'is_cancellation' => false, 'is_completion' => false, 'notify_customer' => true,  'notification_message' => 'سفارش شما دریافت شد و در انتظار تایید پرداخت است.'],
            ['key' => 'processing',           'label' => 'در حال آماده‌سازی',   'color' => '#3b82f6', 'sort_order' => 30, 'is_cancellation' => false, 'is_completion' => false, 'notify_customer' => true,  'notification_message' => 'پرداخت شما تایید شد و سفارشتان در حال آماده‌سازی است.'],
            ['key' => 'shipped',              'label' => 'ارسال شده',            'color' => '#06b6d4', 'sort_order' => 40, 'is_cancellation' => false, 'is_completion' => false, 'notify_customer' => true,  'notification_message' => 'سفارش شما ارسال شد.'],
            ['key' => 'delivered',            'label' => 'تحویل داده شده',       'color' => '#22c55e', 'sort_order' => 50, 'is_cancellation' => false, 'is_completion' => true,  'notify_customer' => true,  'notification_message' => 'سفارش شما تحویل داده شد.'],
            ['key' => 'payment_failed',       'label' => 'خطا در پرداخت',        'color' => '#ef4444', 'sort_order' => 60, 'is_cancellation' => false, 'is_completion' => false, 'notify_customer' => true,  'notification_message' => 'متاسفانه پرداخت شما با خطا مواجه شد.'],
            ['key' => 'cancelled',            'label' => 'لغو شده',              'color' => '#6b7280', 'sort_order' => 70, 'is_cancellation' => true,  'is_completion' => false, 'notify_customer' => true,  'notification_message' => 'سفارش شما لغو شد.'],
            ['key' => 'refunded',             'label' => 'مسترد شده',            'color' => '#ec4899', 'sort_order' => 80, 'is_cancellation' => false, 'is_completion' => false, 'notify_customer' => true,  'notification_message' => 'مبلغ سفارش شما مسترد خواهد شد.'],
        ];

        foreach ($statuses as $status) {
            OrderStatus::updateOrCreate(['key' => $status['key']], $status);
        }
    }
}
