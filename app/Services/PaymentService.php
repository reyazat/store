<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Gateways\CardToCardGateway;
use App\Gateways\ZarinpalGateway;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentService
{
    /**
     * Resolve the gateway driver for a given payment method.
     */
    public function resolveGateway(PaymentMethod $paymentMethod): PaymentGatewayInterface
    {
        return match ($paymentMethod->driver) {
            'card_to_card' => new CardToCardGateway($paymentMethod),
            'zarinpal'     => new ZarinpalGateway($paymentMethod),
            default        => throw new RuntimeException("درایور پرداخت '{$paymentMethod->driver}' پشتیبانی نمی‌شود"),
        };
    }

    /**
     * Initiate payment for an order.
     *
     * @return array{transaction: PaymentTransaction, redirect_url?: string, instructions?: array}
     */
    public function initiatePayment(Order $order, string $driver): array
    {
        $paymentMethod = PaymentMethod::where('driver', $driver)
            ->where('is_active', true)
            ->firstOrFail();

        $gateway = $this->resolveGateway($paymentMethod);

        return $gateway->initiate($order);
    }

    /**
     * Verify callback from gateway and update order status accordingly.
     */
    public function verifyPayment(PaymentTransaction $transaction, array $callbackData): array
    {
        $paymentMethod = PaymentMethod::where('driver', $transaction->driver)->firstOrFail();
        $gateway       = $this->resolveGateway($paymentMethod);

        $result = $gateway->verify($transaction, $callbackData);

        DB::transaction(function () use ($transaction, $result, $paymentMethod) {
            $order = $transaction->order;

            if ($result['success']) {
                // Card-to-card: receipt uploaded, awaiting admin confirmation — do NOT mark as paid yet
                if ($paymentMethod->driver === 'card_to_card') {
                    $order->update([
                        'status' => $paymentMethod->success_order_status,
                    ]);
                } else {
                    $order->update([
                        'payment_status' => 'paid',
                        'status'         => $paymentMethod->success_order_status,
                        'paid_at'        => now(),
                    ]);
                }
            } else {
                $cancelled = str_contains(strtolower($result['message']), 'لغو') ||
                             str_contains(strtolower($result['message']), 'cancel');

                $order->update([
                    'payment_status' => 'failed',
                    'status'         => $cancelled
                        ? $paymentMethod->cancel_order_status
                        : $paymentMethod->failure_order_status,
                ]);
            }
        });

        return $result;
    }
}
