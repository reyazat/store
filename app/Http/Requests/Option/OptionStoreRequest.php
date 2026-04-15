<?php

namespace App\Http\Requests\Option;

use Illuminate\Foundation\Http\FormRequest;

class OptionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:options,name'],
            'slug' => ['required', 'string', 'max:255', 'unique:options,slug'],
            'type' => ['required', 'string', 'in:radio,checkbox,select'],
            'sorted' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function validatedData(): array
    {
        return $this->validated();
    }
}
