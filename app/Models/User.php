<?php

namespace App\Models;

use IamApi\Models\User as BaseUser;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends BaseUser
{
    /**
     * The guard name for Spatie Permission.
     * Required for proper guard detection in console/seed context.
     */
    protected ?string $guard_name = 'api';

    /**
     * Return the guard name for Spatie Permission.
     */
    public function guardName(): string
    {
        return 'api';
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }
}
