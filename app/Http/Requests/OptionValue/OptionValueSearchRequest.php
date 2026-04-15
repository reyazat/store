<?php

namespace App\Http\Requests\OptionValue;

use Illuminate\Foundation\Http\FormRequest;

class OptionValueSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'option_id' => ['nullable', 'integer', 'exists:options,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function validatedFilters(): array
    {
        return $this->validated();
    }
}
