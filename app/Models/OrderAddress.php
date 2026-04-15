<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAddress extends AbstractModel
{
    public $timestamps = true;

    protected $fillable = [
        'order_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'country',
        'province',
        'city',
        'address',
        'postal_code',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
