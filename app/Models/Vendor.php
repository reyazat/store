<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $is_platform
 * @property float $commission_rate
 * @property int $status
 * @property string $approval_status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Listing> $listings
 * @property-read VendorPolicy|null $policy
 * @method static Builder active()
 */
class Vendor extends AbstractModel
{
    use SoftDeletes;

    public array $filterable = [
        'id',
        'name',
        'slug',
        'status',
        'approval_status',
        'is_platform',
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
        'is_platform',
        'commission_rate',
        'status',
        'approval_status',
    ];

    protected $casts = [
        'is_platform' => 'integer',
        'commission_rate' => 'decimal:2',
        'status' => 'integer',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'vendor_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function policy(): HasOne
    {
        return $this->hasOne(VendorPolicy::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }
}
