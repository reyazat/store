<?php

namespace App\Http\Requests\Coupon;

use Illuminate\Foundation\Http\FormRequest;

class CouponUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'           => ['sometimes', 'string', 'unique:coupons,code,' . $this->route('coupon')?->id, 'max:255'],
            'name'           => ['sometimes', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'type'           => ['sometimes', 'in:fixed,percentage'],
            'value'          => ['sometimes', 'numeric', 'min:0'],
            'min_purchase'   => ['nullable', 'numeric', 'min:0'],
            'max_discount'   => ['nullable', 'numeric', 'min:0'],
            'usage_limit'    => ['nullable', 'integer', 'min:1'],
            'usage_per_user' => ['nullable', 'integer', 'min:1'],
            'starts_at'      => ['nullable', 'date'],
            'expires_at'     => ['nullable', 'date', 'after:starts_at'],
            'status'         => ['nullable', 'boolean'],
        ];
    }
}
