<?php

namespace App\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;

class CardToCardGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly PaymentMethod $paymentMethod)
    {
    }

    public function initiate(Order $order): array
    {
        $config = $this->paymentMethod->config ?? [];

        $transaction = $order->paymentTransactions()->create([
            'driver' => $this->getDriverName(),
            'amount' => $order->total,
            'status' => 'pending',
        ]);

        return [
            'transaction'  => $transaction,
            'instructions' => [
                'card_number'  => $config['card_number'] ?? '',
                'card_holder'  => $config['card_holder'] ?? '',
                'bank_name'    => $config['bank_name'] ?? '',
                'amount'       => $order->total,
                'instructions' => $config['instructions'] ?? '',
            ],
        ];
    }

    /**
     * Card-to-card verification is manual (admin confirms after receipt upload).
     * This method marks the transaction as pending (receipt uploaded).
     */
    public function verify(PaymentTransaction $transaction, array $callbackData): array
    {
        $receiptPath   = $callbackData['receipt_path'] ?? null;
        $cardLastFour  = $callbackData['card_last_four'] ?? null;

        if (!$receiptPath) {
            return ['success' => false, 'message' => 'رسید پرداخت ارسال نشده است'];
        }

        $transaction->update([
            'receipt_path'   => $receiptPath,
            'card_last_four' => $cardLastFour,
            'status'         => 'pending',
        ]);

        return ['success' => true, 'message' => 'رسید پرداخت دریافت شد و در انتظار تایید مدیر است'];
    }

    public function getDriverName(): string
    {
        return 'card_to_card';
    }
}
