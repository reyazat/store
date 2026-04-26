<?php

namespace IamApi\Http\Requests\Mfa;

use IamApi\Rules\NoHtml;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyRecoveryCodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'recovery_code' => [
                'required',
                'string',
                'size:10',
                'regex:/^[A-Za-z0-9]{10}$/',
                new NoHtml()
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'recovery_code.required' => 'Recovery code is required.',
            'recovery_code.size' => 'Recovery code must be exactly 10 characters.',
            'recovery_code.regex' => 'Recovery code must contain only letters and numbers.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'recovery_code' => 'recovery code',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Remove any spaces or special characters but keep original case
        if ($this->has('recovery_code')) {
            $code = preg_replace('/[^A-Za-z0-9]/', '', $this->recovery_code);
            $this->merge(['recovery_code' => $code]);
        }
    }

    /**
     * Get the body parameters for API documentation
     */
    public function bodyParameters(): array
    {
        return [
            'recovery_code' => [
                'description' => 'The 10-character recovery code (letters and numbers, case-sensitive)',
                'example' => 'ksX5lOjXpU',
            ],
        ];
    }
}
