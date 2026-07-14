<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase service account credentials
    |--------------------------------------------------------------------------
    |
    | Absolute path, or a path relative to the project root, to the Firebase
    | service account JSON downloaded from Project settings → Service accounts.
    |
    */
    'firebase_credentials' => env('FIREBASE_CREDENTIALS', 'storage/app/firebase/service-account.json'),

    /*
    |--------------------------------------------------------------------------
    | Firebase project ID
    |--------------------------------------------------------------------------
    |
    | Optional override. When empty, the project_id from the credentials JSON
    | is used (e.g. awaan-66719).
    |
    */
    'firebase_project_id' => env('FIREBASE_PROJECT_ID'),

];
