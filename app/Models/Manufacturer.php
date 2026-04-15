<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $image
 * @property int $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Product> $products
 * @method static Builder active()
 */
class Manufacturer extends AbstractModel
{
    use SoftDeletes;

    public array $filterable = [
        'id',
        'name',
        'slug',
        'status',
        'created_at',
    ];

    public array $sortable = [
        'id',
        'name',
        'created_at',
    ];

    protected $fillable = [
        'name',
        'slug',
        'image',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }
}
