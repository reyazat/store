<?php

namespace IamApi\Repositories\Eloquent;

use IamApi\Models\Otp;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\OtpRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentOtpRepository extends BaseRepository implements OtpRepositoryInterface
{
    public function __construct(Otp $model)
    {
        parent::__construct($model);
    }

    public function createOrUpdate(array $data, array $conditions = []): Model
    {
        if (empty($conditions)) {
            return $this->create($data);
        }

        $otp = $this->model->query()->where($conditions)->first();
        if ($otp) {
            $this->update($otp, $data);
            return $otp;
        }

        return $this->create(array_merge($conditions, $data));
    }

    public function updateOrCreate(array $conditions, array $data): Otp
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

    public function findActiveOtp(User $user, string $purpose, string $channel = 'email'): ?Otp
    {
        return $this->model->query()->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('channel', $channel)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function findByCodeHash(string $codeHash): ?Otp
    {
        return $this->model->query()->where('code_hash', $codeHash)->first();
    }

    public function createForUser(User $user, string $purpose, string $channel, string $codeHash, int $expiryMinutes = 10): Model
    {
        return $this->create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'channel' => $channel,
            'code_hash' => $codeHash,
            'expires_at' => now()->addMinutes($expiryMinutes),
            'attempts' => 0,
            'max_attempts' => 3,
            'used' => false
        ]);
    }

    public function markAsUsed(Otp $otp): bool
    {
        return $this->update($otp, ['used' => true]);
    }

    public function incrementAttempts(Otp $otp): bool
    {
        return $this->update($otp, ['attempts' => $otp->attempts + 1]);
    }

    public function findExpiredOtps(): Collection
    {
        return $this->model->query()->where('expires_at', '<', now())->get();
    }

    public function cleanupExpiredOtps(): int
    {
        return $this->model->query()->where('expires_at', '<', now())->delete();
    }

    public function findByPurpose(string $purpose): Collection
    {
        return $this->model->query()->where('purpose', $purpose)->get();
    }

    public function findByChannel(string $channel): Collection
    {
        return $this->model->query()->where('channel', $channel)->get();
    }

    public function revokeActiveOtps(User $user, string $purpose): int
    {
        return $this->model->query()->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->update(['used' => true]);
    }

    public function isValidOtp(Otp $otp): bool
    {
        return !$otp->used &&
               !$otp->isExpired() &&
               $otp->attempts < $otp->max_attempts;
    }

    public function findRecentOtps(User $user, string $purpose, int $minutes = 60): Collection
    {
        return $this->model->query()->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('created_at', '>', now()->subMinutes($minutes))
            ->get();
    }
}
