<?php

use App\Models\Appointment;
use App\Models\AppointmentRefundRequest;
use App\Models\Doctor;
use App\Models\Notification;
use App\Models\User;
use App\Services\AppointmentRefundRequestNotifier;
use App\Services\AppointmentWalletService;
use App\Services\PatientMissedAppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('full refund processing credits patient wallet and closes appointment', function () {
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
        'requested_amount' => 200,
    ]);

    app(AppointmentWalletService::class)->refundAmountToPatient($appointment->fresh(), 200, 'appointment_refund');

    $appointment->refresh();
    $request->update([
        'status' => 'processed',
        'resolution_type' => 'full',
        'processed_amount' => 200,
        'processed_at' => now(),
    ]);
    $appointment->update([
        'status' => 'cancelled',
        'cancel_status' => 'patient_refunded',
    ]);

    expect((float) $patient->fresh()->balanceFloat)->toBe(200.0)
        ->and((float) $doctor->fresh()->balanceFloat)->toBe(0.0)
        ->and($appointment->fresh()->status)->toBe('cancelled')
        ->and($request->fresh()->status)->toBe('processed');
});

test('partial refund processing credits requested amount to patient wallet', function () {
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

    app(AppointmentWalletService::class)->refundAmountToPatient($appointment->fresh(), 50, 'appointment_refund_partial');

    expect((float) $patient->fresh()->balanceFloat)->toBe(50.0)
        ->and((float) $doctor->fresh()->balanceFloat)->toBe(105.0);
});

test('submitting a refund request notifies patient and doctor', function () {
    app()->setLocale('en');

    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $patient = User::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'status' => 'not_attended',
        'cancel_status' => 'doctor_missed',
        'patient_name' => $patient->name,
        'total' => 150,
        'doctor_share' => 105,
        'mashora_share' => 45,
        'wallet_amount' => 150,
    ]);

    app(PatientMissedAppointmentService::class)->requestRefund(
        $patient,
        $appointment,
        'service_not_provided',
    );

    expect(Notification::query()
        ->where('userable_type', User::class)
        ->where('userable_id', $patient->id)
        ->where('type', 'refund_request_submitted')
        ->exists())->toBeTrue()
        ->and(Notification::query()
            ->where('userable_type', Doctor::class)
            ->where('userable_id', $doctor->id)
            ->where('type', 'refund_request_submitted')
            ->exists())->toBeTrue();
});

test('processing a refund request notifies patient and doctor', function () {
    app()->setLocale('en');

    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $patient = User::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'status' => 'not_attended',
        'cancel_status' => 'doctor_missed',
        'patient_name' => $patient->name,
        'total' => 200,
        'doctor_share' => 140,
        'mashora_share' => 60,
        'wallet_amount' => 200,
    ]);

    $request = AppointmentRefundRequest::query()->create([
        'appointment_id' => $appointment->id,
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'reason_key' => 'service_not_provided',
        'status' => 'processed',
        'resolution_type' => 'full',
        'requested_amount' => 200,
        'processed_amount' => 200,
        'processed_at' => now(),
    ]);

    app(AppointmentRefundRequestNotifier::class)->notifyProcessed($request);

    expect(Notification::query()
        ->where('userable_type', User::class)
        ->where('userable_id', $patient->id)
        ->where('type', 'refund_request_processed')
        ->exists())->toBeTrue()
        ->and(Notification::query()
            ->where('userable_type', Doctor::class)
            ->where('userable_id', $doctor->id)
            ->where('type', 'refund_request_processed')
            ->exists())->toBeTrue();
});
