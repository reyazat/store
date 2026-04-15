<?php

namespace App\Http\Resources\Vendor;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Vendor $resource
 * @mixin Vendor
 */
class VendorListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'is_platform' => $this->is_platform,
            'commission_rate' => $this->commission_rate,
            'status' => $this->status,
            'approval_status' => $this->approval_status,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
