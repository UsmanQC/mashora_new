<?php

return [
    'page_heading' => 'Available specialists',
    'page_sub_with_filters' => 'Based on your session preferences.',
    'page_sub_default' => 'Choose a clinician and pick a convenient time.',

    'roles' => [
        'therapist' => 'Non-Doctor (Therapist)',
        'physician_specialist' => 'Doctor (Specialist)',
    ],

    'channel_chat' => 'Chat',
    'channel_video' => 'Video',
    'channel_voice' => 'Voice',
    'price_suffix_per' => ':currency / :minutes minutes',

    'more' => 'More',
    'less' => 'Less',

    'sar' => 'SAR',

    'available_times' => 'Available times',

    'like_incremented' => 'Saved to favourites (demo)',

    'slot_selected_toast' => 'Time slot :time saved (booking opens soon)',

    'demo_cards' => [
        [
            'id' => 'nada-alghamdi',
            'name' => 'Nada Alghamdi',
            'role_kind' => 'therapist',
            'likes' => 17,
            'bio' => 'Psychologist specialising in addictions and interpersonal challenges. Passionate about providing safe, evidenced-based interventions for adolescents and adults. Focus on supporting goals with sustainable habits and emotional regulation.',
            'price_sar' => 100,
            'session_minutes' => 15,
            'channels' => ['chat' => true, 'video' => true, 'voice' => false],
            'slots' => ['17:30', '17:45', '18:00', '18:15', '18:30', '18:45', '19:00'],
            'tags' => ['Substance abuse', 'Relationship problems', 'Obsessive compulsive disorder'],
        ],
        [
            'id' => 'khalid-mohammed',
            'name' => 'Dr. Khalid Mohammed',
            'role_kind' => 'physician_specialist',
            'likes' => 40,
            'bio' => 'Psychiatrist focusing on anxiety, mood episodes, and short-term psychotherapy integrated with pharmacotherapy when clinically appropriate.',
            'price_sar' => 150,
            'session_minutes' => 15,
            'channels' => ['chat' => true, 'video' => true, 'voice' => true],
            'slots' => ['09:30', '10:00', '10:30', '11:00', '17:30', '18:00'],
            'tags' => ['Panic', 'Sleep disorders', 'Depression'],
        ],
    ],
];
