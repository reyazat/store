<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class VendorUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vendorId = $this->route('vendor')?->getKey() ?? $this->route('vendorID');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', "unique:vendors,slug,$vendorId"],
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
