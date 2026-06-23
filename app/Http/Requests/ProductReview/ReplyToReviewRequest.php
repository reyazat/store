<?php

namespace App\Http\Requests\ProductReview;

use Illuminate\Foundation\Http\FormRequest;

class ReplyToReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'متن پاسخ الزامی است',
            'body.min'      => 'پاسخ باید حداقل ۳ کاراکتر باشد',
            'body.max'      => 'پاسخ نباید بیشتر از ۱۰۰۰ کاراکتر باشد',
        ];
    }
}
