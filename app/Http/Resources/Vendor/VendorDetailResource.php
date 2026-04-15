<?php

namespace App\Http\Resources\Vendor;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
/**
 * @property-read Vendor $resource
 * @mixin Vendor
 */
class VendorDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_platform' => $this->is_platform,
            'commission_rate' => $this->commission_rate,
            'status' => $this->status,
            'approval_status' => $this->approval_status,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'users_count' => $this->when(isset($this->users_count), fn () => $this->users_count),
            'listings_count' => $this->when(isset($this->listings_count), fn () => $this->listings_count),
            'user_ids' => $this->whenLoaded('users', fn () => $this->users->pluck('id')),
            'policy' => $this->whenLoaded('policy', fn () => $this->policy ? [
                'id' => $this->policy->id,
                'tax_class_id' => $this->policy->tax_class_id,
                'commission_rate' => $this->policy->commission_rate,
                'min_payout_amount' => $this->policy->min_payout_amount,
                'status' => $this->policy->status,
            ] : null),
        ];
    }
}
