<?php

use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function seedPrescriptionAppointment(): array
{
    $doctor = Doctor::factory()->create([
        'registration_number' => 'LIC-998877',
        'name' => 'Dr. Ali Zairi',
    ]);
    $patient = User::factory()->create([
        'birth_date' => '1990-05-12',
    ]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'status' => 'in_process',
        'appointment_number' => 'APT-10001',
        'patient_name' => 'Sara Patient',
        'patient_phone' => '966500000001',
        'patient_email' => 'sara@example.com',
        'appointment_date' => now()->toDateString(),
        'start_time' => '10:00:00',
    ]);

    Diagnosis::query()->create([
        'appointment_id' => $appointment->id,
        'diagnosis_name' => 'Anxiety',
        'doctor_notes' => 'Follow up in two weeks.',
    ]);

    $appointment->medications()->create([
        'name' => 'Prozac',
        'dosage' => '20 mg',
        'usage' => '1 capsule daily',
        'frequency' => '1',
        'duration' => '30',
        'duration_measurement' => 'days',
        'instructions' => 'Take after breakfast.',
    ]);

    return [$doctor, $patient, $appointment];
}

test('doctor can download prescription pdf when medications exist', function () {
    [$doctor, , $appointment] = seedPrescriptionAppointment();

    $response = $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.appointments.prescription.pdf', $appointment));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('prescription-');
});

test('patient can preview own prescription pdf in browser', function () {
    [, $patient, $appointment] = seedPrescriptionAppointment();

    $response = $this->actingAs($patient)
        ->get(route('patient.prescriptions.preview', $appointment));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('inline');
});

test('patient can download own prescription pdf', function () {
    [, $patient, $appointment] = seedPrescriptionAppointment();

    $response = $this->actingAs($patient)
        ->get(route('patient.prescriptions.pdf', $appointment));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('attachment');
});

test('doctor cannot download prescription pdf without medications', function () {
    $doctor = Doctor::factory()->create();
    $patient = User::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'status' => 'in_process',
    ]);

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.appointments.prescription.pdf', $appointment))
        ->assertNotFound();
});

test('doctor cannot download another doctors prescription pdf', function () {
    [, , $appointment] = seedPrescriptionAppointment();
    $otherDoctor = Doctor::factory()->create();

    $this->actingAs($otherDoctor, 'doctor')
        ->get(route('doctor.appointments.prescription.pdf', $appointment))
        ->assertForbidden();
});

test('patient cannot download another patients prescription pdf', function () {
    [, , $appointment] = seedPrescriptionAppointment();
    $otherPatient = User::factory()->create();

    $this->actingAs($otherPatient)
        ->get(route('patient.prescriptions.pdf', $appointment))
        ->assertForbidden();
});

test('prescription page shows download button when medications exist', function () {
    app()->setLocale('en');

    [$doctor, , $appointment] = seedPrescriptionAppointment();

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.prescription', ['appointment' => $appointment])
        ->assertSee(__('doctor.prescription_form.download_pdf'), false)
        ->assertSee(route('doctor.appointments.prescription.pdf', $appointment), false);
});

test('patient medications page shows preview and download actions', function () {
    app()->setLocale('en');

    [, $patient, $appointment] = seedPrescriptionAppointment();

    Livewire::actingAs($patient)
        ->test('pages::patient.medications')
        ->assertSee(__('patient.medications_page.preview_pdf'), false)
        ->assertSee(__('patient.medications_page.download_pdf'), false)
        ->assertSee(route('patient.prescriptions.preview', $appointment), false)
        ->assertSee(route('patient.prescriptions.pdf', $appointment), false);
});
