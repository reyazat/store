<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CAPTCHA Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for mews/captcha package.
    | This package generates image-based CAPTCHA for Laravel applications.
    |
    */

    // Characters to use for CAPTCHA generation
    'characters' => ['2', '3', '4', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'j', 'm', 'n', 'p', 'q', 'r', 't', 'u', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'M', 'N', 'P', 'Q', 'R', 'T', 'U', 'X', 'Y', 'Z'],

    'default' => [
        'length' => (int) env('CAPTCHA_LENGTH', 7),
        'width' => (int) env('CAPTCHA_WIDTH', 220),
        'height' => (int) env('CAPTCHA_HEIGHT', 60),
        'quality' => (int) env('CAPTCHA_QUALITY', 90),
        'math' => (bool) env('CAPTCHA_MATH', false),
        'expire' => (int) env('CAPTCHA_EXPIRE', 60),
        'encrypt' => (bool) env('CAPTCHA_ENCRYPT', true),
        'sensitive' => false,
        'fontColors' => ['#2c3e50', '#8e44ad', '#16a085', '#c0392b', '#f39c12'],
        'font' => storage_path('fonts/Vazirmatn-Light.ttf'),
        'angle' => 15,
        'sharpen' => 5,
        'blur' => 0,
        'invert' => false,
        'contrast' => 0,
        'bgColor' => '#ffffff',
        'bgImage' => false,
        'lines' => 3,
        'lineColor' => '#cccccc',
        'lineWidth' => 1,
        'textLeftPadding' => 10,
    ],

    'math' => [
        'length' => 9,
        'width' => 120,
        'height' => 36,
        'quality' => 90,
        'math' => true,
        'font' => storage_path('fonts/Vazirmatn-Light.ttf'),
        'bgColor' => '#ffffff',
        'bgImage' => false,
        'fontColors' => ['#2c3e50', '#8e44ad', '#16a085', '#c0392b', '#f39c12'],
        'lines' => 3,
        'lineColor' => '#cccccc',
        'lineWidth' => 1,
        'textLeftPadding' => 10,
    ],

    'flat' => [
        'length' => 7,
        'width' => 200,
        'height' => 46,
        'quality' => 90,
        'font' => storage_path('fonts/Vazirmatn-Light.ttf'),
        'lines' => 6,
        'bgImage' => false,
        'bgColor' => '#ecf2f4',
        'fontColors' => ['#2c3e50', '#c0392b', '#16a085', '#c0392b', '#8e44ad', '#303f9f', '#f57c00', '#795548'],
        'contrast' => -5,
        'lineColor' => '#cccccc',
        'lineWidth' => 1,
        'textLeftPadding' => 10,
    ],

    'mini' => [
        'length' => 5,
        'width' => 100,
        'height' => 32,
        'font' => storage_path('fonts/Vazirmatn-Light.ttf'),
        'bgColor' => '#ffffff',
        'bgImage' => false,
        'fontColors' => ['#2c3e50', '#8e44ad', '#16a085', '#c0392b', '#f39c12'],
        'lines' => 2,
        'lineColor' => '#cccccc',
        'lineWidth' => 1,
        'textLeftPadding' => 5,
    ],

    'inverse' => [
        'length' => 7,
        'width' => 200,
        'height' => 36,
        'quality' => 90,
        'font' => storage_path('fonts/Vazirmatn-Light.ttf'),
        'sensitive' => true,
        'angle' => 12,
        'sharpen' => 10,
        'blur' => 2,
        'invert' => true,
        'contrast' => -5,
        'bgColor' => '#ffffff',
        'bgImage' => false,
        'fontColors' => ['#2c3e50', '#8e44ad', '#16a085', '#c0392b', '#f39c12'],
        'lines' => 3,
        'lineColor' => '#cccccc',
        'lineWidth' => 1,
        'textLeftPadding' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | CAPTCHA API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting for CAPTCHA generation endpoint to prevent abuse.
    | max_requests: Maximum number of CAPTCHA requests per time window
    | time_window: Time window in minutes
    |
    */

    'rate_limit' => [
        'max_requests' => (int) env('CAPTCHA_MAX_REQUESTS', 60),
        'time_window' => (int) env('CAPTCHA_TIME_WINDOW', 1), // minutes
    ],
];
