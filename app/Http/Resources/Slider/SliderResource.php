<?php

namespace App\Http\Resources\Slider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SliderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'subtitle'    => $this->subtitle,
            'image'       => $this->image ? Storage::disk('public')->url($this->image) : null,
            'link'        => $this->link,
            'button_text' => $this->button_text,
            'button_link' => $this->button_link,
            'sort_order'  => $this->sort_order,
            'is_active'   => $this->is_active,
            'created_at'  => $this->created_at,
        ];
    }
}
