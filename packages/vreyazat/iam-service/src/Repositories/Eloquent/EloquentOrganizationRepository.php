<?php

namespace IamApi\Repositories\Eloquent;

use IamApi\Models\Organization;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\OrganizationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class EloquentOrganizationRepository extends BaseRepository implements OrganizationRepositoryInterface
{
    public function __construct(Organization $model)
    {
        parent::__construct($model);
    }

    public function createOrUpdate(array $data, array $conditions = []): Model
    {
        if (empty($conditions)) {
            return $this->create($data);
        }

        $organization = $this->model->query()->where($conditions)->first();
        if ($organization) {
            $this->update($organization, $data);

            return $organization;
        }

        return $this->create(array_merge($conditions, $data));
    }

    public function updateOrCreate(array $conditions, array $data): Organization
    {
        return $this->model->query()->updateOrCreate($conditions, $data);
    }

    public function bulkInsert(array $data): bool
    {
        return $this->model->query()->insert($data);
    }

    public function bulkUpdate(array $data, string $key = 'id'): bool
    {
        foreach ($data as $item) {
            if (isset($item[$key])) {
                $this->model->query()->where($key, $item[$key])->update($item);
            }
        }

        return true;
    }

    public function softDelete(Model $model): bool
    {
        return $this->delete($model);
    }

    public function restore(Model $model): bool
    {
        return false;
    }

    public function forceDelete(Model $model): bool
    {
        return $this->delete($model);
    }

    public function findByName(string $name): ?Organization
    {
        return $this->model->query()->where('name', $name)->first();
    }

    public function findByUser(User $user): Collection
    {
        // هر کاربر فقط یک سازمان دارد
        return $user->organization ? collect([$user->organization]) : collect();
    }

    public function findMembers(Organization $organization): Collection
    {
        return $organization->users;
    }

    public function findMembersByRole(Organization $organization, string $role): Collection
    {
        // نوت: role در Spatie Permission است، نه در organization_user
        return $organization->users()
            ->whereHas('roles', function ($query) use ($role, $organization) {
                $query->where('roles.name', $role)
                    ->where('roles.organization_id', $organization->id);
            })
            ->get();
    }

    public function addMember(Organization $organization, User $user, string $role = 'member'): bool
    {
        if ($this->isMember($organization, $user)) {
            return true; // کاربر قبلاً عضو است
        }

        return DB::transaction(function () use ($organization, $user, $role) {
            // تنظیم team context برای Spatie Permission
            app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

            // بررسی وجود role در دیتابیس
            $roleModel = Role::where('name', $role)
                ->where('organization_id', $organization->id)
                ->first();

            // اگر role وجود ندارد، آن را ایجاد کن
            if (! $roleModel) {
                $roleModel = Role::create([
                    'name' => $role,
                    'guard_name' => 'api',
                    'organization_id' => $organization->id,
                ]);
            }

            // تنظیم organization_id برای کاربر
            $user->update(['organization_id' => $organization->id]);

            // حذف role های قبلی مستقیماً از pivot table
            // Delete by model_id only to handle any model_type variant (IamApi\Models\User or App\Models\User)
            DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->delete();

            // Re-fetch user using the configured auth model class to ensure the correct
            // model_type (e.g. 'App\Models\User') is written to model_has_roles by Spatie.
            // If the passed $user is IamApi\Models\User and the app extends it as App\Models\User,
            // calling assignRole on the base class would store the wrong model_type.
            $authModelClass = config('auth.providers.users.model', get_class($user));
            $userForRole = $authModelClass::find($user->id);

            // Clear cached roles so assignRole sees fresh state
            $userForRole->unsetRelation('roles');
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Assign role جدید — model_type will be 'App\Models\User' (canonical)
            $userForRole->assignRole($roleModel);

            return true;
        });
    }

    public function removeMember(Organization $organization, User $user): bool
    {
        // حذف organization_id از کاربر
        if ($user->organization_id !== $organization->id) {
            return false;
        }

        return DB::transaction(function () use ($user) {
            // حذف role های این سازمان مستقیماً از pivot table
            // (avoid guard mismatch errors)
            DB::table('model_has_roles')
                ->where('model_type', get_class($user))
                ->where('model_id', $user->id)
                ->delete();

            // سپس organization_id را null کن
            $user->update(['organization_id' => null]);

            // Clear cached roles
            $user->unsetRelation('roles');
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return true;
        });
    }

    public function updateMemberRole(Organization $organization, User $user, string $role): bool
    {
        // بررسی عضویت کاربر
        if (! $this->isMember($organization, $user)) {
            return false;
        }

        return DB::transaction(function () use ($organization, $user, $role) {
            // تنظیم team context
            app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

            // بررسی وجود role در دیتابیس
            $roleModel = Role::where('name', $role)
                ->where('organization_id', $organization->id)
                ->first();

            // اگر role وجود ندارد، آن را ایجاد کن
            if (! $roleModel) {
                $roleModel = Role::create([
                    'name' => $role,
                    'guard_name' => 'api',
                    'organization_id' => $organization->id,
                ]);
            }

            // حذف role های قبلی مستقیماً از pivot table
            // (avoid guard mismatch errors)
            DB::table('model_has_roles')
                ->where('model_type', get_class($user))
                ->where('model_id', $user->id)
                ->delete();

            // Clear cached roles so assignRole sees fresh state
            $user->unsetRelation('roles');
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Assign role جدید
            $user->assignRole($roleModel);

            return true;
        });
    }

    public function isMember(Organization $organization, User $user): bool
    {
        return $user->organization_id === $organization->id;
    }

    public function hasRole(Organization $organization, User $user, string $role): bool
    {
        // بررسی اینکه کاربر عضو سازمان است و role دارد
        if (! $this->isMember($organization, $user)) {
            return false;
        }

        return $user->hasRole($role, $organization->id);
    }

    public function getUserRole(Organization $organization, User $user): ?string
    {
        // نوت: role در Spatie Permission است
        // اگر کاربر عضو نیست، null برگردان
        if (! $this->isMember($organization, $user)) {
            return null;
        }

        // تنظیم team context
        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        // اولین role را برگردان
        // Note: setPermissionsTeamId already filters by organization_id
        $role = $user->roles()->first();

        return $role?->name;
    }

    public function findByDomain(string $domain): Collection
    {
        return $this->model->query()->whereHas('domains', function ($query) use ($domain) {
            $query->where('domain', $domain);
        })->get();
    }

    public function countMembers(Organization $organization): int
    {
        return $organization->users()->count();
    }

    public function findOwners(Organization $organization): Collection
    {
        return $this->findMembersByRole($organization, 'owner');
    }

    public function findAdmins(Organization $organization): Collection
    {
        return $this->findMembersByRole($organization, 'admin');
    }

    public function findUsers(Organization $organization): Collection
    {
        return $this->findMembersByRole($organization, 'user');
    }

    public function findRegularMembers(Organization $organization): Collection
    {
        return $this->findMembersByRole($organization, 'member');
    }

    public function hasOwner(Organization $organization): bool
    {
        return $this->findMembersByRole($organization, 'owner')->count() > 0;
    }

    public function countMembersByRole(Organization $organization, string $role): int
    {
        return $this->findMembersByRole($organization, $role)->count();
    }
}
