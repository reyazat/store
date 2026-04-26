<?php

namespace IamApi\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeHtml implements ValidationRule
{
    /**
     * Allowed tag names (lowercase) when HTML is permitted.
     * Scripts and dangerous tags are always forbidden.
     */
    protected array $allowedTags = [
        // Inline formatting
        'b', 'strong', 'i', 'em', 'u', 's', 'sub', 'sup', 'small', 'mark',
        // Text blocks
        'p', 'br', 'hr', 'ul', 'ol', 'li',
        // Headings
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        // Layout / generic containers
        'div', 'span',
        // Quotes & code
        'blockquote', 'pre', 'code', 'kbd',
        // Media / tables (still filtered from scripts)
        'img',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th',
        // Links
        'a'
    ];

    /**
     * Sanitize the given attribute to contain only safe HTML
     * and absolutely no scripts, then accept the cleaned value.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || $value === '') {
            return;
        }

        $sanitized = $this->sanitize($value);

        // اگر مقدار تمیز‌شده با مقدار اصلی فرق دارد، نسخه‌ی تمیز را روی request بنویس
        // تا در $request->validated() مقدار پاک‌شده برگردد.
        if ($sanitized !== $value) {
            // توجه: این روش برای فیلدهای ساده (غیر آرایه‌ای) مناسب است.
            try {
                $request = request();
                if ($request && $request->has($attribute)) {
                    $request->merge([$attribute => $sanitized]);
                }
            } catch (\Throwable $e) {
                // اگر به هر دلیلی request در این کانتکست قابل دسترسی نبود، فقط مقدار را قبول می‌کنیم.
            }
        }

        // در حالت عادی (حتی اگر قبلاً اسکریپت داشت) بعد از sanitize خطایی برنمی‌گردانیم.
        // تنها شرط خطا می‌تواند این باشد که بعد از پاک‌سازی چیزی باقی نمانده باشد و فیلد عمداً required باشد،
        // که آن را به خود ruleهای required می‌سپاریم.
    }

    /**
     * Sanitize HTML value by removing scripts, dangerous tags,
     * event handlers and disallowed tags, keeping only safe markup.
     */
    private function sanitize(string $value): string
    {
        $sanitized = $value;

        // 1) Remove dangerous tags and their content (script, style, iframe, object, embed, link)
        $sanitized = preg_replace(
            '/<\s*(script|style|iframe|object|embed|link)[^>]*>.*?<\s*\/\s*\1\s*>/is',
            '',
            $sanitized
        );
        $sanitized = preg_replace(
            '/<\s*(script|style|iframe|object|embed|link)[^>]*>/is',
            '',
            $sanitized
        );

        // 2) Remove javascript: URLs anywhere
        $sanitized = preg_replace('/javascript\s*:/i', '', $sanitized);

        // 3) Remove inline event handlers like onclick="..."
        $sanitized = preg_replace(
            '/\s+on[a-zA-Z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i',
            '',
            $sanitized
        );

        // 4) Strip all tags except allowed ones
        $allowed = '<' . implode('><', $this->allowedTags) . '>';
        $sanitized = strip_tags($sanitized, $allowed);

        return $sanitized;
    }
}
