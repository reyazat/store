<?php

namespace App\Http\Requests\ProductReview;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title'  => ['nullable', 'string', 'max:150'],
            'body'   => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'امتیاز الزامی است',
            'rating.min'      => 'امتیاز باید بین ۱ تا ۵ باشد',
            'rating.max'      => 'امتیاز باید بین ۱ تا ۵ باشد',
            'body.required'   => 'متن نظر الزامی است',
            'body.min'        => 'متن نظر باید حداقل ۱۰ کاراکتر باشد',
        ];
    }
}
