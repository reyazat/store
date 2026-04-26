<?php

namespace IamApi\Http\Resources\Auth;

use IamApi\Http\Resources\AbstractResource;
use IamApi\Http\Resources\Traits\EscapesOutput;
use IamApi\Http\Resources\User\UserResource;

class AuthResource extends AbstractResource
{
    use EscapesOutput;

    public function toArray($request): array
    {
        $data = [
            'user' => new UserResource($this->resource['user']),
            'tokens' => [
                'access_token' => $this->resource['tokens']['access_token'],
                'refresh_token' => $this->resource['tokens']['refresh_token'],
                'token_type' => $this->resource['tokens']['token_type'] ?? 'Bearer',
                'expires_in' => $this->resource['tokens']['expires_in']
            ]
        ];

        // Escape output to prevent XSS
        return $this->escapeOutput($data);
    }
}
