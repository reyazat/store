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
        'is_shipping',
        'sort_order',
        'notify_customer',
        'notification_message',
    ];

    protected $casts = [
        'is_cancellation' => 'boolean',
        'is_completion'   => 'boolean',
        'is_shipping'     => 'boolean',
        'notify_customer' => 'boolean',
        'sort_order'      => 'integer',
    ];

    private static array $keyCache = [];

    public static function findByKey(string $key): ?self
    {
        if (!array_key_exists($key, self::$keyCache)) {
            self::$keyCache[$key] = static::where('key', $key)->first();
        }

        return self::$keyCache[$key];
    }

    public static function clearCache(): void
    {
        self::$keyCache = [];
    }

    public static function allOrdered(): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('sort_order')->get();
    }

    protected static function booted(): void
    {
        static::saved(fn ()    => static::clearCache());
        static::deleted(fn ()  => static::clearCache());
    }
}
