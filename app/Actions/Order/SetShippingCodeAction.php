<?php

namespace App\Actions\Order;

use App\Models\Order;

class SetShippingCodeAction
{
    public function execute(Order $order, string $shippingCode): Order
    {
        $order->update(['shipping_code' => $shippingCode]);

        $order->recordHistory(
            'shipping_code_set',
            null,
            null,
            'کد رهگیری: ' . $shippingCode,
        );

        return $order;
    }
}
