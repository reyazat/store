<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'driver'         => $this->driver,
            'gateway_ref'    => $this->gateway_ref,
            'amount'         => $this->amount,
            'status'         => $this->status,
            'receipt_path'   => $this->receipt_path ? asset('storage/' . $this->receipt_path) : null,
            'card_last_four' => $this->card_last_four,
            'paid_at'        => $this->paid_at,
            'created_at'     => $this->created_at,
        ];
    }
}
