<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends AbstractModel
{
    protected bool $usesGlobalScopes = false;

    protected $fillable = [
        'user_id',
        'session_id',
        'coupon_id',
        'coupon_discount',
    ];

    protected $casts = [
        'coupon_discount' => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Subtotal before any coupon discount.
     */
    public function getSubtotalAttribute(): float
    {
        return (float) $this->items->sum(fn($item) => $item->price * $item->quantity);
    }

    /**
     * Final total after coupon discount applied.
     */
    public function getTotalAttribute(): float
    {
        return max(0.0, $this->subtotal - (float) ($this->coupon_discount ?? 0));
    }

    public function getItemsCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }
}
