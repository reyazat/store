<?php

namespace App\Http\Resources\Organization;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $owner = $this->users->first(fn($u) => $u->hasRole('owner'));

        return [
            'id'          => $this->id,
            'uuid'        => $this->uuid,
            'name'        => $this->name,
            'description' => $this->description,
            'logo'        => $this->logo,
            'status'      => $this->status,
            'owner'       => $owner ? [
                'id'    => $owner->id,
                'name'  => $owner->name,
                'email' => $owner->email,
            ] : null,
            'members_count' => $this->users_count ?? $this->users->count(),
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
