<?php

namespace IamApi\Http\Requests\Users;

use Closure;
use IamApi\Rules\NoHtml;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $passwordPolicy = config('iam.password', []);

        return [
            'reset_token' => [
                'required',
                'string',
                new NoHtml()
            ],
            'new_password' => [
                'required',
                'string',
                'min:' . ($passwordPolicy['min_length'] ?? 8),
                'max:' . ($passwordPolicy['max_length'] ?? 128),
                'confirmed',
                function ($attribute, $value, $fail) use ($passwordPolicy) {
                    $this->validatePasswordComplexity($value, $fail, $passwordPolicy);
                },
            ],
            'new_password_confirmation' => [
                'required',
                'string'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reset_token.required' => 'توکن بازیابی رمز عبور الزامی است.',
            'new_password.required' => 'رمز عبور جدید الزامی است.',
            'new_password.min' => 'رمز عبور جدید باید حداقل :min کاراکتر باشد.',
            'new_password.max' => 'رمز عبور جدید نباید بیشتر از :max کاراکتر باشد.',
            'new_password.confirmed' => 'تکرار رمز عبور جدید مطابقت ندارد.',
            'new_password_confirmation.required' => 'تکرار رمز عبور جدید الزامی است.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'reset_token' => 'توکن بازیابی',
            'new_password' => 'رمز عبور جدید',
            'new_password_confirmation' => 'تکرار رمز عبور جدید',
        ];
    }

    /**
     * Validate password complexity based on policy
     */
    private function validatePasswordComplexity(string $password, Closure $fail, array $policy): void
    {
        $errors = [];

        // Check uppercase requirement
        if (($policy['require_uppercase'] ?? true) && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'رمز عبور باید حداقل یک حرف بزرگ داشته باشد.';
        }

        // Check lowercase requirement
        if (($policy['require_lowercase'] ?? true) && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'رمز عبور باید حداقل یک حرف کوچک داشته باشد.';
        }

        // Check numbers requirement
        if (($policy['require_numbers'] ?? true) && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'رمز عبور باید حداقل یک عدد داشته باشد.';
        }

        // Check symbols requirement
        if (($policy['require_symbols'] ?? true) && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'رمز عبور باید حداقل یک کاراکتر خاص (مانند !@#$) داشته باشد.';
        }

        // Check for common weak patterns
        if (preg_match('/(.)\1{2,}/', $password)) {
            $errors[] = 'رمز عبور نباید بیش از ۲ کاراکتر تکراری متوالی داشته باشد.';
        }

        if (preg_match('/123|abc|qwe|password|admin/i', $password)) {
            $errors[] = 'رمز عبور نباید شامل الگوها یا کلمات رایج باشد.';
        }

        if (!empty($errors)) {
            $fail(implode(' ', $errors));
        }
    }

    /**
     * Get the body parameters for API documentation
     */
    public function bodyParameters(): array
    {
        return [
            'reset_token' => [
                'description' => 'The password reset token received via email/SMS',
                'example' => 'abc123xyz789...',
            ],
            'new_password' => [
                'description' => 'The new password (must meet complexity requirements)',
                'example' => 'NewSecurePassword123!',
            ],
            'new_password_confirmation' => [
                'description' => 'Password confirmation (must match new_password)',
                'example' => 'NewSecurePassword123!',
            ],
        ];
    }
}
