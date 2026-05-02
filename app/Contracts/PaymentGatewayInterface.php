<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\PaymentTransaction;

interface PaymentGatewayInterface
{
    /**
     * Initiate payment and return redirect URL (or instructions for card-to-card).
     *
     * @return array{transaction: PaymentTransaction, redirect_url?: string, instructions?: array}
     */
    public function initiate(Order $order): array;

    /**
     * Verify a completed payment.
     *
     * @param  array<string, mixed> $callbackData  Query params from gateway callback
     * @return array{success: bool, ref_id?: string, message: string}
     */
    public function verify(PaymentTransaction $transaction, array $callbackData): array;

    public function getDriverName(): string;
}
