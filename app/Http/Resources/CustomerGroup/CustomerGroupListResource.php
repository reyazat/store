<?php

namespace App\Http\Resources\CustomerGroup;

use App\Models\CustomerGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
/**
 * @property-read CustomerGroup $resource
 * @mixin CustomerGroup
 */
class CustomerGroupListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'approve_customer' => $this->approve_customer,
            'status' => $this->status,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
