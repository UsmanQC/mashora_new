<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Registration requires signed URL
    |--------------------------------------------------------------------------
    |
    | Matches Mashorapwa-prod: doctor registration uses a signed route. Set to
    | false in .env for local testing without generating signed URLs.
    |
    */
    'registration_invite_only' => filter_var(
        env('DOCTOR_REGISTRATION_INVITE_ONLY', false),
        FILTER_VALIDATE_BOOLEAN
    ),

];
