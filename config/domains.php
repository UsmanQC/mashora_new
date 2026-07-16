<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Portal subdomains
    |--------------------------------------------------------------------------
    |
    | When set, patient/doctor portals are served on these hosts (no /patient
    | or /doctor path prefix). Leave empty/null to keep path-based portals
    | on APP_URL (e.g. local: /patient, /doctor).
    |
    | Production example:
    |   PATIENT_DOMAIN=patient.awaan.io
    |   DOCTOR_DOMAIN=doctor.awaan.io
    |
    */

    'patient' => env('PATIENT_DOMAIN'),

    'doctor' => env('DOCTOR_DOMAIN'),

];
