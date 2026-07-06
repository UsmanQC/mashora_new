<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\User;
use App\Services\AppointmentWalletService;
use App\Services\DoctorWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('doctor wallet monthly income chart returns six months of net income', function (): void {
    $doctor = Doctor::factory()->create(['commission' => 30]);
    $wallet = app(AppointmentWalletService::class);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => User::factory()->create()->id,
        'total' => 100.00,
    ]);

    $wallet->creditDoctorEarning($appointment->fresh());

    $chart = app(DoctorWalletService::class)->monthlyIncomeChart($doctor->fresh());

    expect($chart)->toHaveCount(6)
        ->and(collect($chart)->last()['is_current'])->toBeTrue()
        ->and(collect($chart)->last()['income'])->toBeGreaterThan(0);
});

test('doctor wallet pending payout sums unpaid invoice doctor shares', function (): void {
    $doctor = Doctor::factory()->create();

    Invoice::factory()->create([
        'doctor_id' => $doctor->id,
        'doctor_share' => 150.00,
        'payment_status' => 'unpaid',
    ]);

    expect(app(DoctorWalletService::class)->pendingPayoutBalance($doctor->fresh()))->toBe(150.0);
});

test('doctor wallet monthly summary reports income refunds sessions and paid out', function (): void {
    $doctor = Doctor::factory()->create(['commission' => 30]);
    $patient = User::factory()->create();
    $wallet = app(AppointmentWalletService::class);

    $completedAppointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'total' => 20.00,
    ]);

    $refundedAppointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'total' => 20.00,
    ]);

    $wallet->creditDoctorEarning($completedAppointment->fresh());
    $wallet->creditDoctorEarning($refundedAppointment->fresh());
    $wallet->refundToPatient($refundedAppointment->fresh());

    $summary = app(DoctorWalletService::class)->monthlySummary($doctor->fresh());

    expect($summary['income'])->toBe(14.0)
        ->and($summary['refunded'])->toBe(14.0)
        ->and($summary['refunded_count'])->toBe(1)
        ->and($summary['paid_sessions'])->toBe(2)
        ->and($summary['paid_out'])->toBe(0.0);
});
