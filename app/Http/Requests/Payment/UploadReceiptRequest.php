<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class UploadReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receipt'        => ['required', 'file', 'image', 'max:4096'],
            'card_last_four' => ['nullable', 'string', 'max:4'],
        ];
    }

    public function messages(): array
    {
        return [
            'receipt.required' => 'تصویر رسید پرداخت الزامی است',
            'receipt.image'    => 'فایل آپلود شده باید تصویر باشد',
            'receipt.max'      => 'حجم تصویر نباید بیشتر از ۴ مگابایت باشد',
        ];
    }
}
