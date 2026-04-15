<?php

namespace App\Http\Requests\CustomerGroup;

use Illuminate\Foundation\Http\FormRequest;

class CustomerGroupSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'integer', 'in:0,1'],
            'approve_customer' => ['nullable', 'integer', 'in:0,1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function validatedFilters(): array
    {
        return $this->validated();
    }
}
