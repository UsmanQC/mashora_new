<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Doctor missed appointment grace period
    |--------------------------------------------------------------------------
    |
    | Minutes after the scheduled session end time before an unpaid start
    | (status new/rescheduled) is marked as missed and refunded.
    |
    */

    'doctor_missed_grace_minutes' => (int) env('APPOINTMENT_DOCTOR_MISSED_GRACE_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Follow-up appointment window
    |--------------------------------------------------------------------------
    |
    | Days after the parent session date when a doctor may offer a free
    | follow-up using their available working hours.
    |
    */

    'follow_up_window_days' => (int) env('APPOINTMENT_FOLLOW_UP_WINDOW_DAYS', 14),

];
