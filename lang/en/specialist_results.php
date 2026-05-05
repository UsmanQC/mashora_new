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

    'sar' => config('currency.sa_riyal_symbol'),

    'available_times' => 'Available times',

    'like_incremented' => 'Saved to favourites (demo)',

    'slot_selected_toast' => 'Time slot :time saved (booking opens soon)',

    'no_results_title' => 'No specialists match these filters',
    'no_results_hint' => 'Try changing session length, language, or sub-specialties — or go back to the filter screen.',
    'adjust_filters' => 'Adjust filters',
];
