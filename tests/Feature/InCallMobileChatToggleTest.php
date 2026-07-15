<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('patient mobile consultation only closes chat when a call first becomes active', function () {
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
        ->assertSee('data-test="patient-consultation-chat-toggle"', false)
        ->html();

    expect($html)
        ->toContain('if (! callActive) { chatOpen = false } callActive = true')
        ->not->toContain('patient-consultation-call-active.window="callActive = true; chatOpen = false"');
});

test('doctor mobile consultation only closes chat when a call first becomes active', function () {
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
        ->assertSee('data-test="doctor-consultation-chat-toggle"', false)
        ->getContent();

    expect($html)
        ->toContain('if (! callActive) { chatOpen = false } callActive = true')
        ->not->toContain('consultation-call-active.window="callActive = true; chatOpen = false"');
});
