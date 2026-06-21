<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use App\Services\AppointmentSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('doctor can start session for new or rescheduled appointments', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'rescheduled',
        'duration' => 30,
        'appointment_date' => now()->toDateString(),
        'scheduled_at' => now(),
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.conversation', ['appointment' => $appointment])
        ->call('startSession');

    $fresh = $appointment->fresh();

    expect($fresh->status)->toBe('in_process')
        ->and($fresh->actual_start_at)->not->toBeNull()
        ->and($fresh->extend_at)->not->toBeNull();
});

test('patient cannot start session through realtime notify endpoint', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
    ]);

    $this->actingAs($user)
        ->postJson(route('patient.appointments.realtime.notify-call', $appointment))
        ->assertForbidden();

    expect($appointment->fresh()->status)->toBe('new');
});

test('patient cannot fetch agora token before doctor starts session', function () {
    config([
        'agora.AGORA_APP_ID' => 'test-app-id',
        'agora.AGORA_APP_CERTIFICATE' => str_repeat('a', 32),
    ]);

    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
    ]);

    $this->actingAs($user)
        ->postJson(route('patient.appointments.realtime.agora-token', $appointment))
        ->assertForbidden();
});

test('patient appointments show waiting state before doctor starts session', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create(['name' => 'Test Doctor']);

    Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'status' => 'new',
        'appointment_date' => now()->addDay()->toDateString(),
        'start_time' => '10:00:00',
        'patient_name' => 'Patient Test',
    ]);

    Livewire::actingAs($user)
        ->test('pages::patient.appointments')
        ->assertSee(__('patient.appointments.waiting_for_doctor'), false);
});

test('patient appointments show join session after doctor starts', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();

    $appointment = Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'status' => 'in_process',
        'actual_start_at' => now(),
    ]);

    $this->actingAs($user)->get(route('patient.appointments'))
        ->assertSuccessful()
        ->assertSee(__('patient.appointments.join_session'), false)
        ->assertSee(route('patient.appointments.conversation', ['appointment' => $appointment->id]), false);
});

test('appointment session service rejects patient-side start attempts', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
    ]);

    $service = app(AppointmentSessionService::class);

    expect($service->canPatientJoin($appointment))->toBeFalse()
        ->and($service->canDoctorStart($appointment))->toBeTrue();
});
