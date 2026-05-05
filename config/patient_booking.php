<?php

return [

    /**
     * When a catalog specialist has no explicit `doctor_database_id`, use this real `doctors.id`
     * so time-slot clicks can open patient.book-appointments (local/staging). Production maps each
     * catalog row to the correct doctor id; for dev, set this to a seeded doctor with `doctor_duration` rows.
     */
    'catalog_doctor_fallback_id' => env('PATIENT_BOOKING_DEMO_DOCTOR_ID') !== null && env('PATIENT_BOOKING_DEMO_DOCTOR_ID') !== ''
        ? (int) env('PATIENT_BOOKING_DEMO_DOCTOR_ID')
        : null,

    /**
     * Optional promo codes for testing: `[ 'CODE' => [ 'type' => 'fixed', 'amount' => 10.0 ], ... ]` or `percent`.
     *
     * @var array<string, array{type: string, amount?: float, percent?: float}>
     */
    'discount_codes' => [
        'DEMO10' => ['type' => 'fixed', 'amount' => 10.0],
    ],
];
