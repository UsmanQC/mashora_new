<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('doctor can download invoice pdf with appointment details', function () {
    $doctor = Doctor::factory()->create();
    $patient = User::factory()->create();

    $invoice = Invoice::query()->create([
        'reference' => 'MSH-'.$doctor->id.'-2026/06',
        'doctor_id' => $doctor->id,
        'issue_date' => '2026-07-01',
        'from_date' => '2026-06-01',
        'to_date' => '2026-06-30',
        'total_amount' => 250.00,
        'doctor_share' => 200.00,
        'mashora_share' => 50.00,
        'payment_status' => 'unpaid',
    ]);

    Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'invoice_id' => $invoice->id,
        'appointment_date' => '2026-06-12',
        'start_time' => '10:00:00',
        'patient_name' => 'Sara Patient',
        'patient_phone' => '966500000001',
        'patient_email' => 'sara@example.com',
        'status' => 'completed',
        'total' => 250.00,
        'doctor_share' => 200.00,
        'mashora_share' => 50.00,
    ]);

    $response = $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.settings.invoices.pdf', $invoice));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('doctor cannot download another doctors invoice pdf', function () {
    $doctor = Doctor::factory()->create();
    $otherDoctor = Doctor::factory()->create();

    $invoice = Invoice::query()->create([
        'reference' => 'MSH-other',
        'doctor_id' => $otherDoctor->id,
        'issue_date' => '2026-07-01',
        'from_date' => '2026-06-01',
        'to_date' => '2026-06-30',
        'total_amount' => 100.00,
        'doctor_share' => 80.00,
        'mashora_share' => 20.00,
        'payment_status' => 'unpaid',
    ]);

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.settings.invoices.pdf', $invoice))
        ->assertForbidden();
});

test('doctor can view invoice detail page with sessions', function () {
    app()->setLocale('en');

    $doctor = Doctor::factory()->create();
    $patient = User::factory()->create();

    $invoice = Invoice::query()->create([
        'reference' => 'MSH-'.$doctor->id.'-2026/06',
        'doctor_id' => $doctor->id,
        'issue_date' => '2026-07-01',
        'from_date' => '2026-06-01',
        'to_date' => '2026-06-30',
        'total_amount' => 120.00,
        'doctor_share' => 90.00,
        'mashora_share' => 30.00,
        'payment_status' => 'paid',
        'paid_at' => now(),
    ]);

    Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'invoice_id' => $invoice->id,
        'patient_name' => 'Ahmed Ali',
        'patient_phone' => '966500000002',
        'status' => 'completed',
        'total' => 120.00,
        'doctor_share' => 90.00,
    ]);

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.settings.invoices.show', $invoice))
        ->assertSuccessful()
        ->assertSee('Ahmed Ali', false)
        ->assertSee(__('doctor.invoices.status_paid'), false);
});
