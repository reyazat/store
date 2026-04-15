<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    |
    | Cross-Origin Resource Sharing (CORS) configuration for API.
    |
    */

     'enabled' => env('CORS_ENABLED', true),

    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '*')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'allowed_methods' => ['*'],

    'max_age' => env('CORS_MAX_AGE', 86400), // 24 hours

    'supports_credentials' => env('CORS_SUPPORTS_CREDENTIALS', true),

];
