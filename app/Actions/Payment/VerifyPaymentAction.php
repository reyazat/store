<?php

namespace App\Actions\Payment;

use App\Models\PaymentTransaction;
use App\Services\PaymentService;

class VerifyPaymentAction
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    public function execute(PaymentTransaction $transaction, array $callbackData): array
    {
        return $this->paymentService->verifyPayment($transaction, $callbackData);
    }
}
