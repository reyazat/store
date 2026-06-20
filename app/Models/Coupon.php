<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends AbstractModel
{
    use SoftDeletes;

    protected bool $usesGlobalScopes = false;

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'description',
        'type',
        'value',
        'min_purchase',
        'max_discount',
        'usage_limit',
        'usage_per_user',
        'used_count',
        'starts_at',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'value' => 'decimal:4',
        'min_purchase' => 'decimal:4',
        'max_discount' => 'decimal:4',
        'usage_limit' => 'integer',
        'usage_per_user' => 'integer',
        'used_count' => 'integer',
        'status' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(\IamApi\Models\Organization::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Check if coupon is platform-level (applies to all stores)
     */
    public function isPlatformLevel(): bool
    {
        return $this->organization_id === null;
    }

    /**
     * Check if coupon belongs to a specific store
     */
    public function isStoreLevel(): bool
    {
        return $this->organization_id !== null;
    }

    public function isValid(): bool
    {
        if (!$this->status) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if ($this->type === 'fixed') {
            $discount = $this->value;
        } else {
            $discount = ($amount * $this->value) / 100;
        }

        if ($this->max_discount && $discount > $this->max_discount) {
            $discount = $this->max_discount;
        }

        return min($discount, $amount);
    }
}
