<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment gateway driver
    |--------------------------------------------------------------------------
    |
    | Supported: "hyperpay" (production Saudi gateway), "stripe" (test locally),
    | "myfatoorah" (legacy).
    |
    */

    'driver' => env('PAYMENT_GATEWAY', 'hyperpay'),

];
