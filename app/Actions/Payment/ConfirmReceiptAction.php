<?php

namespace App\Actions\Payment;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;

class ConfirmReceiptAction
{
    public function execute(PaymentTransaction $transaction): Order
    {
        return DB::transaction(function () use ($transaction) {
            $transaction->update([
                'status'  => 'paid',
                'paid_at' => now(),
            ]);

            $order         = $transaction->order;
            $paymentMethod = PaymentMethod::where('driver', 'card_to_card')->first();

            $order->update([
                'payment_status' => 'paid',
                'status'         => $paymentMethod?->success_order_status ?? 'processing',
                'paid_at'        => now(),
            ]);

            return $order->fresh();
        });
    }
}
