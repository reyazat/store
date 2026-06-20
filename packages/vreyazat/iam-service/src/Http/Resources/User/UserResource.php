<?php

namespace IamApi\Http\Resources\User;

use IamApi\Http\Resources\AbstractResource;
use IamApi\Http\Resources\Traits\EscapesOutput;
use IamApi\Models\User;

/**
 * @property-read User $resource
 * @mixin User
 */
class UserResource extends AbstractResource
{
    use EscapesOutput;

    public function toArray($request): array
    {
        $data = [
            'uuid'                => $this->uuid,
            'name'                => $this->name,
            'email'               => $this->email,
            'phone'               => $this->phone,
            'status'              => $this->status,
            'is_super_admin'      => $this->is_super_admin,
            'organization_id'     => $this->organization_id,
            'email_verified_at'   => $this->email_verified_at?->toISOString(),
            'phone_verified_at'   => $this->phone_verified_at?->toISOString(),
            'last_login_at'       => $this->last_login_at?->toISOString(),
            'password_expires_at' => $this->password_expires_at?->toISOString(),
            'must_change_password' => $this->must_change_password,
            'mfa_enabled'         => $this->mfa_enabled,
            'is_locked'           => $this->isLocked(),
            'locked_until'        => $this->locked_until?->toISOString(),
            'created_at'          => $this->created_at->toISOString(),
            'updated_at'          => $this->updated_at->toISOString(),
        ];

        // Include organization if loaded (single organization per user).
        // Buyers have organization_id=null so $this->organization will be null — that is intentional.
        if ($this->relationLoaded('organization')) {
            $data['organization'] = $this->organization ? [
                'id'     => $this->organization->id,
                'uuid'   => $this->organization->uuid,
                'name'   => $this->organization->name,
                'status' => $this->organization->status,
            ] : null;
        }

        // Escape output to prevent XSS
        return $this->escapeOutput($data);
    }
}
