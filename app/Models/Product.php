<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $base_sku
 * @property string|null $short_description
 * @property string|null $description
 * @property string $main_image
 * @property int|null $manufacturer_id
 * @property int $tax_class_id
 * @property float $weight
 * @property float $length
 * @property float $width
 * @property float $height
 * @property Carbon|null $date_available
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property int $viewed
 * @property int $sorted
 * @property int $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Manufacturer|null $manufacturer
 * @property-read TaxClass $taxClass
 * @property-read Collection<int, Category> $categories
 * @property-read Collection<int, ProductImage> $images
 * @property-read Collection<int, ProductVariant> $variants
 * @property-read Collection<int, Listing> $listings
 * @property-read Collection<int, Option> $options
 * @property-read Collection<int, Product> $relatedProducts
 * @property-read Collection<int, Product> $relatedToProducts
 * @method static Builder active()
 */
class Product extends AbstractModel
{
    use SoftDeletes;

    public array $filterable = [
        'id',
        'name',
        'slug',
        'base_sku',
        'manufacturer_id',
        'tax_class_id',
        'status',
        'created_at',
    ];

    public array $sortable = [
        'id',
        'name',
        'sorted',
        'viewed',
        'created_at',
    ];

    protected $fillable = [
        'name',
        'slug',
        'base_sku',
        'short_description',
        'description',
        'main_image',
        'manufacturer_id',
        'tax_class_id',
        'weight',
        'length',
        'width',
        'height',
        'date_available',
        'meta_title',
        'meta_description',
        'viewed',
        'sorted',
        'status',
        'discount_percent',
        'discount_start_at',
        'discount_end_at',
    ];

    protected $casts = [
        'date_available' => 'date',
        'weight' => 'decimal:3',
        'length' => 'decimal:3',
        'width' => 'decimal:3',
        'height' => 'decimal:3',
        'viewed' => 'integer',
        'sorted' => 'integer',
        'status' => 'integer',
        'discount_percent' => 'integer',
        'discount_start_at' => 'datetime',
        'discount_end_at' => 'datetime',
    ];

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function taxClass(): BelongsTo
    {
        return $this->belongsTo(TaxClass::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product')
            ->withTimestamps();
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(Option::class, 'option_product')
            ->withPivot('required')
            ->withTimestamps();
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(__CLASS__, 'related_products', 'product_id', 'related_product_id')
            ->withTimestamps();
    }

    public function relatedToProducts(): BelongsToMany
    {
        return $this->belongsToMany(__CLASS__, 'related_products', 'related_product_id', 'product_id')
            ->withTimestamps();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Check if the product has an active discount.
     */
    public function hasActiveDiscount(): bool
    {
        if (!$this->discount_percent || $this->discount_percent <= 0) {
            return false;
        }

        $now = now();

        if ($this->discount_start_at && $now->lt($this->discount_start_at)) {
            return false;
        }

        if ($this->discount_end_at && $now->gt($this->discount_end_at)) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the discounted price.
     */
    public function calculateDiscountedPrice(float $originalPrice): float
    {
        if (!$this->hasActiveDiscount()) {
            return $originalPrice;
        }

        $discountAmount = ($originalPrice * $this->discount_percent) / 100;
        return max(0, $originalPrice - $discountAmount);
    }

    /**
     * Scope to filter products with active discounts.
     */
    public function scopeHasActiveDiscount(Builder $query): Builder
    {
        return $query->whereNotNull('discount_percent')
            ->where('discount_percent', '>', 0)
            ->where(function ($q) {
                $q->whereNull('discount_start_at')
                    ->orWhere('discount_start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('discount_end_at')
                    ->orWhere('discount_end_at', '>=', now());
            });
    }

    /**
     * Scope to order by discount percentage (highest first).
     */
    public function scopeOrderByDiscount(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('discount_percent', $direction);
    }
}
