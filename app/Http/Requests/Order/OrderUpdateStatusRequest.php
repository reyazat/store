<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class OrderUpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'exists:order_statuses,key'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'وضعیت سفارش الزامی است',
            'status.exists'   => 'وضعیت انتخاب‌شده معتبر نیست',
        ];
    }
}
