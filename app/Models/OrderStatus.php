<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    protected $fillable = [
        'key',
        'label',
        'color',
        'is_cancellation',
        'is_completion',
        'sort_order',
        'notify_customer',
        'notification_message',
    ];

    protected $casts = [
        'is_cancellation' => 'boolean',
        'is_completion'   => 'boolean',
        'notify_customer' => 'boolean',
        'sort_order'      => 'integer',
    ];

    public static function findByKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }

    public static function allOrdered(): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('sort_order')->get();
    }
}
