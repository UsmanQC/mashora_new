<?php

return [
    'page_heading' => 'الأخصائيون المتاحون',
    'page_sub_with_filters' => 'بناءً على تفضيلات الجلسة التي اخترتها.',
    'page_sub_default' => 'اختر أخصائيًا وحدد وقتًا يناسبك.',

    'roles' => [
        'therapist' => 'غير طبيب (معالج نفسي)',
        'physician_specialist' => 'طبيب (أخصائي نفسي)',
    ],

    'channel_chat' => 'محادثة',
    'channel_video' => 'فيديو',
    'channel_voice' => 'صوت',

    'price_suffix_per' => ':currency / :minutes دقيقة',

    'more' => 'المزيد',
    'less' => 'إخفاء',

    'sar' => 'ر.س',

    'available_times' => 'الأوقات المتاحة',

    'like_incremented' => 'تم الإضافة للمفضلة (عرض تجريبي)',

    'slot_selected_toast' => 'تم حفظ الموعد :time (الحجز قريبًا)',

    'demo_cards' => [
        [
            'id' => 'nada-alghamdi',
            'name' => 'ندى الغامدي',
            'role_kind' => 'therapist',
            'likes' => 17,
            'bio' => 'أخصائية نفسية بتركيز على الإدمان والعلاقات. تهتم بتقديم تدخلات آمنة مبنية على الأدلة للمراهقين والبالغين، مع تمكين انظباط الانفعال وبناء أهداف واقعية طويلة الأمد.',
            'price_sar' => 100,
            'session_minutes' => 15,
            'channels' => ['chat' => true, 'video' => true, 'voice' => false],
            'slots' => ['17:30', '17:45', '18:00', '18:15', '18:30', '18:45', '19:00'],
            'tags' => ['أساءة المواد المخدرة', 'مشاكل العلاقات', 'وسواس قهري'],
        ],
        [
            'id' => 'khalid-mohammed',
            'name' => 'د. خالد محمد',
            'role_kind' => 'physician_specialist',
            'likes' => 40,
            'bio' => 'طبيب نفسي يركز على القلق واضطرابات المزاج، مع توفيرعلاج دوائي عند اللزوم ومتابعة مختصرة للعلاج النفسي.',
            'price_sar' => 150,
            'session_minutes' => 15,
            'channels' => ['chat' => true, 'video' => true, 'voice' => true],
            'slots' => ['09:30', '10:00', '10:30', '11:00', '17:30', '18:00'],
            'tags' => ['هلع', 'اضطرابات النوم', 'اكتئاب'],
        ],
    ],
];
