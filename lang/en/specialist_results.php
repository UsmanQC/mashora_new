<?php

return [
    'page_heading' => 'Available specialists',
    'page_heading_instant' => 'Available now',
    'page_sub_with_filters' => 'Based on your session preferences.',
    'page_sub_default' => 'Choose a clinician and pick a convenient time.',
    'page_sub_instant' => 'Specialists with a free slot in the next :minutes minutes.',
    'instant_window_hint' => 'Showing only times starting within the next :minutes minutes.',

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
    'times_timezone_note' => 'Times are shown in Saudi Arabia time (Asia/Riyadh).',
    'no_slots_for_selected_day' => 'No upcoming times left for the selected day.',

    'like_saved' => 'Saved to favourites',
    'like_removed' => 'Removed from favourites',
    'like_save' => 'Save to favourites',
    'like_login_required' => 'Sign in to save favourites',

    'slot_selected_toast' => 'Time slot :time saved (booking opens soon)',

    'no_results_title' => 'No specialists match these filters',
    'no_results_hint' => 'Try changing session length, language, or sub-specialties — or go back to the filter screen.',
    'adjust_filters' => 'Adjust filters',
    'search_placeholder' => 'Search by doctor name',
    'filters' => 'Filters',
    'today_short' => 'Today',
    'duration_minutes' => ':minutes minutes',
    'filter_title' => 'Filter your search',
    'all_option' => 'All',
    'clear' => 'Clear',
    'apply' => 'Apply',
];
