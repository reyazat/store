<?php

use IamApi\Helpers\SecurityHelper;

if (!function_exists('escape_html')) {
    /**
     * Escape HTML special characters for safe output.
     * 
     * @param mixed $value
     * @param bool $doubleEncode
     * @return string|array|null
     */
    function escape_html($value, bool $doubleEncode = true)
    {
        return SecurityHelper::escapeHtml($value, $doubleEncode);
    }
}

if (!function_exists('escape_js')) {
    /**
     * Escape for JavaScript context.
     * 
     * @param mixed $value
     * @return string
     */
    function escape_js($value): string
    {
        return SecurityHelper::escapeJs($value);
    }
}

if (!function_exists('escape_url')) {
    /**
     * Escape for URL context.
     * 
     * @param string $value
     * @return string
     */
    function escape_url(string $value): string
    {
        return SecurityHelper::escapeUrl($value);
    }
}

if (!function_exists('sanitize_html')) {
    /**
     * Sanitize HTML for safe display (allows specific tags).
     * 
     * @param string $value
     * @param array $allowedTags
     * @return string
     */
    function sanitize_html(string $value, array $allowedTags = []): string
    {
        return SecurityHelper::sanitizeHtml($value, $allowedTags);
    }
}

if (!function_exists('is_dangerous_content')) {
    /**
     * Check if a string contains potentially dangerous content.
     * 
     * @param string $value
     * @return bool
     */
    function is_dangerous_content(string $value): bool
    {
        return SecurityHelper::containsDangerousContent($value);
    }
}
