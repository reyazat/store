<?php

namespace App\Http\Requests\CustomerGroup;

use Illuminate\Foundation\Http\FormRequest;

class CustomerGroupUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $groupId = $this->route('customerGroup')?->getKey() ?? $this->route('customerGroupID');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', "unique:customer_groups,name,$groupId"],
            'slug' => ['sometimes', 'required', 'string', 'max:255', "unique:customer_groups,slug,$groupId"],
            'description' => ['nullable', 'string'],
            'approve_customer' => ['nullable', 'integer', 'in:0,1'],
            'status' => ['nullable', 'integer', 'in:0,1'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    public function validatedData(): array
    {
        return $this->validated();
    }
}
