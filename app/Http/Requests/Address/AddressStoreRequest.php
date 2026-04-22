<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class AddressStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['nullable', 'string', 'max:255'],
            'first_name'  => ['required', 'string', 'max:255'],
            'last_name'   => ['required', 'string', 'max:255'],
            'phone'       => ['required', 'string', 'max:20'],
            'email'       => ['nullable', 'email'],
            'country'     => ['nullable', 'string', 'max:100'],
            'province'    => ['required', 'string', 'max:100'],
            'city'        => ['required', 'string', 'max:100'],
            'address'     => ['required', 'string'],
            'postal_code' => ['required', 'string', 'max:20'],
            'notes'       => ['nullable', 'string'],
            'is_default'  => ['nullable', 'boolean'],
        ];
    }
}
