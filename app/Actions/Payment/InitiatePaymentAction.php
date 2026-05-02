<?php

namespace App\Actions\Payment;

use App\Models\Order;
use App\Services\PaymentService;

class InitiatePaymentAction
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    public function execute(Order $order, string $driver): array
    {
        return $this->paymentService->initiatePayment($order, $driver);
    }
}
