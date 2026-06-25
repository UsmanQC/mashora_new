<?php

/**
 * HyperPay (OPPWA) COPYandPAY credentials and environment URLs.
 */
return [
    'name' => env('HYPERPAY_NAME', 'hyperpay'),
    'env' => env('HYPERPAY_ENV', 'test'),
    'token' => env('HYPERPAY_TOKEN'),
    'entity_mode' => env('HYPERPAY_ENTITY_MODE', 'b2b'),
    'entity_id_b2c' => env('HYPERPAY_ENTITY_ID_B2C'),
    'entity_id_b2b' => env('HYPERPAY_ENTITY_ID_B2B'),

    'test' => [
        'checkout_url' => 'https://eu-test.oppwa.com/v1/checkouts',
        'widget_host' => 'eu-test.oppwa.com',
    ],
    'live' => [
        'checkout_url' => 'https://oppwa.com/v1/checkouts',
        'widget_host' => 'oppwa.com',
    ],
];
