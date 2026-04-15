<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property float $rate
 * @property string $type
 * @property int $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, VendorPolicy> $vendorPolicies
 * @method static Builder active()
 */
class TaxClass extends AbstractModel
{
    public array $filterable = [
        'id',
        'name',
        'type',
        'status',
        'created_at',
    ];

    public array $sortable = [
        'id',
        'name',
        'rate',
        'created_at',
    ];
    protected $fillable = [
        'name',
        'rate',
        'type',
        'status',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'status' => 'integer',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function vendorPolicies(): HasMany
    {
        return $this->hasMany(VendorPolicy::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }
}
