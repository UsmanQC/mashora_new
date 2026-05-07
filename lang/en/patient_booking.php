<?php

return [
    'title' => 'Book an appointment',

    'crumb_find_specialist' => 'Find a specialist',
    'crumb_book' => 'Book an appointment',

    'booking_for_label' => 'I\'m booking for',
    'for_self' => 'Myself',
    'for_other' => 'Another',

    'communication_label' => 'Preferred communication method',
    'channel_chat' => 'Chat',
    'channel_video' => 'Video',
    'channel_voice' => 'Voice',

    'patient_name' => 'Full name',
    'patient_email' => 'Email (optional)',
    'patient_phone' => 'Phone number',
    'patient_notes' => 'Briefly describe the issue',

    'specialist_name' => 'Specialist',
    'session_date' => 'Session date',
    'session_time' => 'Session time',
    'session_duration' => 'Duration (minutes)',
    'session_price' => 'Session price',
    'discount' => 'Discount',
    'total' => 'Total',
    'sar' => config('currency.sa_riyal_symbol'),
    'discount_code' => 'Discount code',

    'next' => 'Next',
    'cancel' => 'Cancel',

    'date_format' => 'd/m/Y',

    'chat_required' => 'Chat must be selected as one of the communication methods.',

    'slot_conflict' => 'This time was just booked. Please pick another slot.',

    'checkout_title' => 'Payment',
    'checkout_subtitle' => 'Review your session and pay securely.',

    'checkout_accepts' => 'Accepted payment methods',
    'payment_brand_visa' => 'Visa',
    'payment_brand_mastercard' => 'Mastercard',
    'payment_brand_mada' => 'mada',
    'payment_brand_apple_pay' => 'Apple Pay',

    'checkout_demo_badge' => 'Demo',
    'checkout_demo_banner' => 'This is a static preview for layout testing. No booking or payment is processed.',
    'checkout_demo_pay_hint' => 'The real flow redirects to MyFatoorah. Here you can only preview the screen.',
    'checkout_demo_pay_toast' => 'Demo only — use a real booking checkout to pay.',

    'pay_now' => 'Pay securely',
    'payment_processing' => 'Starting payment…',
    'payment_secure_note' => 'You will be redirected to our payment partner to complete your card payment.',
    'payment_embedded_unavailable' => 'Card fields are temporarily unavailable. Use the fallback button below to continue securely.',
    'pay_now_fallback' => 'Continue to secure payment page',
    'payment_api_missing' => 'Payment is not configured yet. Add `MYFATOORAH_API_KEY` (or legacy `MYFATOORAH_TOKEN`) to your `.env` file (see `config/myfatoorah.php`).',
    'payment_invoice_failed' => 'Could not create a payment invoice. Please try again or contact support.',
    'payment_start_failed' => 'Something went wrong while starting payment. Please try again.',
    'payment_pending_configure' => 'Add your MyFatoorah API key to `.env`, then try paying again from checkout.',
    'payment_still_pending' => 'Payment is not confirmed yet. Try again from checkout, or contact support if payment was deducted.',
    'payment_local_ssl_fallback' => 'Local SSL trust issue detected. Payment confirmation was simulated for local testing.',

    'discount_apply' => 'Apply',
    'discount_invalid' => 'This discount code is not valid.',
    'discount_applied' => 'Discount applied.',

    'appointment_number_label' => 'Reference',
    'view_appointments' => 'My appointments',

    'back_home' => 'Back to home',
    'back_specialists' => 'Find another specialist',

    'payment_success_title' => 'Payment received',
    'payment_success_body' => 'Your session is booked. You can track it under appointments.',

    'payment_failed_title' => 'Payment was not completed',
    'payment_failed_body' => 'You can go back to specialists to choose another time, or try paying again from checkout.',

    'saved_toast' => 'Booking details saved. Payment will be added in the next step.',
];
