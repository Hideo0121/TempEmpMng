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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'lineworks' => [
        'enabled' => env('LW_ENABLED', env('LINEWORKS_ENABLED', false)),
        'auth_url' => env('LW_AUTH_URL'),
        'api_base' => env('LW_API_BASE', 'https://www.worksapis.com/v1.0'),
        'client_id' => env('LW_CLIENT_ID'),
        'client_secret' => env('LW_CLIENT_SECRET'),
        'service_account' => env('LW_SERVICE_ACCOUNT'),
        'scope' => env('LW_SCOPE', 'calendar'),
        'private_key_pem' => env('LW_PRIVATE_KEY_PEM'),
        'private_key_path' => env('LW_PRIVATE_KEY_PATH'),
        'jwt_alg' => env('LW_JWT_ALG', 'RS256'),
        'key_id' => env('LW_KEY_ID'),
        'default_tz' => env('LW_TZ', 'Asia/Tokyo'),
        'default_duration_minutes' => (int) env('LW_DEFAULT_DURATION_MINUTES', env('LINEWORKS_DEFAULT_DURATION_MINUTES', 60)),
        'calendar_id' => env('LW_CALENDAR_ID'),
        'calendar_name' => env('LW_CALENDAR_NAME'),
        'calendar_prefer_type' => env('LW_CALENDAR_PREFER_TYPE'),
        'calendar_user_id' => env('LW_CALENDAR_USER_ID'),
        'retry_attempts' => (int) env('LW_RETRY_ATTEMPTS', 3),
        'retry_delay_ms' => (int) env('LW_RETRY_DELAY_MS', 250),
    ],

];
