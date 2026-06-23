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
