<?php

namespace IamApi\Repositories\Eloquent;

use IamApi\Models\User;
use IamApi\Models\PasswordHistory;
use IamApi\Repositories\Contracts\PasswordHistoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentPasswordHistoryRepository extends BaseRepository implements PasswordHistoryRepositoryInterface
{
    public function __construct(PasswordHistory $model)
    {
        parent::__construct($model);
    }

    public function createOrUpdate(array $data, array $conditions = []): Model
    {
        if (empty($conditions)) {
            return $this->create($data);
        }

        $history = $this->model->query()->where($conditions)->first();
        if ($history) {
            $this->update($history, $data);
            return $history;
        }

        return $this->create(array_merge($conditions, $data));
    }

    public function updateOrCreate(array $conditions, array $data): PasswordHistory
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
        return $this->model->query()->where('user_id', $user->id)
            ->orderBy('changed_at', 'desc')
            ->get();
    }

    public function findRecentByUser(User $user, int $count = 5): Collection
    {
        return $this->model->query()->where('user_id', $user->id)
            ->orderBy('changed_at', 'desc')
            ->limit($count)
            ->get();
    }

    public function createForUser(User $user, string $passwordHash): Model
    {
        return $this->create([
            'user_id' => $user->id,
            'password_hash' => $passwordHash,
            'changed_at' => now()
        ]);
    }

    public function passwordExistsInHistory(User $user, string $passwordHash, int $historyCount = 5): bool
    {
        $histories = $this->findRecentByUser($user, $historyCount);

        foreach ($histories as $history) {
            if (password_verify($passwordHash, $history->password_hash)) {
                return true;
            }
        }

        return false;
    }

    public function cleanupOldHistory(User $user, int $keepCount = 5): int
    {
        $oldEntries = $this->model->query()->where('user_id', $user->id)
            ->orderBy('changed_at', 'desc')
            ->skip($keepCount)
            ->pluck('id');

        if ($oldEntries->isEmpty()) {
            return 0;
        }

        return $this->model->query()->whereIn('id', $oldEntries)->delete();
    }

    public function deleteAllForUser(User $user): int
    {
        return $this->model->query()->where('user_id', $user->id)->delete();
    }

    public function countForUser(User $user): int
    {
        return $this->model->query()->where('user_id', $user->id)->count();
    }

    public function findOldestForUser(User $user): ?PasswordHistory
    {
        return $this->model->query()->where('user_id', $user->id)
            ->orderBy('changed_at')
            ->first();
    }

    public function findNewestForUser(User $user): ?PasswordHistory
    {
        return $this->model->query()->where('user_id', $user->id)
            ->orderBy('changed_at', 'desc')
            ->first();
    }
}
