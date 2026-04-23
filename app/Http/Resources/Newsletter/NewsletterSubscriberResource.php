<?php

namespace App\Http\Resources\Newsletter;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsletterSubscriberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'email'      => $this->email,
            'is_active'  => $this->is_active,
            'source'     => $this->source,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
