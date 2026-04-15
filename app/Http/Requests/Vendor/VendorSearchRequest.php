<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class VendorSearchRequest extends FormRequest
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
            'approval_status' => ['nullable', 'string', 'in:pending,approved,suspended'],
            'is_platform' => ['nullable', 'integer', 'in:0,1'],
            'sort_by' => ['nullable', 'string', 'in:id,name,slug,status,approval_status,is_platform,created_at'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function validatedFilters(): array
    {
        return $this->validated();
    }
}
