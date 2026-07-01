<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Doctor missed appointment grace period
    |--------------------------------------------------------------------------
    |
    | Minutes after the scheduled session end time before an unpaid start
    | (status new/rescheduled) is marked as missed for patient resolution.
    |
    */

    'doctor_missed_grace_minutes' => (int) env('APPOINTMENT_DOCTOR_MISSED_GRACE_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Relaxed session limits (testing)
    |--------------------------------------------------------------------------
    |
    | When true: doctors may start sessions before the scheduled time, overdue
    | sessions are not auto-marked as missed, and calls are not auto-disconnected
    | when extend_at passes.
    |
    */

    'relaxed_session_limits' => filter_var(
        env('APPOINTMENT_RELAXED_SESSION_LIMITS', env('APP_ENV') === 'local'),
        FILTER_VALIDATE_BOOL
    ),

    /*
    |--------------------------------------------------------------------------
    | Follow-up session options
    |--------------------------------------------------------------------------
    |
    | follow_up_allows_calls: let follow-up appointments use video/voice like new sessions.
    |
    | follow_up_skip_patient_confirmation: legacy flag (follow-ups are always booked for the
    | patient when the doctor schedules them; patient confirmation is not required).
    |
    */

    'follow_up_allows_calls' => filter_var(
        env('APPOINTMENT_FOLLOW_UP_ALLOWS_CALLS', env('APP_ENV') === 'local'),
        FILTER_VALIDATE_BOOL
    ),

    'follow_up_skip_patient_confirmation' => true,

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
