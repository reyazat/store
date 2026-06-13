<?php

namespace App\Actions\Payment;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;

class ConfirmReceiptAction
{
    public function __construct(private readonly OrderService $orderService)
    {
    }

    public function execute(PaymentTransaction $transaction): Order
    {
        return DB::transaction(function () use ($transaction) {
            $transaction->update([
                'status'  => 'paid',
                'paid_at' => now(),
            ]);

            $order         = $transaction->order;
            $paymentMethod = PaymentMethod::where('driver', $transaction->driver)->first();
            $successStatus = $paymentMethod?->success_order_status ?? 'processing';

            // B2 fix: route through OrderService so payment_status_changed history is recorded
            $this->orderService->updatePaymentStatus($order, 'paid');
            $this->orderService->updateOrderStatus($order, $successStatus);

            return $order->fresh();
        });
    }
}
