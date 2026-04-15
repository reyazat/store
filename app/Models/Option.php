<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $type
 * @property int $sorted
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, OptionValue> $values
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, VariantOptionValue> $variantOptionValues
 * @method static Builder ordered()
 */
class Option extends AbstractModel
{
    use SoftDeletes;

    public array $filterable = [
        'id',
        'name',
        'slug',
        'type',
        'created_at',
    ];

    public array $sortable = [
        'id',
        'name',
        'sorted',
        'created_at',
    ];

    protected $fillable = [
        'name',
        'slug',
        'type',
        'sorted',
    ];

    protected $casts = [
        'sorted' => 'integer',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(OptionValue::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'option_product')
            ->withPivot('required')
            ->withTimestamps();
    }

    public function variantOptionValues(): HasMany
    {
        return $this->hasMany(VariantOptionValue::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sorted');
    }
}
