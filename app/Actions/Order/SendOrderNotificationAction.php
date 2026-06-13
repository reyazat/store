<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Support\Facades\Log;

class SendOrderNotificationAction
{
    public function execute(Order $order, ?string $customMessage = null): bool
    {
        $order->loadMissing('user');

        if (!$order->user) {
            return false;
        }

        $statusModel = OrderStatus::findByKey($order->status);
        $message     = $customMessage ?? $statusModel?->notification_message ?? 'وضعیت سفارش شما به‌روزرسانی شد.';

        // Log the notification for now; real SMS/email integration happens via existing channels
        Log::info('Order notification', [
            'order_id'   => $order->id,
            'user_id'    => $order->user->id,
            'user_phone' => $order->user->phone ?? null,
            'message'    => $message,
        ]);

        $order->recordHistory(
            'notification_sent',
            null,
            null,
            $message,
        );

        return true;
    }
}
