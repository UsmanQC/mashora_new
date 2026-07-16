<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('doctor mobile in-call overlay uses soft video sync so polls do not restart agora tracks', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true]);
    $appointment = Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'status' => 'in_process',
        'actual_start_at' => now(),
        'extend_at' => now()->addMinutes(30),
    ]);

    $html = $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.appointments.conversation', $appointment))
        ->assertOk()
        ->assertSee('data-test="doctor-consultation-inline-video"', false)
        ->assertSee('id="doctor-consultation-call-controls-wrap"', false)
        ->getContent();

    expect($html)
        ->toContain('shouldReplayVideoTracks')
        ->toContain('forceReplay')
        ->toContain('isInlineCallLive')
        ->toContain("el?.id === 'doctor-consultation-inline-video'")
        ->not->toMatch('/id="doctor-consultation-call-controls-wrap"[^>]*\bhidden flex\b/');
});

test('patient mobile in-call overlay uses soft video sync so polls do not restart agora tracks', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();
    $appointment = Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'status' => 'in_process',
        'actual_start_at' => now(),
        'extend_at' => now()->addMinutes(30),
    ]);

    $html = $this->actingAs($user)
        ->get(route('patient.appointments.conversation', $appointment))
        ->assertOk()
        ->assertSee('id="patient-consultation-call-controls-wrap"', false)
        ->getContent();

    expect($html)
        ->toContain('shouldReplayVideoTracks')
        ->toContain('forceReplay')
        ->toContain('isInlineCallLive')
        ->not->toMatch('/id="patient-consultation-call-controls-wrap"[^>]*\bhidden flex\b/');
});
