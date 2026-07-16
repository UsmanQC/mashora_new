<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase service account credentials (server → FCM HTTP v1)
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

    /*
    |--------------------------------------------------------------------------
    | Firebase web config (PWA browser push)
    |--------------------------------------------------------------------------
    |
    | From Firebase Console → Project settings → Your apps → Web app.
    | These values are public by design (shipped to the browser).
    |
    */
    'web' => [
        'api_key' => env('FIREBASE_WEB_API_KEY'),
        'auth_domain' => env('FIREBASE_WEB_AUTH_DOMAIN'),
        'project_id' => env('FIREBASE_WEB_PROJECT_ID', env('FIREBASE_PROJECT_ID')),
        'storage_bucket' => env('FIREBASE_WEB_STORAGE_BUCKET'),
        'messaging_sender_id' => env('FIREBASE_WEB_MESSAGING_SENDER_ID'),
        'app_id' => env('FIREBASE_WEB_APP_ID'),
        'measurement_id' => env('FIREBASE_WEB_MEASUREMENT_ID'),
        'vapid_key' => env('FIREBASE_VAPID_KEY'),
    ],

];
