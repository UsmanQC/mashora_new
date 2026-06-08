<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment gateway driver
    |--------------------------------------------------------------------------
    |
    | Supported: "stripe" (test locally), "myfatoorah" (production Saudi gateway).
    |
    */

    'driver' => env('PAYMENT_GATEWAY', 'myfatoorah'),

];
