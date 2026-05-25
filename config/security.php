<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Headers Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security headers to protect against XSS, clickjacking,
    | and other common web vulnerabilities.
    |
    */

    'headers' => [
        /*
        | Content Security Policy (CSP)
        | Controls which resources can be loaded to prevent XSS attacks
        */
        'csp' => [
            'enabled' => env('SECURITY_CSP_ENABLED', true),
            'directives' => [
                'default-src' => ["'self'"],
                'script-src' => [
                    "'self'",
                    "'unsafe-inline'",
                    "'unsafe-eval'",
                    'https://cdn.jsdelivr.net',
                    'https://unpkg.com',
                    'https://cdnjs.cloudflare.com',
                    'http://127.0.0.1:5173',
                    'http://localhost:5173',
                    'https://store.tejaratech.ir',
                    'http://tejaratech.ir',
                    'https://tejaratech.ir',
                ],
                'style-src' => [
                    "'self'",
                    "'unsafe-inline'",
                    'https://fonts.googleapis.com',
                    'https://fonts.bunny.net',
                    'https://unpkg.com',
                    'https://cdnjs.cloudflare.com',
                    'http://127.0.0.1:5173',
                    'http://localhost:5173',
                    'https://store.tejaratech.ir',
                    'https://tejaratech.ir',
                ],
                'img-src' => ["'self'", 'data:', 'https:', 'https://store.tejaratech.ir', 'https://tejaratech.ir'],
                'font-src' => [
                    "'self'",
                    'data:',
                    'https://fonts.gstatic.com',
                    'https://fonts.bunny.net',
                    'https://store.tejaratech.ir',
                    'https://tejaratech.ir',
                ],
                'connect-src' => [
                    "'self'",
                    'http://127.0.0.1:5173',
                    'http://localhost:5173',
                    'ws://127.0.0.1:5173',
                    'ws://localhost:5173',
                    'https://store.tejaratech.ir',
                    'https://tejaratech.ir',
                ],
                'frame-ancestors' => ["'none'"],
                'base-uri' => ["'self'"],
                'form-action' => ["'self'"],
            ],
        ],
        /*
        | X-Content-Type-Options
        | Prevents MIME sniffing attacks
        */
        'x_content_type_options' => env('SECURITY_X_CONTENT_TYPE_OPTIONS', 'nosniff'),

        /*
        | X-Frame-Options
        | Prevents clickjacking attacks
        */
        'x_frame_options' => env('SECURITY_X_FRAME_OPTIONS', 'DENY'),

        /*
        | X-XSS-Protection
        | Legacy XSS protection (mostly replaced by CSP)
        */
        'x_xss_protection' => env('SECURITY_X_XSS_PROTECTION', '1; mode=block'),

        /*
        | Referrer-Policy
        | Controls how much referrer information is sent
        */
        'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),

        /*
        | Permissions-Policy
        | Restricts browser features
        */
        'permissions_policy' => [
            'geolocation' => [],
            'microphone' => [],
            'camera' => [],
            'payment' => [],
            'usb' => [],
            'magnetometer' => [],
            'gyroscope' => [],
            'accelerometer' => [],
        ],

        /*
        | Strict-Transport-Security (HSTS)
        | Forces HTTPS connections
        */
        'hsts' => [
            'enabled' => env('SECURITY_HSTS_ENABLED', true),
            'max_age' => env('SECURITY_HSTS_MAX_AGE', 31536000), // 1 year
            'include_subdomains' => env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', true),
            'preload' => env('SECURITY_HSTS_PRELOAD', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Escaping Configuration
    |--------------------------------------------------------------------------
    |
    | Configure automatic output escaping in API resources.
    |
    */

    'output_escaping' => [
        /*
        | Enable automatic output escaping in resources
        */
        'enabled' => env('SECURITY_OUTPUT_ESCAPING_ENABLED', true),

        /*
        | Fields that should never be escaped (IDs, timestamps, etc.)
        */
        'unescaped_fields' => [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
            'email_verified_at',
            'phone_verified_at',
            'last_login_at',
            'password_expires_at',
            'locked_until',
            'mfa_enabled',
            'is_super_admin',
            'is_locked',
            'must_change_password',
            'status',
            'organization_id',
            'user_id',
            'department_id',
            'role_id',
            'permission_id',
        ],

        /*
        | Allowed HTML tags for fields that need to preserve HTML
        */
        'allowed_html_tags' => [
            'b', 'strong', 'i', 'em', 'u', 's', 'sub', 'sup', 'small', 'mark',
            'p', 'br', 'hr', 'ul', 'ol', 'li',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'div', 'span',
            'blockquote', 'pre', 'code', 'kbd',
            'img', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th',
            'a',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Input Sanitization Configuration
    |--------------------------------------------------------------------------
    |
    | Configure input sanitization rules.
    |
    */

    'input_sanitization' => [
        /*
        | Enable automatic input sanitization
        */
        'enabled' => env('SECURITY_INPUT_SANITIZATION_ENABLED', true),

        /*
        | Log sanitization events for security monitoring
        */
        'log_sanitization' => env('SECURITY_LOG_SANITIZATION', false),
    ],
];
