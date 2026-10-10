<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'order_id',
        'driver',
        'client_txn_id',
        'gateway_token',
        'gateway_ref',
        'amount',
        'status',
        'receipt_path',
        'card_last_four',
        'gateway_response',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
