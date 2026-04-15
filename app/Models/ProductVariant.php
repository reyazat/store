<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $product_id
 * @property string $sku
 * @property string|null $barcode
 * @property float|null $weight
 * @property float|null $length
 * @property float|null $width
 * @property float|null $height
 * @property int $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Product $product
 * @property-read Collection<int, OptionValue> $optionValues
 * @property-read Collection<int, VariantOptionValue> $variantOptionValues
 * @property-read Collection<int, ListingVariant> $listingVariants
 * @method static Builder active()
 */
class ProductVariant extends AbstractModel
{
    public array $filterable = [
        'id',
        'product_id',
        'sku',
        'status',
        'created_at',
    ];

    public array $sortable = [
        'id',
        'sku',
        'created_at',
    ];
    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'weight',
        'length',
        'width',
        'height',
        'status',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'weight' => 'decimal:3',
        'length' => 'decimal:3',
        'width' => 'decimal:3',
        'height' => 'decimal:3',
        'status' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(OptionValue::class, 'variant_option_values', 'variant_id', 'option_value_id')
            ->withPivot('option_id')
            ->withTimestamps();
    }

    public function variantOptionValues(): HasMany
    {
        return $this->hasMany(VariantOptionValue::class, 'variant_id');
    }

    public function listingVariants(): HasMany
    {
        return $this->hasMany(ListingVariant::class, 'variant_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }
}
