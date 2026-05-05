<?php

return [

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
