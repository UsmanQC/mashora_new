<?php

return [
    'title' => 'حجز موعد',

    'crumb_find_specialist' => 'البحث عن أخصائي',
    'crumb_book' => 'حجز موعد',

    'booking_for_label' => 'أحجز لـ',
    'for_self' => 'نفسي',
    'for_other' => 'شخص آخر',

    'communication_label' => 'طريقة التواصل المفضلة',
    'channel_chat' => 'محادثة',
    'channel_video' => 'فيديو',
    'channel_voice' => 'صوت',

    'patient_name' => 'الاسم الكامل',
    'patient_email' => 'البريد (اختياري)',
    'patient_phone' => 'رقم الجوال',
    'patient_notes' => 'وصف موجز للحالة',

    'specialist_name' => 'الأخصائي',
    'session_date' => 'تاريخ الجلسة',
    'session_time' => 'وقت الجلسة',
    'session_duration' => 'المدة (دقيقة)',
    'session_price' => 'سعر الجلسة',
    'discount' => 'خصم',
    'total' => 'الإجمالي',
    'sar' => 'ر.س',
    'discount_code' => 'رمز الخصم',

    'next' => 'التالي',
    'cancel' => 'إلغاء',

    'date_format' => 'd/m/Y',

    'chat_required' => 'يجب تضمين المحادثة ضمن طرق التواصل.',

    'slot_conflict' => 'تم حجز هذا الوقت للتو. يُرجى اختيار وقت آخر.',

    'checkout_title' => 'الدفع',
    'checkout_subtitle' => 'راجع تفاصيل الجلسة وأكمل الدفع بأمان.',

    'checkout_accepts' => 'طرق الدفع المقبولة',
    'payment_brand_visa' => 'فيزا',
    'payment_brand_mastercard' => 'ماستركارد',
    'payment_brand_mada' => 'مدى',
    'payment_brand_apple_pay' => 'Apple Pay',

    'checkout_demo_badge' => 'تجريبي',
    'checkout_demo_banner' => 'هذه معاينة ثابتة لاختبار الواجهة. لا يُنشأ حجز ولا يُنفَّذ دفع.',
    'checkout_demo_pay_hint' => 'في الواقع الفعلي ستُعاد التوجيه إلى MyFatoorah. هنا المعاينة فقط.',
    'checkout_demo_pay_toast' => 'وضع تجريبي — استخدم الدفع بعد حجز حقيقي.',

    'pay_now' => 'ادفع بأمان',
    'payment_processing' => 'جارٍ بدء الدفع…',
    'payment_secure_note' => 'ستُعاد توجيهك إلى بوابة الدفع لإتمام الدفع بالبطاقة.',
    'payment_api_missing' => 'لم تُضبط بوابة الدفع بعد. أضف `MYFATOORAH_API_KEY` (أو `MYFATOORAH_TOKEN` القديم) في ملف `.env` (انظر `config/myfatoorah.php`).',
    'payment_invoice_failed' => 'تعذّر إنشاء فاتورة الدفع. حاول مرة أخرى أو تواصل مع الدعم.',
    'payment_start_failed' => 'حدث خطأ أثناء بدء الدفع. حاول مرة أخرى.',
    'payment_pending_configure' => 'أضف مفتاح MyFatoorah في `.env` ثم أعد المحاولة من صفحة الدفع.',
    'payment_still_pending' => 'لم يُؤكد الدفع بعد. أعد المحاولة من صفحة الدفع أو تواصل مع الدعم إذا تم خصم المبلغ.',

    'discount_apply' => 'تطبيق',
    'discount_invalid' => 'رمز الخصم غير صالح.',
    'discount_applied' => 'تم تطبيق الخصم.',

    'appointment_number_label' => 'المرجع',
    'view_appointments' => 'مواعيدي',

    'back_home' => 'العودة إلى الرئيسية',
    'back_specialists' => 'البحث عن أخصائي آخر',

    'payment_success_title' => 'تم استلام الدفع',
    'payment_success_body' => 'تم حجز جلستك. يمكنك متابعة المواعيد من القائمة.',

    'payment_failed_title' => 'لم يكتمل الدفع',
    'payment_failed_body' => 'يمكنك العودة لقائمة الأخصائيين لاختيار وقت آخر، أو إعادة المحاولة من صفحة الدفع.',

    'saved_toast' => 'تم حفظ بيانات الحجز. سيتم إضافة الدفع في الخطوة التالية.',
];
