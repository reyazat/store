<?php

namespace IamApi\Repositories\Eloquent;

use IamApi\Models\User;
use IamApi\Models\PasswordHistory;
use IamApi\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class EloquentUserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function createOrUpdate(array $data, array $conditions = []): Model
    {
        if (empty($conditions)) {
            return $this->create($data);
        }

        $user = $this->model->query()->where($conditions)->first();
        if ($user) {
            $this->update($user, $data);
            return $user;
        }

        return $this->create(array_merge($conditions, $data));
    }

    public function updateOrCreate(array $conditions, array $data): User
    {
        return $this->model->query()->updateOrCreate($conditions, $data);
    }

    public function bulkInsert(array $data): bool
    {
        return $this->model->query()->insert($data);
    }

    public function bulkUpdate(array $data, string $key = 'id'): bool
    {
        // Implementation for bulk update
        foreach ($data as $item) {
            if (isset($item[$key])) {
                $this->model->query()->where($key, $item[$key])->update($item);
            }
        }
        return true;
    }

    public function softDelete(Model $model): bool
    {
        if (method_exists($model, 'delete')) {
            return $model->delete();
        }
        return false;
    }

    public function restore(Model $model): bool
    {
        if (method_exists($model, 'restore')) {
            return $model->restore();
        }
        return false;
    }

    public function forceDelete(Model $model): bool
    {
        if (method_exists($model, 'forceDelete')) {
            return $model->forceDelete();
        }
        return $this->delete($model);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->query()->where('email', $email)->first();
    }

    public function findByPhone(string $phone): ?User
    {
        // First try exact match
        $user = $this->model->query()->where('phone', $phone)->first();
        if ($user) {
            return $user;
        }

        // Normalize Iranian phone number formats for searching
        // Handle: +989XXXXXXXXX, 989XXXXXXXXX, 09XXXXXXXXX, 9XXXXXXXXX
        $normalizedPhone = $this->normalizeIranianPhone($phone);

        // Try with normalized format
        return $this->model->query()
            ->where(function ($query) use ($phone, $normalizedPhone) {
                $query->where('phone', $phone)
                      ->orWhere('phone', $normalizedPhone)
                      // Also check common variations
                      ->orWhere('phone', preg_replace('/^\+98/', '0', $normalizedPhone))
                      ->orWhere('phone', preg_replace('/^\+98/', '', $normalizedPhone))
                      // Handle 98XXXXXXXXX format (without +)
                      ->orWhere('phone', preg_replace('/^\+/', '', $normalizedPhone));
            })
            ->first();
    }

    /**
     * Normalize Iranian phone number to international format (+98)
     */
    private function normalizeIranianPhone(string $phone): string
    {
        // Remove all non-digit characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // If already starts with +98, it's already normalized
        if (str_starts_with($phone, '+98')) {
            return $phone;
        }

        // Handle 09XXXXXXXXX -> +989XXXXXXXXX
        if (preg_match('/^0?9\d{9}$/', $phone)) {
            return preg_replace('/^0?9/', '+989', $phone);
        }

        return $phone;
    }

    public function findByIdentifier(string $identifier): ?User
    {
        // Try to find by email first
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return $this->findByEmail($identifier);
        }

        // Try to find by phone
        if (preg_match('/^\+?[1-9]\d{1,14}$/', $identifier)) {
            return $this->findByPhone($identifier);
        }

        return null;
    }

    public function findActiveUsers(): Collection
    {
        return $this->model->query()->where('status', 'active')->get();
    }

    public function findLockedUsers(): Collection
    {
        return $this->model->query()->where('locked_until', '>', now())->get();
    }

    public function findUsersWithExpiredPasswords(): Collection
    {
        return $this->model->query()->where('password_expires_at', '<', now())->get();
    }

    public function findUsersMustChangePassword(): Collection
    {
        return $this->model->query()->where('must_change_password', true)->get();
    }

    public function lockUser(User $user, Carbon $until): bool
    {
        return $this->update($user, ['locked_until' => $until]);
    }

    public function unlockUser(User $user): bool
    {
        return $this->update($user, [
            'locked_until' => null,
            'failed_login_attempts' => 0
        ]);
    }

    public function incrementFailedAttempts(User $user): bool
    {
        return $this->update($user, [
            'failed_login_attempts' => $user->failed_login_attempts + 1
        ]);
    }

    public function resetFailedAttempts(User $user): bool
    {
        return $this->update($user, ['failed_login_attempts' => 0]);
    }

    public function updateLastLogin(User $user, string $ip): bool
    {
        return $this->update($user, [
            'last_login_at' => now(),
            'last_login_ip' => $ip
        ]);
    }

    public function forcePasswordChange(User $user): bool
    {
        return $this->update($user, ['must_change_password' => true]);
    }

    public function updatePasswordWithHistory(User $user, string $password): bool
    {
        // Save current password to history
        if ($user->password) {
            PasswordHistory::query()->create([
                'user_id' => $user->id,
                'password_hash' => $user->password,
                'changed_at' => now()
            ]);
        }

        // Clean up old history entries
        $historyCount = config('iam.password.history_count', 5);
        $oldEntries = PasswordHistory::query()->where('user_id', $user->id)
            ->orderBy('changed_at', 'desc')
            ->skip($historyCount)
            ->take(PHP_INT_MAX)  // SQLite requires LIMIT with OFFSET
            ->pluck('id');

        if ($oldEntries->isNotEmpty()) {
            PasswordHistory::query()->whereIn('id', $oldEntries)->delete();
        }

        // Update password
        return $this->update($user, [
            'password' => $password,
            'password_changed_at' => now(),
            'password_expires_at' => now()->addDays(config('iam.password.expiry_days', 90)),
            'must_change_password' => false
        ]);
    }

    public function passwordExistsInHistory(User $user, string $password, int $historyCount = 5): bool
    {
        $histories = PasswordHistory::query()->where('user_id', $user->id)
            ->orderBy('changed_at', 'desc')
            ->limit($historyCount)
            ->pluck('password_hash');

        foreach ($histories as $hash) {
            if (Hash::check($password, $hash)) {
                return true;
            }
        }

        return false;
    }

    public function findByOrganization(int $organizationId): Collection
    {
        // Single-organization model: users have organization_id directly
        return $this->model->query()->where('organization_id', $organizationId)->get();
    }

    public function findByOrganizationRole(int $organizationId, string $role): Collection
    {
        // Single-organization model: users have organization_id directly
        // Role is managed via Spatie Permission package
        return $this->model->query()
            ->where('organization_id', $organizationId)
            ->whereHas('roles', function ($query) use ($organizationId, $role) {
                $query->where('roles.name', $role)
                      ->where('roles.organization_id', $organizationId);
            })->get();
    }

    public function findSuperAdmins(): Collection
    {
        return $this->model->query()->where('is_super_admin', true)->get();
    }

    public function isSuperAdmin(User $user): bool
    {
        return $user->is_super_admin === true;
    }

    public function findAllOwners(): Collection
    {
        // Single-organization model: find users with 'owner' role via Spatie Permission
        return $this->model->query()
            ->whereNotNull('organization_id')
            ->whereHas('roles', function ($query) {
                $query->where('roles.name', 'owner');
            })->get();
    }
}
