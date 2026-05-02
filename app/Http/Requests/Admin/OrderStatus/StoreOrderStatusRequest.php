<?php

namespace App\Http\Requests\Admin\OrderStatus;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key'                  => ['required', 'string', 'max:60', 'unique:order_statuses,key', 'regex:/^[a-z_]+$/'],
            'label'                => ['required', 'string', 'max:100'],
            'color'                => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_cancellation'      => ['boolean'],
            'is_completion'        => ['boolean'],
            'sort_order'           => ['integer', 'min:0'],
            'notify_customer'      => ['boolean'],
            'notification_message' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.unique'       => 'این کلید وضعیت قبلاً استفاده شده است',
            'key.regex'        => 'کلید فقط می‌تواند شامل حروف کوچک انگلیسی و زیرخط باشد',
            'color.regex'      => 'رنگ باید در فرمت هگز (#rrggbb) وارد شود',
        ];
    }
}
