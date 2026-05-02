<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class AdminLockUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason'           => ['required', 'string', 'max:500'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'دلیل قفل کردن الزامی است.',
            'reason.max'      => 'دلیل قفل کردن نباید بیشتر از ۵۰۰ کاراکتر باشد.',
        ];
    }
}
