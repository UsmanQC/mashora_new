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
    'user' => env('DREAMS_SMS_USER'),
    'secret_key' => env('DREAMS_SMS_SECRET_KEY'),
    'sender' => env('DREAMS_SMS_SENDER', 'Awaan'),
];
