<?php

namespace App\Http\Requests\TaxClass;

use Illuminate\Foundation\Http\FormRequest;

class TaxClassUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $taxClassId = $this->route('taxClass')?->getKey() ?? $this->route('taxClassID');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', "unique:tax_classes,name,$taxClassId"],
            'rate' => ['sometimes', 'required', 'numeric', 'min:0'],
            'type' => ['sometimes', 'required', 'string', 'in:P,F'],
            'status' => ['nullable', 'integer', 'in:0,1'],
        ];
    }

    public function validatedData(): array
    {
        return $this->validated();
    }
}
