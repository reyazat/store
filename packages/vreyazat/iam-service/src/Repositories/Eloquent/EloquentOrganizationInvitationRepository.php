<?php

namespace IamApi\Repositories\Eloquent;

use IamApi\Models\Organization;
use IamApi\Models\OrganizationInvitation;
use IamApi\Models\User;
use IamApi\Repositories\Contracts\OrganizationInvitationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EloquentOrganizationInvitationRepository extends BaseRepository implements OrganizationInvitationRepositoryInterface
{
    public function __construct(OrganizationInvitation $model)
    {
        parent::__construct($model);
    }

    public function createOrUpdate(array $data, array $conditions = []): Model
    {
        if (empty($conditions)) {
            return $this->create($data);
        }

        $invitation = $this->model->query()->where($conditions)->first();
        if ($invitation) {
            $this->update($invitation, $data);
            return $invitation;
        }

        return $this->create(array_merge($conditions, $data));
    }

    public function updateOrCreate(array $conditions, array $data): OrganizationInvitation
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

    public function findByToken(string $token): ?OrganizationInvitation
    {
        return $this->model->query()->where('token', $token)->first();
    }

    public function findByEmailAndOrganization(string $email, Organization $organization): ?OrganizationInvitation
    {
        return $this->model->query()->where('email', $email)
            ->where('organization_id', $organization->id)
            ->first();
    }

    public function findByOrganization(Organization $organization): Collection
    {
        return $this->model->query()->where('organization_id', $organization->id)->get();
    }

    public function findByEmail(string $email): Collection
    {
        return $this->model->query()->where('email', $email)->get();
    }

    public function findPendingInvitations(): Collection
    {
        return $this->model->query()->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->get();
    }

    public function findExpiredInvitations(): Collection
    {
        return $this->model->query()->where('expires_at', '<', now())
            ->where('status', 'pending')
            ->get();
    }

    public function createInvitation(Organization $organization, string $email, string $role, User $invitedBy): OrganizationInvitation
    {
        return $this->model->query()->create([
            'organization_id' => $organization->id,
            'email' => $email,
            'role' => $role,
            'token' => Str::random(64),
            'invited_by' => $invitedBy->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(7)
        ]);
    }

    public function acceptInvitation(OrganizationInvitation $invitation, User $user): bool
    {
        // Add user to organization by setting organization_id
        $organization = $invitation->organization;
        $user->update(['organization_id' => $organization->id]);

        // Update invitation status
        return $this->update($invitation, [
            'status' => 'accepted',
            'accepted_by' => $user->id,
            'accepted_at' => now()
        ]);
    }

    public function rejectInvitation(OrganizationInvitation $invitation): bool
    {
        return $this->update($invitation, [
            'status' => 'rejected',
            'rejected_at' => now()
        ]);
    }

    public function cancelInvitation(OrganizationInvitation $invitation): bool
    {
        return $this->update($invitation, [
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);
    }

    public function isExpired(OrganizationInvitation $invitation): bool
    {
        return now()->gt($invitation->expires_at);
    }

    public function isPending(OrganizationInvitation $invitation): bool
    {
        return $invitation->status === 'pending' && !$this->isExpired($invitation);
    }

    public function cleanupExpiredInvitations(): int
    {
        return $this->model->query()->where('expires_at', '<', now())
            ->where('status', 'pending')
            ->update(['status' => 'expired']);
    }

    public function resendInvitation(OrganizationInvitation $invitation): bool
    {
        return $this->update($invitation, [
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
            'status' => 'pending'
        ]);
    }
}
