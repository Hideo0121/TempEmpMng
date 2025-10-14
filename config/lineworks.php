<?php

return [
    'enabled' => (bool) env('LINEWORKS_ENABLED', false),
    'base_url' => env('LINEWORKS_BASE_URL', 'https://www.worksapis.com/v1.0'),
    'calendar_id' => env('LINEWORKS_CALENDAR_ID'),
    'access_token' => env('LINEWORKS_ACCESS_TOKEN'),
    'default_duration_minutes' => (int) env('LINEWORKS_DEFAULT_DURATION_MINUTES', 60),
    'timezone' => env('LINEWORKS_TIMEZONE', 'Asia/Tokyo'),
];
