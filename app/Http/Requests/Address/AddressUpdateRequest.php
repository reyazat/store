<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class AddressUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['nullable', 'string', 'max:255'],
            'first_name'  => ['sometimes', 'string', 'max:255'],
            'last_name'   => ['sometimes', 'string', 'max:255'],
            'phone'       => ['sometimes', 'string', 'max:20'],
            'email'       => ['nullable', 'email'],
            'country'     => ['nullable', 'string', 'max:100'],
            'province'    => ['sometimes', 'string', 'max:100'],
            'city'        => ['sometimes', 'string', 'max:100'],
            'address'     => ['sometimes', 'string'],
            'postal_code' => ['sometimes', 'string', 'max:20'],
            'notes'       => ['nullable', 'string'],
            'is_default'  => ['nullable', 'boolean'],
        ];
    }
}
