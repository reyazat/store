<?php

namespace App\Http\Requests\Manufacturer;

use Illuminate\Foundation\Http\FormRequest;

class ManufacturerUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $manufacturerId = $this->route('manufacturer')?->getKey() ?? $this->route('manufacturerID');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', "unique:manufacturers,slug,$manufacturerId"],
            'image' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'integer', 'in:0,1'],
        ];
    }

    public function validatedData(): array
    {
        return $this->validated();
    }
}
