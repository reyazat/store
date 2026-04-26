<?php

namespace IamApi\Repositories\Eloquent;

use IamApi\Models\JwtToken;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\JwtTokenRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentJwtTokenRepository extends BaseRepository implements JwtTokenRepositoryInterface
{
    public function __construct(JwtToken $model)
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

    public function updateOrCreate(array $conditions, array $data): JwtToken
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
        return false; // JWT tokens don't support soft delete
    }

    public function forceDelete(Model $model): bool
    {
        return $this->delete($model);
    }

    public function findByJti(string $jti): ?JwtToken
    {
        return $this->model->query()->where('jti', $jti)->first();
    }

    public function findByRefreshToken(string $refreshTokenHash): ?JwtToken
    {
        return $this->model->query()->where('refresh_token_hash', $refreshTokenHash)->first();
    }

    public function findActiveTokensForUser(User $user): Collection
    {
        return $this->model->query()->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->get();
    }

    public function findAllTokensForUser(User $user): Collection
    {
        return $this->model->query()->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findExpiredTokens(): Collection
    {
        return $this->model->query()->where('expires_at', '<', now())->get();
    }

    public function revokeByJti(string $jti): bool
    {
        return $this->model->query()->where('jti', $jti)
            ->update(['revoked_at' => now()]) > 0;
    }

    public function revokeAllForUser(User $user): bool
    {
        return $this->model->query()->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]) >= 0;
    }

    public function revokeExpiredTokens(): int
    {
        return $this->model->query()->where('expires_at', '<', now())
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function cleanupOldTokens(int $daysOld = 30): int
    {
        return $this->model->query()->where('created_at', '<', now()->subDays($daysOld))
            ->whereNotNull('revoked_at')
            ->delete();
    }

    public function updateLastUsed(JwtToken $token): bool
    {
        return $this->update($token, ['last_used_at' => now()]);
    }

    public function findByDevice(User $user, string $deviceName): Collection
    {
        return $this->model->query()->where('user_id', $user->id)
            ->where('device_name', $deviceName)
            ->get();
    }

    public function findByIp(string $ip): Collection
    {
        return $this->model->query()->where('ip', $ip)->get();
    }
}
