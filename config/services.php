<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'avreen' => [
        'site_host' => env('IPG_SITE_HOST', 'https://site.tj724.ir'),
        'payment_host' => env('IPG_PAYMENT_HOST', 'https://pay.tj724.ir'),
        'mid' => env('IPG_MID'),
        'terminal_id' => env('IPG_TERMINAL_ID'),
        'password' => env('IPG_PASSWORD'),
        'redirect_url' => env('IPG_REDIRECT_URL'),
        'private_key_path' => env('IPG_PRIVATE_KEY_PATH'),
        'center_public_key_path' => env('IPG_CENTER_PUBLIC_KEY_PATH'),
        'timezone' => env('IPG_TIMEZONE', 'Asia/Tehran'),
        'signature_algorithm' => env('IPG_SIGNATURE_ALGORITHM', 'SHA512withRSA'),
        'default_language' => env('IPG_DEFAULT_LANGUAGE', 'fa'),
        'txn_type_purchase' => env('IPG_TXN_TYPE_PURCHASE', '000000'),
        'callback_sign_fields' => env('IPG_CALLBACK_SIGN_FIELDS', 'status&token&mid&txnType&terminalId&amount&clientTxnId&rrn&stan&txnDate'),
        'confirm_deadline_seconds' => (int) env('IPG_CONFIRM_DEADLINE_SECONDS', 1800),
        'timeout' => (int) env('IPG_HTTP_TIMEOUT', 15),
    ],

];
