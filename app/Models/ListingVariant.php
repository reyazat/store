<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $listing_id
 * @property int $variant_id
 * @property float|null $price_override
 * @property int $stock_quantity
 * @property int $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Listing $listing
 * @property-read ProductVariant $variant
 * @method static Builder active()
 */
class ListingVariant extends AbstractModel
{
    protected bool $usesGlobalScopes = false;

    public array $filterable = [
        'id',
        'listing_id',
        'variant_id',
        'status',
        'created_at',
    ];

    public array $sortable = [
        'id',
        'stock_quantity',
        'status',
        'created_at',
    ];

    protected $fillable = [
        'listing_id',
        'variant_id',
        'price_override',
        'stock_quantity',
        'status',
    ];

    protected $casts = [
        'listing_id' => 'integer',
        'variant_id' => 'integer',
        'price_override' => 'decimal:4',
        'stock_quantity' => 'integer',
        'status' => 'integer',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }
}
