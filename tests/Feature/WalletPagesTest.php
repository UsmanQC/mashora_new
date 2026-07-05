<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use App\Services\AppointmentWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('patient wallet page shows balance and refund transaction', function (): void {
    app()->setLocale('en');

    $patient = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create(['commission' => 0]);

    $appointment = Appointment::factory()->create([
        'user_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'total' => 120.00,
        'doctor_share' => 120.00,
        'status' => 'new',
    ]);

    $doctor->depositFloat(120.00);
    app(AppointmentWalletService::class)->refundToPatient($appointment->fresh());

    $this->actingAs($patient)
        ->get(route('patient.wallet'))
        ->assertSuccessful()
        ->assertSee('data-test="patient-luxury-wallet"', false)
        ->assertSee('data-test="patient-wallet-header"', false)
        ->assertSee(__('patient.wallet.title'), false)
        ->assertSee('120.00', false)
        ->assertSee(__('patient.wallet.type_refund'), false)
        ->assertSee('bg-emerald-100', false)
        ->assertSee('text-rose-600', false);
});

test('doctor wallet page shows balance and earning transaction', function (): void {
    app()->setLocale('en');

    $doctor = Doctor::factory()->create(['status' => 'approved', 'commission' => 25]);
    $patient = User::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'total' => 400.00,
        'status' => 'new',
    ]);

    app(AppointmentWalletService::class)->creditDoctorEarning($appointment->fresh());

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.settings.wallet'))
        ->assertSuccessful()
        ->assertSee(__('doctor.wallet.title'), false)
        ->assertSee('300.00', false)
        ->assertSee(__('doctor.wallet.type_earning'), false);
});

test('doctor wallet page shows paid sessions count for current month', function (): void {
    app()->setLocale('en');

    $doctor = Doctor::factory()->create(['status' => 'approved', 'commission' => 25]);
    $patient = User::factory()->create();

    Appointment::factory()->count(2)->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'total' => 100.00,
        'status' => 'completed',
    ])->each(function (Appointment $appointment): void {
        app(AppointmentWalletService::class)->creditDoctorEarning($appointment->fresh());
    });

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.settings.wallet'))
        ->assertSuccessful()
        ->assertSee(__('doctor.wallet.month_sessions'), false)
        ->assertSee('2', false)
        ->assertSee(__('doctor.wallet.sessions_suffix'), false);
});

test('doctor wallet page shows previous month income', function (): void {
    app()->setLocale('en');

    $doctor = Doctor::factory()->create(['status' => 'approved', 'commission' => 25]);
    $patient = User::factory()->create();

    $this->travelTo(now(config('app.timezone'))->subMonth()->day(15));

    $previousMonthAppointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'total' => 200.00,
        'status' => 'new',
    ]);

    app(AppointmentWalletService::class)->creditDoctorEarning($previousMonthAppointment->fresh());

    $this->travelTo(now(config('app.timezone'))->startOfMonth()->addDays(2));

    $currentMonthAppointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'total' => 400.00,
        'status' => 'new',
    ]);

    app(AppointmentWalletService::class)->creditDoctorEarning($currentMonthAppointment->fresh());

    $this->travelBack();

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.settings.wallet'))
        ->assertSuccessful()
        ->assertSee(__('doctor.wallet.previous_month_income'), false)
        ->assertSee('150.00', false)
        ->assertSee('300.00', false);
});

test('doctor wallet page shows simplified monthly summary for mixed month', function (): void {
    app()->setLocale('en');

    $doctor = Doctor::factory()->create(['status' => 'approved', 'commission' => 30]);
    $patient = User::factory()->create();

    $completedAppointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'total' => 20.00,
        'status' => 'completed',
    ]);

    $refundedAppointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'total' => 20.00,
        'status' => 'cancelled',
    ]);

    $wallet = app(AppointmentWalletService::class);
    $wallet->creditDoctorEarning($completedAppointment->fresh());
    $wallet->creditDoctorEarning($refundedAppointment->fresh());
    $wallet->refundToPatient($refundedAppointment->fresh());

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.settings.wallet'))
        ->assertSuccessful()
        ->assertSee(__('doctor.wallet.month_income'), false)
        ->assertSee(__('doctor.wallet.month_refunded_sessions'), false)
        ->assertSee(__('doctor.wallet.month_sessions'), false)
        ->assertSee(__('doctor.wallet.month_paid_out'), false)
        ->assertSee('14.00', false)
        ->assertSee('2', false);
});

test('doctor wallet page shows zero income previous month after refund reversal', function (): void {
    app()->setLocale('en');

    $doctor = Doctor::factory()->create(['status' => 'approved', 'commission' => 30]);
    $patient = User::factory()->create();

    $this->travelTo(now(config('app.timezone'))->subMonth()->day(15));

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $patient->id,
        'total' => 20.00,
        'status' => 'new',
    ]);

    app(AppointmentWalletService::class)->creditDoctorEarning($appointment->fresh());
    app(AppointmentWalletService::class)->refundToPatient($appointment->fresh());

    $this->travelBack();

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.settings.wallet'))
        ->assertSuccessful()
        ->assertSee(__('doctor.wallet.previous_month_income'), false)
        ->assertSee('0.00', false);
});

test('patient wallet page tolerates transactions linked to soft deleted wallets', function (): void {
    app()->setLocale('en');

    $patient = User::factory()->create(['profile_completed' => true]);
    $patient->depositFloat(80.00);
    $patient->wallet?->delete();
    $patient->unsetRelation('wallet');

    $this->actingAs($patient)
        ->get(route('patient.wallet'))
        ->assertSuccessful()
        ->assertSee(__('patient.wallet.title'), false);
});

test('guest cannot access patient wallet', function (): void {
    $this->get(route('patient.wallet'))
        ->assertRedirect(route('patient.phone'));
});
