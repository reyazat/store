<?php

namespace IamApi\Http\Requests\Users;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ForcePasswordChangeRequest extends FormRequest
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
        $passwordPolicy = config('iam.password', []);

        return [
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
            'new_password.required' => 'New password is required.',
            'new_password.min' => 'New password must be at least :min characters.',
            'new_password.max' => 'New password must not exceed :max characters.',
            'new_password.confirmed' => 'New password confirmation does not match.',
            'new_password_confirmation.required' => 'New password confirmation is required.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'new_password' => 'new password',
            'new_password_confirmation' => 'new password confirmation',
        ];
    }

    /**
     * Prepare the data for validation.
     * Decode base64 encoded passwords from frontend
     */
    protected function prepareForValidation(): void
    {
        // Decode new_password
        if ($this->has('new_password') && !empty($this->new_password)) {
            $decodedPassword = base64_decode($this->new_password, true);
            
            if ($decodedPassword !== false) {
                $this->merge(['new_password' => $decodedPassword]);
            }
        }

        // Decode new_password_confirmation
        if ($this->has('new_password_confirmation') && !empty($this->new_password_confirmation)) {
            $decodedPasswordConfirmation = base64_decode($this->new_password_confirmation, true);
            
            if ($decodedPasswordConfirmation !== false) {
                $this->merge(['new_password_confirmation' => $decodedPasswordConfirmation]);
            }
        }
    }

    /**
     * Validate password complexity based on policy
     */
    private function validatePasswordComplexity(string $password, Closure $fail, array $policy): void
    {
        $errors = [];

        // Check uppercase requirement
        if (($policy['require_uppercase'] ?? true) && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }

        // Check lowercase requirement
        if (($policy['require_lowercase'] ?? true) && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }

        // Check numbers requirement
        if (($policy['require_numbers'] ?? true) && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }

        // Check symbols requirement
        if (($policy['require_symbols'] ?? true) && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }

        // Check for common weak patterns
        if (preg_match('/(.)\1{2,}/', $password)) {
            $errors[] = 'Password cannot contain more than 2 consecutive identical characters.';
        }

        if (preg_match('/123|abc|qwe|password|admin/i', $password)) {
            $errors[] = 'Password cannot contain common patterns or words.';
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
