<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo checkout page
    |--------------------------------------------------------------------------
    |
    | When true, `/patient/checkout-demo` renders a mock checkout for UI/QA.
    | Defaults to enabled in the local environment; set PATIENT_DEMO_CHECKOUT
    | to override (e.g. false on local, or true on a staging host).
    |
    */
    'demo_checkout_enabled' => filter_var(
        env('PATIENT_DEMO_CHECKOUT', env('APP_ENV') === 'local'),
        FILTER_VALIDATE_BOOLEAN
    ),

    /*
    |--------------------------------------------------------------------------
    | Placeholder email domain
    |--------------------------------------------------------------------------
    |
    | Phone-only patient accounts need a unique email for the users table. This
    | domain is used for generated addresses and should not receive real mail.
    |
    */
    'placeholder_email_domain' => env('PATIENT_PLACEHOLDER_EMAIL_DOMAIN', 'patient.invalid'),

];
