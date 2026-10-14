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
            'image'         => $this->resolveImageUrl($this->image),
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

    private function resolveImageUrl(?string $path): ?string
    {
        if (!$path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
