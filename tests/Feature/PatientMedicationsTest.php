<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Medication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('patient medications page shows empty state when no prescriptions exist', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['profile_completed' => true]);

    $this->actingAs($user)
        ->get(route('patient.medications'))
        ->assertSuccessful()
        ->assertSee('data-test="patient-luxury-medications"', false)
        ->assertSee('data-test="patient-medications-header"', false)
        ->assertSee(__('patient.medications_page.empty_title'), false);
});

test('patient medications page lists prescriptions from their appointments', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create(['status' => 'approved', 'name' => 'Sara Al-Qahtani']);

    $appointment = Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'status' => 'completed',
        'appointment_date' => '2026-06-20',
        'start_time' => '10:00:00',
        'scheduled_at' => '2026-06-20 10:00:00',
    ]);

    Medication::create([
        'appointment_id' => $appointment->id,
        'name' => 'Sertraline 50mg',
        'dosage' => '1 tablet',
        'usage' => 'After breakfast',
        'frequency' => 'Once daily',
        'duration' => '30',
        'duration_measurement' => 'days',
        'instructions' => 'Take with water.',
    ]);

    $otherUser = User::factory()->create();
    $otherAppointment = Appointment::factory()->create([
        'user_id' => $otherUser->id,
        'doctor_id' => $doctor->id,
        'status' => 'completed',
    ]);

    Medication::create([
        'appointment_id' => $otherAppointment->id,
        'name' => 'Hidden medication',
    ]);

    $this->actingAs($user)
        ->get(route('patient.medications'))
        ->assertSuccessful()
        ->assertSee('data-test="patient-prescription-card"', false)
        ->assertSee('Sertraline 50mg', false)
        ->assertSee('After breakfast', false)
        ->assertSee('Once daily', false)
        ->assertSee('Take with water.', false)
        ->assertSee('Sara Al-Qahtani', false)
        ->assertDontSee('Hidden medication', false);
});
