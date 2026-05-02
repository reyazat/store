<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'uuid'                 => $this->uuid,
            'name'                 => $this->name,
            'email'                => $this->email,
            'phone'                => $this->phone,
            'status'               => $this->status,
            'is_super_admin'       => $this->is_super_admin,
            'email_verified_at'    => $this->email_verified_at?->toISOString(),
            'phone_verified_at'    => $this->phone_verified_at?->toISOString(),
            'last_login_at'        => $this->last_login_at?->toISOString(),
            'last_login_ip'        => $this->last_login_ip,
            'password_expires_at'  => $this->password_expires_at?->toISOString(),
            'must_change_password' => $this->must_change_password,
            'mfa_enabled'          => $this->mfa_enabled,
            'is_locked'            => $this->isLocked(),
            'locked_until'         => $this->locked_until?->toISOString(),
            'created_at'           => $this->created_at->toISOString(),
            'updated_at'           => $this->updated_at->toISOString(),
            'organization'         => $this->whenLoaded('organization', fn () => $this->organization ? [
                'id'   => $this->organization->id,
                'uuid' => $this->organization->uuid,
                'name' => $this->organization->name,
            ] : null),
        ];
    }
}
