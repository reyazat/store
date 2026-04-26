<?php

namespace App\Repositories\Eloquents;

use App\Models\User;
use IamApi\Repositories\Eloquent\EloquentUserRepository;

/**
 * Custom User Repository that uses App\Models\User instead of IamApi\Models\User.
 *
 * This ensures all User instances returned have the guardName() method
 * for proper Spatie Permission guard detection in console/seed context.
 */
class UserRepository extends EloquentUserRepository
{
    // Constructor is inherited from parent - uses App\Models\User passed from service provider
}
