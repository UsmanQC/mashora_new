<?php

/**
 * Minimal Chatify-style settings kept for legacy columns on `users`
 * (avatar, messenger colours, etc.).
 */
return [
    'user_avatar' => [
        'default' => env('CHATIFY_DEFAULT_AVATAR', 'avatar.png'),
    ],

    'colors' => [
        'default' => env('CHATIFY_MESSENGER_COLOR', '#2180f3'),
    ],
];
