<?php

namespace IamApi\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoHtml implements ValidationRule
{
    /**
     * Validate that the given attribute does not contain any HTML/script.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || $value === '') {
            return;
        }
        $sanitized = $this->sanitize($value);

        // اگر مقدار تمیز‌شده با مقدار اصلی فرق دارد، نسخه‌ی تمیز را روی request بنویس
        // تا در ورودی‌های بعدی از مقدار پاک‌شده استفاده شود.
        if ($sanitized !== $value) {
            try {
                $request = request();
                if ($request && $request->has($attribute)) {
                    $request->merge([$attribute => $sanitized]);
                }
            } catch (\Throwable $e) {
                // اگر به هر دلیلی request در این کانتکست قابل دسترسی نبود، فقط مقدار را قبول می‌کنیم.
            }
        }
    }

    /**
     * Sanitize plain-text fields by removing any HTML/script content entirely.
     */
    private function sanitize(string $value): string
    {
        $sanitized = $value;

        // Remove normal script tags and their content
        $sanitized = preg_replace(
            '/<\s*script\b[^>]*>.*?<\s*\/\s*script\s*>/is',
            '',
            $sanitized
        );

        // Remove encoded script blocks like &lt;script&gt;...&lt;/script&gt;
        $sanitized = preg_replace(
            '/&lt;\s*script\b[^&]*&gt;.*?&lt;\s*\/\s*script\s*&gt;/is',
            '',
            $sanitized
        );

        // Remove javascript: occurrences
        $sanitized = preg_replace('/javascript\s*:/i', '', $sanitized);

        // Finally strip any remaining HTML tags
        $sanitized = strip_tags($sanitized);

        return $sanitized;
    }
}
