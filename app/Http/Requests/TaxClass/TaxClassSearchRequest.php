<?php

namespace App\Http\Requests\TaxClass;

use Illuminate\Foundation\Http\FormRequest;

class TaxClassSearchRequest extends FormRequest
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
            'type' => ['nullable', 'string', 'in:P,F'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function validatedFilters(): array
    {
        return $this->validated();
    }
}
