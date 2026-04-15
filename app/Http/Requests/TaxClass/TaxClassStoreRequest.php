<?php

namespace App\Http\Requests\TaxClass;

use Illuminate\Foundation\Http\FormRequest;

class TaxClassStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:tax_classes,name'],
            'rate' => ['required', 'numeric', 'min:0'],
            'type' => ['required', 'string', 'in:P,F'],
            'status' => ['nullable', 'integer', 'in:0,1'],
        ];
    }

    public function validatedData(): array
    {
        return $this->validated();
    }
}
