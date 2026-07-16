<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Live SMS toggle
    |--------------------------------------------------------------------------
    |
    | When true, App\Services\SmsService dispatches real SMS even outside the
    | production environment. Set SMS_LIVE=true in .env to receive OTPs on a
    | real device while APP_ENV stays as local/staging. In production this is
    | implicitly enabled.
    */
    'live' => env('SMS_LIVE', false),

    /*
    |--------------------------------------------------------------------------
    | Dreams.sa SMS (Saudi numbers — digits starting with 966)
    |--------------------------------------------------------------------------
    */
    'user' => env('DREAMS_SMS_USER', 'Scriptoot'),
    'secret_key' => env('DREAMS_SMS_SECRET_KEY', 'bb71962dab4b29fc8d91557b5e324ae26c9d69d2589a9685fd98a096bf2759db'),
    'sender' => env('DREAMS_SMS_SENDER', 'Awaan'),
];
