<?php

namespace IamApi\Http\Resources\Auth;

use IamApi\Http\Resources\AbstractResource;
use IamApi\Http\Resources\Traits\EscapesOutput;

class TokenResource extends AbstractResource
{
    use EscapesOutput;

    public function toArray($request): array
    {
        $data = [
            'access_token' => $this->resource['access_token'],
            'refresh_token' => $this->resource['refresh_token'],
            'token_type' => $this->resource['token_type'] ?? 'Bearer',
            'expires_in' => $this->resource['expires_in']
        ];

        // Escape output to prevent XSS
        return $this->escapeOutput($data);
    }
}
