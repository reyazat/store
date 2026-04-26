<?php

namespace IamApi\Repositories\Eloquent;

use IamApi\Models\User;
use IamApi\Models\UserTotpSecret;
use IamApi\Repositories\Contracts\UserTotpSecretRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class EloquentUserTotpSecretRepository extends BaseRepository implements UserTotpSecretRepositoryInterface
{
    public function __construct(UserTotpSecret $model)
    {
        parent::__construct($model);
    }

    public function createOrUpdate(array $data, array $conditions = []): Model
    {
        if (empty($conditions)) {
            return $this->create($data);
        }

        $secret = $this->model->query()->where($conditions)->first();
        if ($secret) {
            $this->update($secret, $data);
            return $secret;
        }

        return $this->create(array_merge($conditions, $data));
    }

    public function updateOrCreate(array $conditions, array $data): UserTotpSecret
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

    public function findByUser(User $user): ?UserTotpSecret
    {
        return $this->model->query()->where('user_id', $user->id)->first();
    }

    public function createForUser(User $user, string $secret): Model
    {
        return $this->create([
            'user_id' => $user->id,
            'secret' => $secret,
            'enabled' => false
        ]);
    }

    public function enableForUser(User $user): bool
    {
        $totpSecret = $this->findByUser($user);
        if (!$totpSecret) {
            return false;
        }

        return $this->update($totpSecret, ['enabled' => true]);
    }

    public function disableForUser(User $user): bool
    {
        $totpSecret = $this->findByUser($user);
        if (!$totpSecret) {
            return false;
        }

        return $this->update($totpSecret, ['enabled' => false]);
    }

    public function isEnabledForUser(User $user): bool
    {
        $totpSecret = $this->findByUser($user);
        return $totpSecret && $totpSecret->enabled;
    }

    public function updateSecretForUser(User $user, string $secret): bool
    {
        $totpSecret = $this->findByUser($user);
        if (!$totpSecret) {
            return false;
        }

        return $this->update($totpSecret, ['secret' => $secret]);
    }

    public function deleteForUser(User $user): bool
    {
        $totpSecret = $this->findByUser($user);
        if (!$totpSecret) {
            return false;
        }

        return $this->delete($totpSecret);
    }

    public function findUsersWithTotpEnabled()
    {
        return $this->model->query()->where('enabled', true)
            ->with('user')
            ->get()
            ->pluck('user');
    }

    public function findUsersWithTotpDisabled()
    {
        return $this->model->query()->where('enabled', false)
            ->with('user')
            ->get()
            ->pluck('user');
    }
}
