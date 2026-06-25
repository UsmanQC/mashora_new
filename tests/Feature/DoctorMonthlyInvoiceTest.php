<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\User;
use App\Services\AppointmentWalletService;
use App\Services\DoctorMonthlyInvoiceService;
use App\Services\DoctorWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('monthly invoice command generates invoice for previous month completed appointments', function () {
    Carbon::setTestNow('2026-07-01 01:00:00');

    $doctor = Doctor::factory()->create(['commission' => 25]);
    $patient = User::factory()->create();

    $juneAppointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'appointment_date' => '2026-06-15',
        'status' => 'completed',
        'total' => 200.00,
        'doctor_share' => 150.00,
        'mashora_share' => 50.00,
    ]);

    $julyAppointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'appointment_date' => '2026-07-02',
        'status' => 'completed',
        'total' => 100.00,
        'doctor_share' => 75.00,
        'mashora_share' => 25.00,
    ]);

    $result = app(DoctorMonthlyInvoiceService::class)->generateForIssueDate(Carbon::parse('2026-07-01'));

    expect($result['created'])->toBe(1);

    $invoice = Invoice::query()->where('doctor_id', $doctor->id)->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->reference)->toBe('MSH-'.$doctor->id.'-2026/06')
        ->and($invoice->issue_date?->toDateString())->toBe('2026-07-01')
        ->and($invoice->from_date?->toDateString())->toBe('2026-06-01')
        ->and($invoice->to_date?->toDateString())->toBe('2026-06-30')
        ->and((float) $invoice->total_amount)->toBe(200.0)
        ->and((float) $invoice->doctor_share)->toBe(150.0)
        ->and((float) $invoice->mashora_share)->toBe(50.0)
        ->and($invoice->payment_status)->toBe('unpaid');

    $juneAppointment->refresh();
    $julyAppointment->refresh();

    expect($juneAppointment->invoice_id)->toBe($invoice->id)
        ->and($julyAppointment->invoice_id)->toBeNull();

    Carbon::setTestNow();
});

test('monthly invoice generation is idempotent for the same period', function () {
    Carbon::setTestNow('2026-07-01 01:00:00');

    $doctor = Doctor::factory()->create();
    $patient = User::factory()->create();

    Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'appointment_date' => '2026-06-10',
        'status' => 'completed',
        'total' => 120.00,
        'doctor_share' => 90.00,
        'mashora_share' => 30.00,
    ]);

    $service = app(DoctorMonthlyInvoiceService::class);

    $first = $service->generateForIssueDate(Carbon::parse('2026-07-01'));
    $second = $service->generateForIssueDate(Carbon::parse('2026-07-01'));

    expect($first['created'])->toBe(1)
        ->and($second['created'])->toBe(0)
        ->and(Invoice::query()->where('doctor_id', $doctor->id)->count())->toBe(1);

    Carbon::setTestNow();
});

test('marking invoice as paid settles doctor wallet and updates status', function () {
    $doctor = Doctor::factory()->create(['commission' => 0]);
    $patient = User::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'status' => 'completed',
        'total' => 300.00,
    ]);

    app(AppointmentWalletService::class)->creditDoctorEarning($appointment->fresh());

    $invoice = Invoice::query()->create([
        'reference' => 'MSH-'.$doctor->id.'-2026/06',
        'doctor_id' => $doctor->id,
        'issue_date' => '2026-07-01',
        'from_date' => '2026-06-01',
        'to_date' => '2026-06-30',
        'total_amount' => 300.00,
        'doctor_share' => 300.00,
        'mashora_share' => 0.00,
        'payment_status' => 'unpaid',
    ]);

    $appointment->forceFill(['invoice_id' => $invoice->id])->save();

    expect(app(DoctorWalletService::class)->balance($doctor->fresh()))->toBe(300.0);

    $paid = app(DoctorWalletService::class)->settleInvoicePayout($invoice->fresh());

    expect($paid->payment_status)->toBe('paid')
        ->and($paid->paid_at)->not->toBeNull()
        ->and($paid->wallet_settled_at)->not->toBeNull()
        ->and(app(DoctorWalletService::class)->balance($doctor->fresh()))->toBe(0.0);
});

test('artisan command generates monthly invoices', function () {
    Carbon::setTestNow('2026-07-01 01:00:00');

    $doctor = Doctor::factory()->create();
    $patient = User::factory()->create();

    Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'appointment_date' => '2026-06-20',
        'status' => 'completed',
        'total' => 80.00,
        'doctor_share' => 60.00,
        'mashora_share' => 20.00,
    ]);

    $this->artisan('invoices:generate-monthly')
        ->assertSuccessful();

    expect(Invoice::query()->where('doctor_id', $doctor->id)->exists())->toBeTrue();

    Carbon::setTestNow();
});
