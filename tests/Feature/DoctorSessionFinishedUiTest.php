<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('doctor conversation hides ends-in countdown when session time has expired', function () {
    config(['appointments.relaxed_session_limits' => false]);

    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true]);
    $appointment = Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'status' => 'in_process',
        'actual_start_at' => now()->subHour(),
        'extend_at' => now()->subMinute(),
    ]);

    $html = $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.appointments.conversation', $appointment))
        ->assertOk()
        ->assertSee(__('doctor.consultation.session_finished'), false)
        ->assertSee('data-test="doctor-session-finished-chip"', false)
        ->assertSee('data-test="doctor-consultation-session-ended"', false)
        ->assertDontSee('data-test="doctor-session-countdown-mobile"', false)
        ->assertDontSee('id="timer-session-remaining-mobile"', false)
        ->assertDontSee('id="wrap-session-remaining"', false)
        ->assertDontSee(__('doctor.consultation.ends_in'), false)
        ->getContent();

    expect($html)
        ->not->toContain('id="timer-session-remaining-mobile"')
        ->toContain('data-test="doctor-session-finished-chip"');
});

test('doctor conversation still shows ends-in countdown while session is active', function () {
    config(['appointments.relaxed_session_limits' => false]);

    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true]);
    $appointment = Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'status' => 'in_process',
        'actual_start_at' => now(),
        'extend_at' => now()->addMinutes(30),
    ]);

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.appointments.conversation', $appointment))
        ->assertOk()
        ->assertSee(__('doctor.consultation.ends_in'), false)
        ->assertSee('id="timer-session-remaining-mobile"', false)
        ->assertSee('data-test="doctor-session-countdown-mobile"', false)
        ->assertDontSee('data-test="doctor-consultation-session-ended"', false);
});
