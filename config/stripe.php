<?php

return [

    'key' => env('STRIPE_KEY'),

    'secret' => env('STRIPE_SECRET'),

    'currency' => env('STRIPE_CURRENCY', 'sar'),

    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

];
