<?php

namespace IamApi\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string|null $description
 * @property string|null $logo
 * @property string $status active|inactive|suspended
 * @property Collection $users
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Organization extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'logo', 'status'];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function users(): HasMany
    {
        // Use the configured auth User model so that Spatie role checks (hasRole, getRoleNames)
        // use the correct model_type (e.g. 'App\Models\User' instead of 'IamApi\Models\User').
        // This is critical in apps where App\Models\User extends IamApi\Models\User.
        $userModel = config('auth.providers.users.model', User::class);

        return $this->hasMany($userModel, 'organization_id', 'id');
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class, 'organization_id', 'id');
    }

    public function domains(): HasMany
    {
        return $this->hasMany(OrganizationDomain::class, 'organization_id', 'id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class, 'organization_id', 'id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'organization_id', 'id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'organization_id', 'id');
    }
}
