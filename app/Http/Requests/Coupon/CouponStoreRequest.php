<?php

namespace App\Http\Requests\Coupon;

use Illuminate\Foundation\Http\FormRequest;

class CouponStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'           => ['required', 'string', 'unique:coupons,code', 'max:255'],
            'name'           => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string'],
            'type'           => ['required', 'in:fixed,percentage'],
            'value'          => ['required', 'numeric', 'min:0'],
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
