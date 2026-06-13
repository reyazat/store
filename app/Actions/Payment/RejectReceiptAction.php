<?php

namespace App\Actions\Payment;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;

class RejectReceiptAction
{
    public function __construct(private readonly OrderService $orderService)
    {
    }

    public function execute(PaymentTransaction $transaction): Order
    {
        return DB::transaction(function () use ($transaction) {
            $transaction->update(['status' => 'failed']);

            $order         = $transaction->order;
            $paymentMethod = PaymentMethod::where('driver', $transaction->driver)->first();
            $failureStatus = $paymentMethod?->failure_order_status ?? 'payment_failed';

            // B2 fix: route through OrderService so payment_status_changed history is recorded
            $this->orderService->updatePaymentStatus($order, 'failed');
            $this->orderService->updateOrderStatus($order, $failureStatus);

            return $order->fresh();
        });
    }
}
