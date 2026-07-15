<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Doctor missed appointment grace period
    |--------------------------------------------------------------------------
    |
    | Minutes after the scheduled session start time before an unstarted session
    | (status new/rescheduled, no actual_start_at) is marked as missed for patient
    | reschedule or refund.
    |
    */

    'doctor_missed_grace_minutes' => (int) env('APPOINTMENT_DOCTOR_MISSED_GRACE_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Relaxed session limits (testing)
    |--------------------------------------------------------------------------
    |
    | When true: doctors may start sessions before the scheduled time, and calls are not
    | auto-disconnected when extend_at passes. Missed-session marking still runs.
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

    /*
    |--------------------------------------------------------------------------
    | Post-session chat window
    |--------------------------------------------------------------------------
    |
    | Hours after the session ends when chat stays open for patients and
    | doctors (completed appointments and confirmed follow-ups).
    |
    */

    'post_session_chat_window_hours' => (int) env('APPOINTMENT_POST_SESSION_CHAT_WINDOW_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Follow-up session duration
    |--------------------------------------------------------------------------
    |
    | Minutes allowed for a free follow-up video/voice session. Calls auto-end
    | when this limit is reached (via extend_at on session start).
    |
    */

    'follow_up_session_duration_minutes' => (int) env('APPOINTMENT_FOLLOW_UP_SESSION_DURATION_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Doctor-scheduled paid appointment payment window
    |--------------------------------------------------------------------------
    |
    | Minutes the patient has to confirm and pay after the doctor schedules a
    | new paid appointment following a follow-up session.
    |
    */

    'doctor_scheduled_payment_grace_minutes' => (int) env('APPOINTMENT_DOCTOR_SCHEDULED_PAYMENT_GRACE_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Payment-missed reschedule window
    |--------------------------------------------------------------------------
    |
    | Hours after the payment-missed appointment was created during which the
    | patient may choose a new slot. After this window the item is removed.
    |
    */

    'payment_missed_reschedule_window_hours' => (int) env('APPOINTMENT_PAYMENT_MISSED_RESCHEDULE_WINDOW_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Instant consultation window
    |--------------------------------------------------------------------------
    |
    | Minutes from now within which a doctor must have a free slot to appear
    | in instant booking results.
    |
    */

    'instant_window_minutes' => (int) env('APPOINTMENT_INSTANT_WINDOW_MINUTES', 60),

];
