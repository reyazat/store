<?php

namespace IamApi\Http\Resources\User;

use IamApi\Http\Resources\AbstractResource;
use IamApi\Http\Resources\Traits\EscapesOutput;
use IamApi\Models\User;

/**
 * @property-read User $resource
 * @mixin User
 */
class UserProfileResource extends AbstractResource
{
    use EscapesOutput;

    public function toArray($request): array
    {
        $data = [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'phone_verified_at' => $this->phone_verified_at?->toISOString(),
        ];

        // Escape output to prevent XSS
        return $this->escapeOutput($data);
    }
}
