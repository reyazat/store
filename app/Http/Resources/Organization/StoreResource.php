<?php

namespace App\Http\Resources\Organization;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\PermissionRegistrar;

class StoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Set permissionsTeamId to the organization scope before checking roles
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->id);

        $owner = $this->users->first(function ($u) {
            $u->unsetRelation('roles');

            return $u->hasRole('owner');
        });

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'logo' => $this->logo,
            'status' => $this->status,
            'owner' => $owner ? [
                'id' => $owner->id,
                'name' => $owner->name,
                'email' => $owner->email,
            ] : null,
            'members_count' => $this->users_count ?? $this->users->count(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
