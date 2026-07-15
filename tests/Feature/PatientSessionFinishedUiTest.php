<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('patient conversation shows finished session ui when session time has expired', function () {
    config(['appointments.relaxed_session_limits' => false]);

    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();
    $appointment = Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'status' => 'in_process',
        'actual_start_at' => now()->subHour(),
        'extend_at' => now()->subMinute(),
    ]);

    $html = Livewire::actingAs($user)
        ->test('pages::patient.appointment.conversation', ['appointment' => $appointment])
        ->assertSee(__('patient.appointments.session_finished'), false)
        ->assertSee('data-test="patient-consultation-session-ended"', false)
        ->assertDontSee('data-test="patient-consultation-inline-video"', false)
        ->assertDontSee('id="patient-timer-session-remaining-mobile"', false)
        ->assertDontSee('id="patient-session-countdown-mobile"', false)
        ->assertDontSee(__('patient.appointments.luxury.ends_in'), false)
        ->assertDontSee('id="patient-call-started-chip"', false)
        ->assertDontSee('id="patient-waiting-for-call-chip"', false)
        ->html();

    expect($html)
        ->toMatch('/id="patient-session-live-banner"[^>]*\bhidden\b/')
        ->not->toMatch('/id="patient-live-now-badge"/')
        ->not->toMatch('/id="patient-chat-live-now-badge"/')
        ->not->toMatch('/id="patient-session-finished-chip"[^>]*\bhidden\b/')
        ->not->toMatch('/id="patient-session-finished-chip-desktop"[^>]*\bhidden\b/')
        ->not->toContain('id="patient-call-started-chip"');
});

test('patient conversation still shows waiting chip while session is active', function () {
    config(['appointments.relaxed_session_limits' => false]);

    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();
    $appointment = Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'status' => 'in_process',
        'actual_start_at' => now(),
        'extend_at' => now()->addMinutes(30),
    ]);

    $html = Livewire::actingAs($user)
        ->test('pages::patient.appointment.conversation', ['appointment' => $appointment])
        ->assertSee(__('patient.appointments.waiting_for_specialist_call'), false)
        ->assertSee(__('patient.appointments.luxury.ends_in'), false)
        ->assertSee('data-test="patient-consultation-inline-video"', false)
        ->assertDontSee('data-test="patient-consultation-session-ended"', false)
        ->assertSee('id="patient-timer-session-remaining-mobile"', false)
        ->html();

    expect($html)
        ->toMatch('/id="patient-call-started-chip"[^>]*\bhidden\b/')
        ->not->toMatch('/id="patient-call-started-chip"[^>]*\binline-flex\b/');
});

test('patient conversation hides call chips when session has not started', function () {
    config(['appointments.relaxed_session_limits' => false]);

    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();
    $appointment = Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'status' => 'new',
        'actual_start_at' => null,
        'extend_at' => null,
    ]);

    $html = Livewire::actingAs($user)
        ->test('pages::patient.appointment.conversation', ['appointment' => $appointment])
        ->assertDontSee('id="patient-call-started-chip"', false)
        ->assertDontSee('id="patient-waiting-for-call-chip"', false)
        ->assertDontSee('id="patient-call-started-chip-desktop"', false)
        ->assertDontSee('id="patient-waiting-for-call-chip-desktop"', false)
        ->html();

    // Overlay title may still contain this string while hidden; chips must not be in the DOM.
    expect($html)
        ->toMatch('/id="patient-agora-overlay"[^>]*\bhidden\b/')
        ->not->toContain('id="patient-call-started-chip"')
        ->not->toContain('id="patient-waiting-for-call-chip"');
});
