<?php

namespace App\Http\Resources\Option;

use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
/**
 * @property-read Option $resource
 * @mixin Option
 */
class OptionListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'sorted' => $this->sorted,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
