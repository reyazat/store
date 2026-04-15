<?php

namespace App\Http\Requests\OptionValue;

use Illuminate\Foundation\Http\FormRequest;

class OptionValueUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'option_id' => ['sometimes', 'required', 'integer', 'exists:options,id'],
            'value' => ['sometimes', 'required', 'string', 'max:255'],
            'sorted' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function validatedData(): array
    {
        return $this->validated();
    }
}
