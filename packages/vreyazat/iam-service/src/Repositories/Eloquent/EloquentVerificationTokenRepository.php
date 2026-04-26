<?php

namespace IamApi\Repositories\Eloquent;

use IamApi\Models\User;
use IamApi\Models\VerificationToken;
use IamApi\Repositories\Contracts\VerificationTokenRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EloquentVerificationTokenRepository extends BaseRepository implements VerificationTokenRepositoryInterface
{
    public function __construct(VerificationToken $model)
    {
        parent::__construct($model);
    }

    public function createOrUpdate(array $data, array $conditions = []): Model
    {
        if (empty($conditions)) {
            return $this->create($data);
        }

        $token = $this->model->query()->where($conditions)->first();
        if ($token) {
            $this->update($token, $data);
            return $token;
        }

        return $this->create(array_merge($conditions, $data));
    }

    public function updateOrCreate(array $conditions, array $data): VerificationToken
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

    public function findByToken(string $token): ?VerificationToken
    {
        return $this->model->query()->where('token', $token)->first();
    }

    public function findByUser(User $user): Collection
    {
        return $this->model->query()->where('user_id', $user->id)->get();
    }

    public function findByType(string $type): Collection
    {
        return $this->model->query()->where('type', $type)->get();
    }

    public function findActiveByUserAndType(User $user, string $type): Collection
    {
        return $this->model->query()->where('user_id', $user->id)
            ->where('type', $type)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->get();
    }

    public function findExpiredTokens(): Collection
    {
        return $this->model->query()->where('expires_at', '<', now())->get();
    }

    public function createForUser(User $user, string $type, int $expiryMinutes = 60): Model
    {
        return $this->create([
            'user_id' => $user->id,
            'type' => $type,
            'token' => Str::random(64),
            'used' => false,
            'expires_at' => now()->addMinutes($expiryMinutes)
        ]);
    }

    public function markAsUsed(VerificationToken $token): bool
    {
        return $this->update($token, ['used' => true]);
    }

    public function isExpired(VerificationToken $token): bool
    {
        return now()->gt($token->expires_at);
    }

    public function isUsed(VerificationToken $token): bool
    {
        return $token->used;
    }

    public function isValid(VerificationToken $token): bool
    {
        return !$this->isUsed($token) && !$this->isExpired($token);
    }

    public function revokeTokensForUserAndType(User $user, string $type): int
    {
        return $this->model->query()->where('user_id', $user->id)
            ->where('type', $type)
            ->where('used', false)
            ->update(['used' => true]);
    }

    public function cleanupExpiredTokens(): int
    {
        return $this->model->query()->where('expires_at', '<', now())->delete();
    }

    public function findRecentTokens(User $user, string $type, int $minutes = 60): Collection
    {
        return $this->model->query()->where('user_id', $user->id)
            ->where('type', $type)
            ->where('created_at', '>', now()->subMinutes($minutes))
            ->get();
    }
}
