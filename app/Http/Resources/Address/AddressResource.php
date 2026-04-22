<?php

namespace App\Http\Resources\Address;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'first_name'  => $this->first_name,
            'last_name'   => $this->last_name,
            'phone'       => $this->phone,
            'email'       => $this->email,
            'country'     => $this->country,
            'province'    => $this->province,
            'city'        => $this->city,
            'address'     => $this->address,
            'postal_code' => $this->postal_code,
            'notes'       => $this->notes,
            'is_default'  => $this->is_default,
        ];
    }
}
