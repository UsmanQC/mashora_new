<?php

return [

    'name' => env('PWA_NAME', 'Awaan'),

    'short_name' => env('PWA_SHORT_NAME', 'Awaan'),

    'description' => env('PWA_DESCRIPTION', 'Mental health appointments and care on Awaan.'),

    'start_url' => env('PWA_START_URL', '/patient'),

    'scope' => env('PWA_SCOPE', '/'),

    'display' => 'standalone',

    'orientation' => 'portrait-primary',

    'background_color' => env('PWA_BACKGROUND_COLOR', '#F3F5F9'),

    'theme_color' => env('PWA_THEME_COLOR', '#10B981'),

    'cache_version' => env('PWA_CACHE_VERSION', 'awaan-v1'),

    'scopes' => [
        '/patient',
        '/doctor',
    ],

    'icons' => [
        ['path' => 'images/pwa/icon-192.png', 'sizes' => '192x192', 'purpose' => 'any'],
        ['path' => 'images/pwa/icon-512.png', 'sizes' => '512x512', 'purpose' => 'any'],
        ['path' => 'images/pwa/icon-512.png', 'sizes' => '512x512', 'purpose' => 'maskable'],
    ],

];
