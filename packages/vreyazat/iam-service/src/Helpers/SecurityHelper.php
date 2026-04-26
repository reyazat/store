<?php

namespace IamApi\Helpers;

/**
 * Security Helper
 * 
 * Provides security utilities for output escaping and sanitization.
 */
class SecurityHelper
{
    /**
     * Escape HTML special characters for safe output in HTML context.
     * 
     * This prevents XSS attacks by converting special characters to HTML entities.
     * Use this when outputting user-generated content in HTML.
     * 
     * @param mixed $value The value to escape
     * @param bool $doubleEncode Whether to encode existing HTML entities
     * @return string|array|null The escaped value
     */
    public static function escapeHtml($value, bool $doubleEncode = true)
    {
        if (is_null($value)) {
            return null;
        }

        if (is_array($value)) {
            return array_map(fn($item) => self::escapeHtml($item, $doubleEncode), $value);
        }

        if (is_object($value)) {
            return $value;
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8', $doubleEncode);
    }

    /**
     * Escape for JavaScript context.
     * 
     * Use this when embedding data in JavaScript code.
     * 
     * @param mixed $value The value to escape
     * @return string The escaped value
     */
    public static function escapeJs($value): string
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
        }

        // Escape for JavaScript string context
        $value = (string) $value;
        $value = str_replace(['\\', "'", '"', "\n", "\r", "\t", '<', '>'], 
                           ['\\\\', "\\'", '\\"', '\\n', '\\r', '\\t', '\\x3C', '\\x3E'], 
                           $value);
        
        return $value;
    }

    /**
     * Escape for URL context.
     * 
     * Use this when embedding data in URLs.
     * 
     * @param string $value The value to escape
     * @return string The escaped value
     */
    public static function escapeUrl(string $value): string
    {
        return rawurlencode($value);
    }

    /**
     * Escape for CSS context.
     * 
     * Use this when embedding data in CSS.
     * 
     * @param string $value The value to escape
     * @return string The escaped value
     */
    public static function escapeCss(string $value): string
    {
        // Remove any characters that could break out of CSS context
        return preg_replace('/[^a-zA-Z0-9\-_]/', '', $value);
    }

    /**
     * Sanitize HTML for safe display (allows specific tags).
     * 
     * This is more permissive than escapeHtml but still removes dangerous content.
     * Use this for rich text content that needs to preserve some HTML.
     * 
     * @param string $value The HTML to sanitize
     * @param array $allowedTags List of allowed HTML tags
     * @return string The sanitized HTML
     */
    public static function sanitizeHtml(string $value, array $allowedTags = []): string
    {
        if (empty($allowedTags)) {
            $allowedTags = ['b', 'strong', 'i', 'em', 'u', 'p', 'br', 'ul', 'ol', 'li', 'a'];
        }

        // Remove dangerous tags and their content
        $value = preg_replace(
            '/<\s*(script|style|iframe|object|embed|link)[^>]*>.*?<\s*\/\s*\1\s*>/is',
            '',
            $value
        );

        // Remove javascript: URLs
        $value = preg_replace('/javascript\s*:/i', '', $value);

        // Remove inline event handlers
        $value = preg_replace(
            '/\s+on[a-zA-Z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i',
            '',
            $value
        );

        // Strip all tags except allowed ones
        $allowed = '<' . implode('><', $allowedTags) . '>';
        $value = strip_tags($value, $allowed);

        return $value;
    }

    /**
     * Check if a string contains potentially dangerous content.
     * 
     * @param string $value The value to check
     * @return bool True if dangerous content is detected
     */
    public static function containsDangerousContent(string $value): bool
    {
        // Check for script tags
        if (preg_match('/<\s*script\b/i', $value)) {
            return true;
        }

        // Check for javascript: URLs
        if (preg_match('/javascript\s*:/i', $value)) {
            return true;
        }

        // Check for event handlers
        if (preg_match('/\s+on[a-zA-Z]+\s*=/i', $value)) {
            return true;
        }

        // Check for data: URLs with HTML
        if (preg_match('/data:text\/html/i', $value)) {
            return true;
        }

        return false;
    }

    /**
     * Escape array values recursively for safe output.
     * 
     * @param array $data The array to escape
     * @param bool $doubleEncode Whether to encode existing HTML entities
     * @return array The escaped array
     */
    public static function escapeArray(array $data, bool $doubleEncode = true): array
    {
        $escaped = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $escaped[$key] = self::escapeArray($value, $doubleEncode);
            } elseif (is_string($value)) {
                $escaped[$key] = self::escapeHtml($value, $doubleEncode);
            } else {
                $escaped[$key] = $value;
            }
        }
        
        return $escaped;
    }
}
