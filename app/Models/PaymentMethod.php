<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'driver',
        'label',
        'description',
        'is_active',
        'sort_order',
        'config',
        'success_order_status',
        'failure_order_status',
        'cancel_order_status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected function config(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): array {
                $decoded = $value;

                for ($attempt = 0; $attempt < 2 && is_string($decoded); $attempt++) {
                    $decoded = json_decode($decoded, true);
                }

                return is_array($decoded) ? $decoded : [];
            },
            set: function (mixed $value): string {
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    $value = is_array($decoded) ? $decoded : [];
                }

                return json_encode(is_array($value) ? $value : [], JSON_UNESCAPED_UNICODE);
            },
        );
    }

    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
