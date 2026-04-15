<?php

namespace App\Http\Requests\CustomerGroup;

use Illuminate\Foundation\Http\FormRequest;

class CustomerGroupStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:customer_groups,name'],
            'slug' => ['required', 'string', 'max:255', 'unique:customer_groups,slug'],
            'description' => ['nullable', 'string'],
            'approve_customer' => ['nullable', 'integer', 'in:0,1'],
            'status' => ['nullable', 'integer', 'in:0,1'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    public function validatedData(): array
    {
        return $this->validated();
    }
}
