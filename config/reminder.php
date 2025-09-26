<?php

return [
    'timezone' => env('REMINDER_TIMEZONE', env('APP_TIMEZONE', 'UTC')),
    'disable_30m' => env('REMINDER_DISABLE_30M', false),
    'cc_managers' => env('REMINDER_CC_MANAGERS', ''),
];
