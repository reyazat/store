<?php

namespace App\Http\Resources\NavLink;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NavLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'label'      => $this->label,
            'url'        => $this->url,
            'group'      => $this->group,
            'target'     => $this->target,
            'sort_order' => $this->sort_order,
            'is_active'  => $this->is_active,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
