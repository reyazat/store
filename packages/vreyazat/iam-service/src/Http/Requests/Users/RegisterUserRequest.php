<?php

namespace IamApi\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use IamApi\Rules\NoHtml;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    /**
     * Prepare the data for validation — normalize phone before rules run.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone') && $this->phone) {
            $this->merge(['phone' => $this->normalizePhone($this->phone)]);
        }

        if ($this->has('email') && $this->email) {
            $this->merge(['email' => strtolower($this->email)]);
        }
    }

    public function rules(): array 
    {
        return [
            'name' => ['required', 'string', 'max:255', new NoHtml()],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', new NoHtml()],
            'phone' => ['nullable', 'string', 'regex:/^\+?[1-9]\d{1,14}$/', 'max:20', 'unique:users,phone', new NoHtml()],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'password_confirmation' => ['required', 'string'],
            'send_verification_email' => ['boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام و نام خانوادگی الزامی است',
            'name.max' => 'نام نباید بیشتر از ۲۵۵ کاراکتر باشد',
            'email.required' => 'آدرس ایمیل الزامی است',
            'email.email' => 'لطفاً یک آدرس ایمیل معتبر وارد کنید',
            'email.unique' => 'این آدرس ایمیل قبلاً ثبت شده است',
            'phone.regex' => 'شماره موبایل باید به فرمت معتبر وارد شود (مثال: ۰۹۱۲۳۴۵۶۷۸۹)',
            'phone.unique' => 'این شماره موبایل قبلاً ثبت شده است',
            'password.required' => 'رمز عبور الزامی است',
            'password.confirmed' => 'تکرار رمز عبور مطابقت ندارد',
            'password_confirmation.required' => 'تکرار رمز عبور الزامی است'
        ];
    }

    /**
     * Normalize an Iranian phone number to E.164 format (+98XXXXXXXXX).
     *
     * Handles all common user inputs:
     *   09123456789  → +989123456789
     *   9123456789   → +989123456789
     *   989123456789 → +989123456789
     *  +989123456789 → +989123456789  (already correct)
     */
    private function normalizePhone(string $phone): string
    {
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        if (str_starts_with($cleaned, '+98') && strlen($cleaned) === 13) {
            return $cleaned;
        }

        $digits = ltrim($cleaned, '+');

        if (preg_match('/^989\d{9}$/', $digits)) {
            return '+' . $digits;
        }

        if (preg_match('/^09\d{9}$/', $digits)) {
            return '+98' . substr($digits, 1);
        }

        if (preg_match('/^9\d{9}$/', $digits)) {
            return '+98' . $digits;
        }

        return str_starts_with($cleaned, '+') ? $cleaned : '+' . $digits;
    }

    /**
     * Get the body parameters for API documentation
     */
    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'The user\'s full name',
                'example' => 'John Doe',
            ],
            'email' => [
                'description' => 'The user\'s email address',
                'example' => 'john.doe@example.com',
            ],
            'phone' => [
                'description' => 'The user\'s phone number (optional)',
                'example' => '+989123456789',
            ],
            'password' => [
                'description' => 'The user\'s password (min 8 chars, mixed case, numbers, symbols)',
                'example' => 'SecurePassword123!',
            ],
            'password_confirmation' => [
                'description' => 'Password confirmation (must match password)',
                'example' => 'SecurePassword123!',
            ],
            'send_verification_email' => [
                'description' => 'Whether to send verification email',
                'example' => true,
            ],
        ];
    }
}
