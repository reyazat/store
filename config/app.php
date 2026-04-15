<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | another UI element where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'fa'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'fa'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'fa_IR'),

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | List of supported locales for the application.
    |
    */

    'supported_locales' => ['fa', 'en', 'ar', 'ru'],

    /*
    |--------------------------------------------------------------------------
    | Locale Cookie
    |--------------------------------------------------------------------------
    |
    | Enable/disable storing locale preference in cookie.
    |
    */

    'locale_cookie_enabled' => env('LOCALE_COOKIE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | Current API version for the application.
    |
    */

    'api_version' => env('API_VERSION', '1.0'),

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Default rate limits for API endpoints.
    |
    */

    'rate_limit' => [
        'default' => env('API_RATE_LIMIT', 60), // requests per minute
        'authenticated' => env('API_RATE_LIMIT_AUTH', 120),
        'guest' => env('API_RATE_LIMIT_GUEST', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Max Query Limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of records that can be requested via ?limit parameter.
    |
    */

    'max_query_limit' => env('MAX_QUERY_LIMIT', 100),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 characters string to ensure that all encrypted values
    | are secure. You should do this before deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | RTL Locales
    |--------------------------------------------------------------------------
    |
    | List of locales that use Right-to-Left (RTL) direction.
    |
    */

    'rtl_locales' => ['fa', 'ar'],

    /*
    |--------------------------------------------------------------------------
    | Default Pagination
    |--------------------------------------------------------------------------
    |
    | Default pagination settings for the application.
    |
    */

    'pagination' => [
        'default' => env('PAGINATION_DEFAULT', 15),
        'max' => env('PAGINATION_MAX', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for content management.
    |
    */

    'content' => [
        'default_status' => env('CONTENT_DEFAULT_STATUS', 'draft'),
        'auto_excerpt_length' => env('CONTENT_EXCERPT_LENGTH', 200),
    ],

];
