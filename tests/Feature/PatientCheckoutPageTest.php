<?php

use App\Models\Doctor;
use App\Models\Duration;
use App\Models\TemporaryAppointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createLuxuryCheckoutAppointment(User $user, Doctor $doctor): TemporaryAppointment
{
    return TemporaryAppointment::query()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'appointment_date' => now()->addDay()->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
        'duration' => 30,
        'extend_at' => now()->addDay()->addMinutes(30)->format('Y-m-d H:i:s'),
        'appointment_for' => 'self',
        'patient_name' => $user->name,
        'patient_phone' => '966500000000',
        'communications' => ['chat', 'video_call', 'voice_call'],
        'amount' => 200.0,
        'discount' => 0,
        'tax' => 0,
        'total' => 200.0,
        'wallet_amount' => 0,
        'payment_status' => 'unpaid',
    ]);
}

test('authenticated patient sees luxury checkout shell on mobile', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    Duration::query()->create(['duration' => 30, 'title' => '30 min']);
    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $doctor->durations()->attach(30, ['price' => 200.0]);

    $temp = createLuxuryCheckoutAppointment($user, $doctor);

    $this->actingAs($user)
        ->get(route('patient.checkout', $temp))
        ->assertSuccessful()
        ->assertSee('data-test="patient-luxury-checkout"', false)
        ->assertSee('data-test="patient-checkout-header"', false)
        ->assertSee('data-test="patient-checkout-step-payment"', false)
        ->assertSee('data-test="patient-checkout-payment-card"', false)
        ->assertSee('data-test="patient-checkout-payment-methods"', false)
        ->assertSee(__('patient_booking.pay_through'), false)
        ->assertSee('data-test="patient-navbar-language-switch"', false);
});

test('myfatoorah checkout panel does not render fake non-interactive card fields', function () {
    $panel = file_get_contents(resource_path('views/partials/patient-checkout-payment-panel.blade.php'));

    expect($panel)
        ->toContain('mf-form-element')
        ->not->toContain('payment-card-field-guide');
});

test('guest cannot access checkout page', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    Duration::query()->create(['duration' => 30, 'title' => '30 min']);
    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $doctor->durations()->attach(30, ['price' => 200.0]);

    $temp = createLuxuryCheckoutAppointment($user, $doctor);

    $this->get(route('patient.checkout', $temp))
        ->assertRedirect(route('patient.phone'));
});
