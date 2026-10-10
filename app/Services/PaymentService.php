<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Gateways\AvreenGateway;
use App\Gateways\CardToCardGateway;
use App\Gateways\ZarinpalGateway;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentService
{
    public function __construct(private readonly OrderService $orderService) {}

    /**
     * Resolve the gateway driver for a given payment method.
     */
    public function resolveGateway(PaymentMethod $paymentMethod): PaymentGatewayInterface
    {
        return match ($paymentMethod->driver) {
            'avreen' => new AvreenGateway($paymentMethod),
            'card_to_card' => new CardToCardGateway($paymentMethod),
            'zarinpal' => new ZarinpalGateway($paymentMethod),
            default => throw new RuntimeException("درایور پرداخت '{$paymentMethod->driver}' پشتیبانی نمی‌شود"),
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

        $result = $gateway->initiate($order);

        $order->update(['payment_method' => $driver]);

        return $result;
    }

    /**
     * Verify callback from gateway and update order status accordingly.
     */
    public function verifyPayment(PaymentTransaction $transaction, array $callbackData): array
    {
        $paymentMethod = PaymentMethod::where('driver', $transaction->driver)->firstOrFail();
        $gateway = $this->resolveGateway($paymentMethod);

        $result = $gateway->verify($transaction, $callbackData);

        DB::transaction(function () use ($transaction, $result, $paymentMethod) {
            $transaction->loadMissing('order');
            $order = $transaction->order;

            if ($result['success']) {
                if ($paymentMethod->driver === 'card_to_card') {
                    // Receipt uploaded — awaiting admin confirmation; do NOT mark payment as paid yet
                    $this->orderService->updateOrderStatus($order, $paymentMethod->success_order_status);
                } else {
                    // Online gateway confirmed — mark as paid immediately
                    $order->update(['payment_status' => 'paid', 'paid_at' => now()]);
                    $this->orderService->updateOrderStatus($order, $paymentMethod->success_order_status);
                }
            } else {
                $cancelled = str_contains(strtolower($result['message']), 'لغو') ||
                             str_contains(strtolower($result['message']), 'cancel');

                $newStatus = $cancelled
                    ? $paymentMethod->cancel_order_status
                    : $paymentMethod->failure_order_status;

                $order->update(['payment_status' => 'failed']);
                $this->orderService->updateOrderStatus($order, $newStatus);
            }
        });

        return $result;
    }
}
