<?php

namespace App\Actions\Payment;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;

class RejectReceiptAction
{
    public function execute(PaymentTransaction $transaction): Order
    {
        return DB::transaction(function () use ($transaction) {
            $transaction->update([
                'status' => 'failed',
            ]);

            $order         = $transaction->order;
            $paymentMethod = PaymentMethod::where('driver', 'card_to_card')->first();

            $order->update([
                'payment_status' => 'failed',
                'status'         => $paymentMethod?->failure_order_status ?? 'payment_failed',
            ]);

            return $order->fresh();
        });
    }
}
