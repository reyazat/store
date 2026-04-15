<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $product_id
 * @property int $vendor_id
 * @property string $status
 * @property float $base_price
 * @property int $min_order_qty
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Product $product
 * @property-read Vendor $vendor
 * @property-read Collection<int, ListingVariant> $listingVariants
 * @property-read Collection<int, ListingCustomerGroupPrice> $customerGroupPrices
 * @method static Builder active()
 */
class Listing extends AbstractModel
{
    public array $filterable = [
        'id',
        'product_id',
        'vendor_id',
        'status',
        'created_at',
    ];

    public array $sortable = [
        'id',
        'base_price',
        'min_order_qty',
        'created_at',
    ];
    protected $fillable = [
        'product_id',
        'vendor_id',
        'status',
        'base_price',
        'min_order_qty',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'vendor_id' => 'integer',
        'base_price' => 'decimal:4',
        'min_order_qty' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function listingVariants(): HasMany
    {
        return $this->hasMany(ListingVariant::class);
    }

    public function customerGroupPrices(): HasMany
    {
        return $this->hasMany(ListingCustomerGroupPrice::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
