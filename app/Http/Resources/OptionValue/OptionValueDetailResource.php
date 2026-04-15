<?php

namespace App\Http\Resources\OptionValue;

use App\Models\OptionValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
/**
 * @property-read OptionValue $resource
 * @mixin OptionValue
 */
class OptionValueDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'option_id' => $this->option_id,
            'value' => $this->value,
            'sorted' => $this->sorted,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'option' => $this->whenLoaded('option', fn () => $this->option ? [
                'id' => $this->option->id,
                'name' => $this->option->name,
                'slug' => $this->option->slug,
                'type' => $this->option->type,
            ] : null),
        ];
    }
}
