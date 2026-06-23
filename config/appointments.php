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

];
