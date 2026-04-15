<?php

namespace App\Http\Resources\Option;

use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
/**
 * @property-read Option $resource
 * @mixin Option
 */
class OptionDetailResource extends JsonResource
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
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'values' => $this->whenLoaded('values', fn () => $this->values->map(fn ($value) => [
                'id' => $value->id,
                'value' => $value->value,
                'sorted' => $value->sorted,
            ])),
        ];
    }
}
