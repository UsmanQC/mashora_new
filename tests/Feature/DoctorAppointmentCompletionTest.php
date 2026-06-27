<?php

use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\Doctor;
use App\Models\Medication;
use App\Models\User;
use App\Services\AppointmentCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('conversation page can mark in process appointment completed', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'in_process',
        'prescription_not_needed' => true,
        'appointment_date' => now()->toDateString(),
        'scheduled_at' => now(),
    ]);

    Diagnosis::create([
        'appointment_id' => $appointment->id,
        'diagnosis_name' => 'Example diagnosis',
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.conversation', ['appointment' => $appointment])
        ->call('requestCompleteAppointment')
        ->assertSet('showCompleteAppointmentModal', true)
        ->call('confirmCompleteAppointment')
        ->assertRedirect(route('doctor.appointments.follow-up', $appointment));

    expect($appointment->fresh()->status)->toBe('completed');
});

test('appointment completion service requires diagnosis and prescription when applicable', function () {
    $appointment = Appointment::factory()->create([
        'status' => 'in_process',
        'prescription_not_needed' => false,
    ]);

    $service = app(AppointmentCompletionService::class);

    expect($service->attemptCompletion($appointment->fresh()))
        ->toBe(AppointmentCompletionService::MISSING_DIAGNOSIS);

    Diagnosis::create([
        'appointment_id' => $appointment->id,
        'diagnosis_name' => 'Test',
    ]);

    expect($service->attemptCompletion($appointment->fresh()))
        ->toBe(AppointmentCompletionService::MISSING_PRESCRIPTION);

    Medication::create([
        'appointment_id' => $appointment->id,
        'name' => 'Med',
    ]);

    expect($service->attemptCompletion($appointment->fresh()))
        ->toBe(AppointmentCompletionService::COMPLETED);
});
