<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'sokrosok' => [
        // Token baca-saja untuk app lain (dipisah koma agar mudah rotasi). Min. 40 karakter per token.
        'read_tokens' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('SOKROSOK_READ_TOKENS', ''))
        ))),
        // Zona waktu MySQL tempat bin_logs.created_at (CURRENT_TIMESTAMP) disimpan. Ubah ke UTC bila jam server DB sudah UTC.
        'log_db_timezone' => env('SOKROSOK_LOG_DB_TIMEZONE', 'Asia/Jakarta'),
    ],

];