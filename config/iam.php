<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IAM Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security configurations for the IAM system including
    | password policies, authentication settings, and JWT token management.
    |
    */

    'password' => [
        'expiry_days' => (int) env('IAM_PASSWORD_EXPIRY_DAYS', 90),
        'warning_days' => (int) env('IAM_PASSWORD_WARNING_DAYS', 7),
        'history_count' => (int) env('IAM_PASSWORD_HISTORY_COUNT', 5),
        'min_length' => (int) env('IAM_PASSWORD_MIN_LENGTH', 8),
        'max_length' => (int) env('IAM_PASSWORD_MAX_LENGTH', 128),
        'require_uppercase' => (bool) env('IAM_PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => (bool) env('IAM_PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => (bool) env('IAM_PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => (bool) env('IAM_PASSWORD_REQUIRE_SYMBOLS', true),
        'allowed_symbols' => env('IAM_PASSWORD_ALLOWED_SYMBOLS', '!@#$%^&*()_+-=[]{}|;:,.<>?'),
        'prevent_common_patterns' => (bool) env('IAM_PASSWORD_PREVENT_COMMON', true),
        'prevent_user_info' => (bool) env('IAM_PASSWORD_PREVENT_USER_INFO', true),
    ],

    'authentication' => [
        'throttle' => [
            'window_seconds' => (int) env('IAM_THROTTLE_WINDOW', 900),     // 15 minutes
            'max_attempts' => (int) env('IAM_THROTTLE_MAX_ATTEMPTS', 5),   // Max attempts before requiring CAPTCHA
            'lock_attempts' => (int) env('IAM_THROTTLE_LOCK_ATTEMPTS', 10), // Before account lock
            'lock_seconds' => (int) env('IAM_THROTTLE_LOCK_DURATION', 1800), // 30 minutes
        ],
        'lockout' => [
            'max_attempts' => (int) env('IAM_LOCKOUT_MAX_ATTEMPTS', 10),
            'duration_minutes' => (int) env('IAM_LOCKOUT_DURATION_MINUTES', 30),
            'progressive' => (bool) env('IAM_LOCKOUT_PROGRESSIVE', true),
            'auto_unlock' => (bool) env('IAM_LOCKOUT_AUTO_UNLOCK', true),
            'thresholds' => [
                ['attempts' => 3, 'duration_minutes' => 1],
                ['attempts' => 5, 'duration_minutes' => 5],
                ['attempts' => 7, 'duration_minutes' => 15],
                ['attempts' => 10, 'duration_minutes' => 30],
            ],
            'unlock_methods' => ['otp', 'admin', 'time_based', 'security_question'],
        ],
        'mfa' => [
            'enabled' => (bool) env('IAM_MFA_ENABLED', true),
            'enforce_for_admins' => (bool) env('IAM_MFA_ENFORCE_ADMINS', true),
            'recovery_codes_count' => (int) env('IAM_MFA_RECOVERY_CODES', 10),
            'totp_issuer' => env('IAM_TOTP_ISSUER', config('app.name')),
            'totp_window' => (int) env('IAM_MFA_TOTP_WINDOW', 1),
            'backup_codes_enabled' => (bool) env('IAM_MFA_BACKUP_CODES', true),
        ],
    ],

    'otp' => [
        'email' => [
            'enabled' => (bool) env('IAM_OTP_EMAIL_ENABLED', true),
            'expiry_minutes' => (int) env('IAM_OTP_EMAIL_EXPIRY', 10),
            'max_attempts' => (int) env('IAM_OTP_EMAIL_MAX_ATTEMPTS', 5),
            'rate_limit_per_hour' => (int) env('IAM_OTP_EMAIL_RATE_LIMIT', 5),
        ],
        'sms' => [
            'enabled' => (bool) env('IAM_OTP_SMS_ENABLED', true),
            'expiry_minutes' => (int) env('IAM_OTP_SMS_EXPIRY', 5),
            'max_attempts' => (int) env('IAM_OTP_SMS_MAX_ATTEMPTS', 3),
            'rate_limit_per_hour' => (int) env('IAM_OTP_SMS_RATE_LIMIT', 3),
            'provider' => env('SMS_PROVIDER', 'kavenegar'),
        ],
        'code_length' => (int) env('IAM_OTP_CODE_LENGTH', 6),
        'numeric_only' => (bool) env('IAM_OTP_NUMERIC_ONLY', true),
    ],

    'jwt' => [
        'access_ttl' => (int) env('JWT_TTL', 15),                    // 15 minutes
        'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 43200),        // 30 days
        'leeway' => (int) env('JWT_LEEWAY', 60),                     // 60 seconds
        'blacklist_grace_period' => (int) env('JWT_BLACKLIST_GRACE_PERIOD', 10),
        'algorithm' => env('JWT_ALGO', 'HS256'),
        'blacklist_enabled' => (bool) env('JWT_BLACKLIST_ENABLED', true),
    ],

    'session' => [
        'single_device_only' => (bool) env('IAM_SINGLE_DEVICE_ONLY', true), // Only one device login at a time
        'concurrent_limit' => (int) env('IAM_CONCURRENT_SESSIONS', 5), // Max concurrent sessions per user (if single_device_only is false)
        'device_tracking' => (bool) env('IAM_DEVICE_TRACKING', true),
        'ip_validation' => (bool) env('IAM_IP_VALIDATION', false),      // Strict IP validation
        'user_agent_validation' => (bool) env('IAM_USER_AGENT_VALIDATION', true),
    ],

    'security' => [
        'activity_logging' => (bool) env('IAM_ACTIVITY_LOGGING', true),
        'failed_login_logging' => (bool) env('IAM_FAILED_LOGIN_LOGGING', true),
        'suspicious_activity_threshold' => (int) env('IAM_SUSPICIOUS_THRESHOLD', 10),
        'auto_lock_suspicious_accounts' => (bool) env('IAM_AUTO_LOCK_SUSPICIOUS', true),
        'ip_whitelist_enabled' => (bool) env('IAM_IP_WHITELIST_ENABLED', false),
        'device_fingerprinting' => (bool) env('IAM_DEVICE_FINGERPRINTING', false),
        'geolocation_checking' => (bool) env('IAM_GEOLOCATION_CHECKING', false),
    ],

    'rate_limiting' => [
        'api' => [
            'authenticated_per_minute' => (int) env('IAM_API_RATE_AUTHENTICATED', 60),
            'guest_per_minute' => (int) env('IAM_API_RATE_GUEST', 20),
        ],
        'login' => [
            'attempts_per_window' => (int) env('IAM_LOGIN_RATE_ATTEMPTS', 5),
            'window_minutes' => (int) env('IAM_LOGIN_RATE_WINDOW', 15),
        ],
        'registration' => [
            'attempts_per_hour' => (int) env('IAM_REGISTRATION_RATE', 3),
        ],
        'password_reset' => [
            'attempts_per_hour' => (int) env('IAM_PASSWORD_RESET_RATE', 3),
        ],
    ],

    'organization' => [
        'require_context' => (bool) env('IAM_REQUIRE_ORG_CONTEXT', true),
        'default_role' => env('IAM_DEFAULT_ORG_ROLE', 'member'),
        'max_members' => (int) env('IAM_MAX_ORG_MEMBERS', 1000),
        'invitation_expiry_hours' => (int) env('IAM_INVITATION_EXPIRY', 72), // 3 days
        'multi_tenancy_enabled' => (bool) env('IAM_MULTI_TENANCY', true),
    ],

    'features' => [
        'registration_enabled' => (bool) env('IAM_REGISTRATION_ENABLED', true),
        'password_reset_enabled' => (bool) env('IAM_PASSWORD_RESET_ENABLED', true),
        'email_verification_required' => (bool) env('IAM_EMAIL_VERIFICATION_REQUIRED', true),
        'phone_verification_enabled' => (bool) env('IAM_PHONE_VERIFICATION_ENABLED', true),
        'social_login_enabled' => (bool) env('IAM_SOCIAL_LOGIN_ENABLED', false),
        'remember_me_enabled' => (bool) env('IAM_REMEMBER_ME_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Loading
    |--------------------------------------------------------------------------
    |
    | When the consuming app loads IAM routes manually (e.g. via AppServiceProvider
    | with the correct 'api' prefix and middleware), set this to true so the
    | package's ServiceProvider does NOT auto-register routes without the api
    | middleware group. This prevents duplicate routes and security gaps.
    |
    */
    'disable_auto_routes' => (bool) env('IAM_DISABLE_AUTO_ROUTES', true),
];
