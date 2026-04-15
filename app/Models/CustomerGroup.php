<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $approve_customer
 * @property int $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, ListingCustomerGroupPrice> $listingPrices
 * @method static Builder active()
 */
class CustomerGroup extends AbstractModel
{
    public array $filterable = [
        'id',
        'name',
        'slug',
        'approve_customer',
        'status',
        'created_at',
    ];

    public array $sortable = [
        'id',
        'name',
        'status',
        'created_at',
    ];
    protected $fillable = [
        'name',
        'slug',
        'description',
        'approve_customer',
        'status',
    ];

    protected $casts = [
        'approve_customer' => 'integer',
        'status' => 'integer',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'customer_group_user');
    }

    public function listingPrices(): HasMany
    {
        return $this->hasMany(ListingCustomerGroupPrice::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }
}
