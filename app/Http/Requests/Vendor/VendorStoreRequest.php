<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class VendorStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:vendors,slug'],
            'description' => ['nullable', 'string'],
            'is_platform' => ['nullable', 'integer', 'in:0,1'],
            'commission_rate' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'integer', 'in:0,1'],
            'approval_status' => ['nullable', 'string', 'in:pending,approved,suspended'],
        ];
    }

    public function validatedData(): array
    {
        return $this->validated();
    }
}
