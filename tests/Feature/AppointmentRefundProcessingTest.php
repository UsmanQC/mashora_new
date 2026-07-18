<?php

use App\Models\Appointment;
use App\Models\AppointmentRefundRequest;
use App\Models\Doctor;
use App\Models\User;
use App\Services\AppointmentRefundProcessingService;
use App\Services\PatientMissedAppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('processing service credits wallet refunds', function () {
    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $patient = User::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'status' => 'not_attended',
        'cancel_status' => 'doctor_missed',
        'total' => 200,
        'doctor_share' => 140,
        'mashora_share' => 60,
        'wallet_amount' => 200,
    ]);

    $doctor->depositFloat(140.00);

    $request = AppointmentRefundRequest::query()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'reason_key' => 'service_not_provided',
        'status' => 'approved',
        'refund_destination' => 'wallet',
        'requested_amount' => 200,
    ]);

    app(AppointmentRefundProcessingService::class)->process(
        $request,
        $appointment->fresh(),
        200,
        AppointmentRefundProcessingService::DESTINATION_WALLET,
    );

    expect((float) $patient->fresh()->balanceFloat)->toBe(200.0)
        ->and($appointment->fresh()->status)->toBe('cancelled')
        ->and($appointment->fresh()->cancel_status)->toBe('patient_refunded');
});

test('processing service sends payment account refunds through myfatoorah', function () {
    config([
        'myfatoorah.api_key' => 'test-key',
        'myfatoorah.is_test' => true,
    ]);

    Http::fake([
        'https://apitest.myfatoorah.com/v2/MakeRefund' => Http::response([
            'IsSuccess' => true,
            'Data' => ['RefundInvoiceId' => 'RF-2001'],
        ], 200),
    ]);

    $doctor = Doctor::factory()->create();
    $patient = User::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'payment_invoice_id' => 'INV-9001',
        'total' => 120,
        'doctor_share' => 84,
        'mashora_share' => 36,
    ]);

    $request = AppointmentRefundRequest::query()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'reason_key' => 'service_not_provided',
        'status' => 'approved',
        'refund_destination' => 'payment_account',
        'requested_amount' => 120,
    ]);

    $result = app(AppointmentRefundProcessingService::class)->process(
        $request,
        $appointment->fresh(),
        120,
        AppointmentRefundProcessingService::DESTINATION_PAYMENT_ACCOUNT,
    );

    expect($result['destination'])->toBe('payment_account')
        ->and($result['refund_invoice_id'])->toBe('RF-2001')
        ->and($appointment->fresh()->refund_payment_invoice_id)->toBe('RF-2001')
        ->and((float) $patient->fresh()->balanceFloat)->toBe(0.0);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://apitest.myfatoorah.com/v2/MakeRefund');
});

test('patient can request refund to payment account when invoice exists', function () {
    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $patient = User::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'status' => 'not_attended',
        'cancel_status' => 'doctor_missed',
        'payment_invoice_id' => 'INV-123',
        'total' => 150,
    ]);

    $request = app(PatientMissedAppointmentService::class)->requestRefund(
        $patient,
        $appointment,
        'service_not_provided',
        null,
        AppointmentRefundRequest::REFUND_DESTINATION_PAYMENT_ACCOUNT,
    );

    expect($request->refund_destination)->toBe('payment_account')
        ->and($request->status)->toBe('pending_review')
        ->and((float) $request->requested_amount)->toBe(150.0);
});

test('processing service allows partial wallet refunds up to amount paid', function () {
    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $patient = User::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'status' => 'not_attended',
        'cancel_status' => 'doctor_missed',
        'total' => 200,
        'doctor_share' => 140,
        'mashora_share' => 60,
        'wallet_amount' => 200,
    ]);

    $doctor->depositFloat(140.00);

    $request = AppointmentRefundRequest::query()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'reason_key' => 'service_not_provided',
        'status' => 'approved',
        'refund_destination' => 'wallet',
        'requested_amount' => 200,
    ]);

    app(AppointmentRefundProcessingService::class)->process(
        $request,
        $appointment->fresh(),
        75,
        AppointmentRefundProcessingService::DESTINATION_WALLET,
        true,
    );

    expect((float) $patient->fresh()->balanceFloat)->toBe(75.0);
});

test('processing service rejects refunds above amount paid', function () {
    $doctor = Doctor::factory()->create();
    $patient = User::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'payment_invoice_id' => 'INV-9001',
        'total' => 120,
        'doctor_share' => 84,
        'mashora_share' => 36,
    ]);

    $request = AppointmentRefundRequest::query()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'reason_key' => 'service_not_provided',
        'status' => 'approved',
        'refund_destination' => 'wallet',
        'requested_amount' => 120,
    ]);

    app(AppointmentRefundProcessingService::class)->process(
        $request,
        $appointment->fresh(),
        121,
        AppointmentRefundProcessingService::DESTINATION_WALLET,
    );
})->throws(ValidationException::class);

test('payment account refunds cannot exceed gateway portion when wallet was used', function () {
    config([
        'myfatoorah.api_key' => 'test-key',
        'myfatoorah.is_test' => true,
    ]);

    Http::fake([
        'https://apitest.myfatoorah.com/v2/MakeRefund' => Http::response([
            'IsSuccess' => true,
            'Data' => ['RefundInvoiceId' => 'RF-2002'],
        ], 200),
    ]);

    $doctor = Doctor::factory()->create();
    $patient = User::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'payment_invoice_id' => 'INV-9002',
        'total' => 120,
        'wallet_amount' => 40,
        'doctor_share' => 84,
        'mashora_share' => 36,
    ]);

    $processing = app(AppointmentRefundProcessingService::class);

    expect($processing->amountPaid($appointment))->toBe(120.0)
        ->and($processing->gatewayAmountPaid($appointment))->toBe(80.0)
        ->and($processing->maximumRefundableAmount($appointment, AppointmentRefundProcessingService::DESTINATION_PAYMENT_ACCOUNT))->toBe(80.0);

    $request = AppointmentRefundRequest::query()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'reason_key' => 'service_not_provided',
        'status' => 'approved',
        'refund_destination' => 'payment_account',
        'requested_amount' => 120,
    ]);

    $processing->process(
        $request,
        $appointment->fresh(),
        80,
        AppointmentRefundProcessingService::DESTINATION_PAYMENT_ACCOUNT,
    );

    expect($appointment->fresh()->refund_payment_invoice_id)->toBe('RF-2002');
});

test('payment account refundable amount uses paid total when wallet_amount equals total but invoice exists', function () {
    $doctor = Doctor::factory()->create();
    $patient = User::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'status' => 'not_attended',
        'cancel_status' => 'doctor_missed',
        'payment_invoice_id' => 'INV-CARD-1',
        'total' => 150,
        'wallet_amount' => 150,
        'doctor_share' => 105,
    ]);

    $processing = app(AppointmentRefundProcessingService::class);

    expect($processing->gatewayAmountPaid($appointment))->toBe(150.0)
        ->and($processing->maximumRefundableAmount(
            $appointment,
            AppointmentRefundProcessingService::DESTINATION_PAYMENT_ACCOUNT,
        ))->toBe(150.0);

    $request = app(PatientMissedAppointmentService::class)->requestRefund(
        $patient,
        $appointment,
        'service_not_provided',
        null,
        AppointmentRefundRequest::REFUND_DESTINATION_PAYMENT_ACCOUNT,
    );

    expect((float) $request->requested_amount)->toBe(150.0);
});
