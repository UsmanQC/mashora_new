<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Agora RTC
    |--------------------------------------------------------------------------
    |
    | Set AGORA_APP_ID and AGORA_APP_CERTIFICATE in .env (see Agora Console).
    | Keys match legacy Mashorapwa: AGORA_APP_ID, AGORA_APP_CERTIFICATE.
    |
    */

    'AGORA_APP_ID' => env('AGORA_APP_ID', ''),

    'AGORA_APP_CERTIFICATE' => env('AGORA_APP_CERTIFICATE', ''),
];
