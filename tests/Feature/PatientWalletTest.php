<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Notification;
use App\Models\TemporaryAppointment;
use App\Models\User;
use App\Services\AppointmentWalletService;
use App\Services\PatientPaymentCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('doctor cancel refunds appointment total to patient wallet', function (): void {
    $patient = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create(['profile_completed' => true, 'commission' => 30]);

    $appointment = Appointment::factory()->create([
        'user_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'total' => 150.00,
        'doctor_share' => 105.00,
        'mashora_share' => 45.00,
        'status' => 'new',
    ]);

    $doctor->depositFloat(105.00);

    app(AppointmentWalletService::class)->refundToPatient($appointment->fresh());

    $appointment->forceFill(['status' => 'cancelled', 'cancel_status' => 'doctor'])->save();

    $appointment->refresh();

    expect((float) $patient->fresh()->balanceFloat)->toBe(150.0)
        ->and((float) $doctor->fresh()->balanceFloat)->toBe(0.0)
        ->and((float) $appointment->doctor_share)->toBe(0.0)
        ->and((float) $appointment->mashora_share)->toBe(0.0);
});

test('doctor cancel debits only doctor share from doctor wallet on refund', function (): void {
    $patient = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create(['profile_completed' => true, 'commission' => 30]);

    $appointment = Appointment::factory()->create([
        'user_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'total' => 112.00,
        'doctor_share' => 85.40,
        'mashora_share' => 26.60,
        'status' => 'new',
    ]);

    $doctor->depositFloat(85.40);

    app(AppointmentWalletService::class)->refundToPatient($appointment->fresh());

    expect((float) $patient->fresh()->balanceFloat)->toBe(112.0)
        ->and((float) $doctor->fresh()->balanceFloat)->toBe(0.0);
});

test('doctor cancel refund is idempotent and does not double credit patient', function (): void {
    $patient = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create(['profile_completed' => true, 'commission' => 30]);

    $appointment = Appointment::factory()->create([
        'user_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'total' => 150.00,
        'doctor_share' => 105.00,
        'mashora_share' => 45.00,
        'status' => 'new',
    ]);

    $doctor->depositFloat(105.00);

    $wallet = app(AppointmentWalletService::class);
    $wallet->refundToPatient($appointment->fresh());
    $wallet->refundToPatient($appointment->fresh());

    expect((float) $patient->fresh()->balanceFloat)->toBe(150.0)
        ->and((float) $doctor->fresh()->balanceFloat)->toBe(0.0);
});

test('unpaid appointment is not refunded when doctor cancels', function (): void {
    $patient = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create(['profile_completed' => true, 'commission' => 30]);

    $appointment = Appointment::factory()->create([
        'user_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'total' => 150.00,
        'doctor_share' => 0,
        'mashora_share' => 0,
        'status' => 'new',
    ]);

    app(AppointmentWalletService::class)->refundToPatient($appointment->fresh());

    expect((float) $patient->fresh()->balanceFloat)->toBe(0.0);
});

test('checkout amount due subtracts wallet balance', function (): void {
    $temp = new TemporaryAppointment([
        'total' => 200.00,
        'wallet_amount' => 75.00,
    ]);

    expect(PatientPaymentCompletionService::amountDue($temp))->toBe(125.0);
});

test('wallet only booking refunded in full when doctor cancels via portal', function (): void {
    $patient = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create(['profile_completed' => true, 'commission' => 30, 'status' => 'approved']);

    $patient->depositFloat(300.00);

    $temp = TemporaryAppointment::query()->create([
        'user_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => now()->addDay(),
        'appointment_date' => now()->addDay()->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
        'duration' => 30,
        'extend_at' => now()->addDay()->addMinutes(30),
        'appointment_for' => 'self',
        'patient_name' => $patient->name,
        'patient_phone' => '966500000000',
        'communications' => ['chat'],
        'amount' => 150.00,
        'discount' => 0,
        'tax' => 0,
        'total' => 150.00,
        'wallet_amount' => 150.00,
        'payment_status' => 'unpaid',
    ]);

    $appointment = app(PatientPaymentCompletionService::class)->completeWithWalletOnly($temp);

    expect($appointment)->not->toBeNull()
        ->and((float) $patient->fresh()->balanceFloat)->toBe(150.0)
        ->and((float) $doctor->fresh()->balanceFloat)->toBe(105.0)
        ->and((float) $appointment->mashora_share)->toBe(45.0);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointments')
        ->call('cancelAppointment', $appointment->id)
        ->assertHasNoErrors();

    $appointment->refresh();

    expect((float) $patient->fresh()->balanceFloat)->toBe(300.0)
        ->and((float) $doctor->fresh()->balanceFloat)->toBe(0.0)
        ->and((float) $appointment->doctor_share)->toBe(0.0)
        ->and((float) $appointment->mashora_share)->toBe(0.0);
});

test('wallet only booking deducts patient balance', function (): void {
    $patient = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create(['profile_completed' => true, 'commission' => 20]);

    $patient->depositFloat(300.00);

    $temp = TemporaryAppointment::query()->create([
        'user_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => now()->addDay(),
        'appointment_date' => now()->addDay()->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
        'duration' => 30,
        'extend_at' => now()->addDay()->addMinutes(30),
        'appointment_for' => 'self',
        'patient_name' => $patient->name,
        'patient_phone' => '966500000000',
        'communications' => ['chat'],
        'amount' => 250.00,
        'discount' => 0,
        'tax' => 0,
        'total' => 250.00,
        'wallet_amount' => 250.00,
        'payment_status' => 'unpaid',
    ]);

    $appointment = app(PatientPaymentCompletionService::class)->completeWithWalletOnly($temp);

    expect($appointment)->not->toBeNull()
        ->and((float) $patient->fresh()->balanceFloat)->toBe(50.0)
        ->and((float) $appointment->wallet_amount)->toBe(250.0)
        ->and((float) $doctor->fresh()->balanceFloat)->toBe(200.0);

    $notification = Notification::query()
        ->where('userable_type', Doctor::class)
        ->where('userable_id', $doctor->id)
        ->where('type', 'appointment_booked')
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toBe(__('doctor.notifications.appointment_booked_title'));
});
