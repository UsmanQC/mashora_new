<?php

return [

    'display' => 'standalone',

    'orientation' => 'portrait-primary',

    'lang' => env('PWA_LANG', 'ar'),

    'dir' => env('PWA_DIR', 'auto'),

    'cache_version' => env('PWA_CACHE_VERSION', 'awaan-v3'),

    'apps' => [
        'patient' => [
            'name' => env('PWA_PATIENT_NAME', env('PWA_NAME', 'Awaan')),
            'short_name' => env('PWA_PATIENT_SHORT_NAME', env('PWA_SHORT_NAME', 'Awaan')),
            'description' => env(
                'PWA_PATIENT_DESCRIPTION',
                env('PWA_DESCRIPTION', 'منصة أوان للرعاية النفسية — احجز جلسات أونلاين مع مختصين مرخصين بخصوصية وأمان.'),
            ),
            'start_url' => env('PWA_PATIENT_START_URL', '/patient'),
            'scope' => env('PWA_PATIENT_SCOPE', '/patient'),
            'background_color' => env('PWA_PATIENT_BACKGROUND_COLOR', env('PWA_BACKGROUND_COLOR', '#F3F5F9')),
            'theme_color' => env('PWA_PATIENT_THEME_COLOR', env('PWA_THEME_COLOR', '#10B981')),
        ],
        'doctor' => [
            'name' => env('PWA_DOCTOR_NAME', 'Awaan Doctor'),
            'short_name' => env('PWA_DOCTOR_SHORT_NAME', 'Awaan Dr'),
            'description' => env(
                'PWA_DOCTOR_DESCRIPTION',
                'بوابة أوان للأطباء — إدارة المواعيد والجلسات والمرضى بسهولة وأمان.',
            ),
            'start_url' => env('PWA_DOCTOR_START_URL', '/doctor'),
            'scope' => env('PWA_DOCTOR_SCOPE', '/doctor'),
            'background_color' => env('PWA_DOCTOR_BACKGROUND_COLOR', '#F3F5F9'),
            'theme_color' => env('PWA_DOCTOR_THEME_COLOR', '#10B981'),
        ],
    ],

    'icons' => [
        ['path' => 'images/pwa/icon-192-v3.png', 'sizes' => '192x192', 'purpose' => 'any'],
        ['path' => 'images/pwa/icon-512-v3.png', 'sizes' => '512x512', 'purpose' => 'any'],
        ['path' => 'images/pwa/icon-192-maskable-v3.png', 'sizes' => '192x192', 'purpose' => 'maskable'],
        ['path' => 'images/pwa/icon-512-maskable-v3.png', 'sizes' => '512x512', 'purpose' => 'maskable'],
    ],

];
