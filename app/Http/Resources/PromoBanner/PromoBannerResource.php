<?php

namespace App\Http\Resources\PromoBanner;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PromoBannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'subtitle'      => $this->subtitle,
            'icon'          => $this->icon,
            'image'         => $this->image ? Storage::disk('public')->url($this->image) : null,
            'link'          => $this->link,
            'cta_text'      => $this->cta_text,
            'gradient_from' => $this->gradient_from,
            'gradient_to'   => $this->gradient_to,
            'placement'     => $this->placement,
            'sort_order'    => $this->sort_order,
            'is_active'     => $this->is_active,
            'starts_at'     => $this->starts_at?->toDateTimeString(),
            'ends_at'       => $this->ends_at?->toDateTimeString(),
        ];
    }
}
