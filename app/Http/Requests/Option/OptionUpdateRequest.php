<?php

namespace App\Http\Requests\Option;

use Illuminate\Foundation\Http\FormRequest;

class OptionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $optionId = $this->route('option')?->getKey() ?? $this->route('optionID');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', "unique:options,name,$optionId"],
            'slug' => ['sometimes', 'required', 'string', 'max:255', "unique:options,slug,$optionId"],
            'type' => ['sometimes', 'required', 'string', 'in:radio,checkbox,select'],
            'sorted' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function validatedData(): array
    {
        return $this->validated();
    }
}
