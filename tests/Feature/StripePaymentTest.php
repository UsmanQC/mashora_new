<?php

use App\Models\Doctor;
use App\Models\Duration;
use App\Models\TemporaryAppointment;
use App\Models\User;
use App\Services\PatientPaymentCompletionService;
use App\Services\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Stripe\Checkout\Session as StripeSession;

uses(RefreshDatabase::class);

test('stripe payment success without session id fails', function () {
    config([
        'payment.driver' => 'stripe',
        'stripe.key' => 'pk_test_example',
        'stripe.secret' => 'sk_test_example',
    ]);

    $user = User::factory()->create(['profile_completed' => true]);
    Duration::query()->create(['duration' => 15, 'title' => '15 min']);

    $doctor = Doctor::query()->create([
        'name' => 'Dr Test',
        'name_ar' => 'د',
        'status' => 'approved',
        'spoken_languages' => 'ar',
        'gender' => 'male',
    ]);

    $doctor->durations()->attach(15, ['price' => 100.0]);

    $temp = TemporaryAppointment::create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'appointment_date' => now()->addDay()->toDateString(),
        'start_time' => '12:15:00',
        'end_time' => '12:30:00',
        'duration' => 15,
        'extend_at' => now()->addDay()->addMinutes(15)->format('Y-m-d H:i:s'),
        'appointment_for' => 'self',
        'patient_name' => 'Patient',
        'patient_phone' => '966500000000',
        'communications' => ['chat'],
        'amount' => 100,
        'discount' => 0,
        'tax' => 0,
        'total' => 100,
        'appointment_type' => 'regular',
        'payment_status' => 'unpaid',
    ]);

    $result = app(PatientPaymentCompletionService::class)->confirmIfPaid($temp, Request::create('/'));

    expect($result['state'])->toBe('failed')
        ->and($result['appointment'])->toBeNull();
});

test('stripe payment success confirms booking when session is paid', function () {
    config([
        'payment.driver' => 'stripe',
        'stripe.key' => 'pk_test_example',
        'stripe.secret' => 'sk_test_example',
    ]);

    $user = User::factory()->create(['profile_completed' => true]);
    Duration::query()->create(['duration' => 15, 'title' => '15 min']);

    $doctor = Doctor::query()->create([
        'name' => 'Dr Test',
        'name_ar' => 'د',
        'status' => 'approved',
        'spoken_languages' => 'ar',
        'gender' => 'male',
    ]);

    $doctor->durations()->attach(15, ['price' => 100.0]);

    $temp = TemporaryAppointment::create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'appointment_date' => now()->addDay()->toDateString(),
        'start_time' => '12:15:00',
        'end_time' => '12:30:00',
        'duration' => 15,
        'extend_at' => now()->addDay()->addMinutes(15)->format('Y-m-d H:i:s'),
        'appointment_for' => 'self',
        'patient_name' => 'Patient',
        'patient_phone' => '966500000000',
        'communications' => ['chat'],
        'amount' => 100,
        'discount' => 0,
        'tax' => 0,
        'total' => 100,
        'appointment_type' => 'regular',
        'payment_status' => 'unpaid',
        'payment_session_id' => 'cs_test_123',
    ]);

    $session = StripeSession::constructFrom([
        'id' => 'cs_test_123',
        'payment_status' => 'paid',
        'payment_intent' => 'pi_test_123',
        'amount_total' => 10000,
        'currency' => 'sar',
        'metadata' => [
            'type' => 'booking',
            'temporary_appointment_id' => (string) $temp->id,
            'user_id' => (string) $user->id,
        ],
    ]);

    $this->mock(StripeCheckoutService::class, function ($mock) use ($session): void {
        $mock->shouldReceive('retrieveSession')
            ->once()
            ->with('cs_test_123')
            ->andReturn($session);
    });

    $result = app(PatientPaymentCompletionService::class)->confirmIfPaid(
        $temp,
        Request::create('/', 'GET', ['session_id' => 'cs_test_123'])
    );

    expect($result['state'])->toBe('paid')
        ->and($result['appointment'])->not->toBeNull();

    $temp->refresh();

    expect($temp->payment_status)->toBe('paid')
        ->and($temp->appointment_id)->not->toBeNull();
});

test('payment failed route shows success when booking already completed', function () {
    config([
        'payment.driver' => 'stripe',
        'stripe.key' => 'pk_test_example',
        'stripe.secret' => 'sk_test_example',
    ]);

    $user = User::factory()->create(['profile_completed' => true]);
    Duration::query()->create(['duration' => 15, 'title' => '15 min']);

    $doctor = Doctor::query()->create([
        'name' => 'Dr Test',
        'name_ar' => 'د',
        'status' => 'approved',
        'spoken_languages' => 'ar',
        'gender' => 'male',
    ]);

    $doctor->durations()->attach(15, ['price' => 100.0]);

    $temp = TemporaryAppointment::create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'appointment_date' => now()->addDay()->toDateString(),
        'start_time' => '12:15:00',
        'end_time' => '12:30:00',
        'duration' => 15,
        'extend_at' => now()->addDay()->addMinutes(15)->format('Y-m-d H:i:s'),
        'appointment_for' => 'self',
        'patient_name' => 'Patient',
        'patient_phone' => '966500000000',
        'communications' => ['chat'],
        'amount' => 100,
        'discount' => 0,
        'tax' => 0,
        'total' => 100,
        'appointment_type' => 'regular',
        'payment_status' => 'paid',
        'payment_session_id' => 'cs_test_paid',
        'appointment_id' => null,
    ]);

    $appointment = app(PatientPaymentCompletionService::class)->forceCompleteForTesting($temp->fresh());

    expect($appointment)->not->toBeNull();

    $this->actingAs($user)
        ->get(route('patient.payment.failed', $temp))
        ->assertSuccessful()
        ->assertSee(__('patient_booking.payment_success_title'), false);
});
