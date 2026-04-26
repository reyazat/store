<?php

namespace IamApi\Repositories\Eloquent;

use IamApi\Models\User;
use IamApi\Models\UserRecoveryCode;
use IamApi\Repositories\Contracts\UserRecoveryCodeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentUserRecoveryCodeRepository extends BaseRepository implements UserRecoveryCodeRepositoryInterface
{
    public function __construct(UserRecoveryCode $model)
    {
        parent::__construct($model);
    }

    public function createOrUpdate(array $data, array $conditions = []): Model
    {
        if (empty($conditions)) {
            return $this->create($data);
        }

        $code = $this->model->query()->where($conditions)->first();
        if ($code) {
            $this->update($code, $data);
            return $code;
        }

        return $this->create(array_merge($conditions, $data));
    }

    public function updateOrCreate(array $conditions, array $data): UserRecoveryCode
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

    public function findByUser(User $user): Collection
    {
        return $this->model->query()->where('user_id', $user->id)->get();
    }

    public function findUnusedByUser(User $user): Collection
    {
        return $this->model->query()->where('user_id', $user->id)
            ->whereNull('used_at')
            ->get();
    }

    public function findUsedByUser(User $user): Collection
    {
        return $this->model->query()->where('user_id', $user->id)
            ->whereNotNull('used_at')
            ->get();
    }

    public function createCodesForUser(User $user, array $codeHashes)
    {
        $codes = collect();

        foreach ($codeHashes as $hash) {
            $code = $this->create([
                'user_id' => $user->id,
                'code_hash' => $hash,
                'used_at' => null
            ]);
            $codes->push($code);
        }

        return $codes;
    }

    public function findByCodeHash(string $codeHash): ?UserRecoveryCode
    {
        return $this->model->query()->where('code_hash', $codeHash)->first();
    }

    public function markAsUsed(UserRecoveryCode $recoveryCode): bool
    {
        return $this->update($recoveryCode, ['used_at' => now()]);
    }

    public function deleteAllForUser(User $user): int
    {
        return $this->model->query()->where('user_id', $user->id)->delete();
    }

    public function countUnusedForUser(User $user): int
    {
        return $this->model->query()->where('user_id', $user->id)
            ->whereNull('used_at')
            ->count();
    }

    public function countUsedForUser(User $user): int
    {
        return $this->model->query()->where('user_id', $user->id)
            ->whereNotNull('used_at')
            ->count();
    }

    public function hasUnusedCodes(User $user): bool
    {
        return $this->countUnusedForUser($user) > 0;
    }

    public function regenerateCodesForUser(User $user, array $codeHashes)
    {
        // Delete existing codes
        $this->deleteAllForUser($user);

        // Create new codes
        return $this->createCodesForUser($user, $codeHashes);
    }
}
