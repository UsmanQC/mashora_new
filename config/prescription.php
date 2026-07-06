<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Prescription PDF branding
    |--------------------------------------------------------------------------
    */

    'company' => [
        'name' => env('PRESCRIPTION_COMPANY_NAME', 'Awaan'),
        'name_ar' => env('PRESCRIPTION_COMPANY_NAME_AR', 'أوان'),
        'country' => env('PRESCRIPTION_COMPANY_COUNTRY', 'Kingdom of Saudi Arabia'),
        'country_ar' => env('PRESCRIPTION_COMPANY_COUNTRY_AR', 'المملكة العربية السعودية'),
        'website' => env('PRESCRIPTION_COMPANY_WEBSITE', 'https://awaan.io'),
        'email' => env('PRESCRIPTION_COMPANY_EMAIL', 'contact@awaan.io'),
        'phone' => env('PRESCRIPTION_COMPANY_PHONE', ''),
    ],

];
