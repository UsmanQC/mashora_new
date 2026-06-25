<?php

use App\Models\Doctor;
use App\Models\Duration;
use App\Models\TemporaryAppointment;
use App\Models\User;
use App\Services\HyperpayCheckoutService;
use App\Services\PatientPaymentCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('hyperpay payment success without checkout id fails', function () {
    config([
        'payment.driver' => 'hyperpay',
        'hyperpay.token' => 'test-token',
        'hyperpay.entity_id_b2c' => 'entity-test',
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

test('hyperpay payment success confirms booking when payment is successful', function () {
    config([
        'payment.driver' => 'hyperpay',
        'hyperpay.token' => 'test-token',
        'hyperpay.entity_id_b2c' => 'entity-test',
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

    $merchantTransactionId = 'MSH_BOOK_1_202601011200001234';

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
        'payment_session_id' => 'checkout-test-123',
        'payment_invoice_id' => $merchantTransactionId,
    ]);

    $responseData = [
        'id' => 'payment-abc',
        'ndc' => 'checkout-test-123',
        'merchantTransactionId' => $merchantTransactionId,
        'result' => [
            'code' => '000.000.000',
            'description' => 'Transaction succeeded',
        ],
        'amount' => '100.00',
        'currency' => 'SAR',
        'paymentBrand' => 'VISA',
    ];

    $this->mock(HyperpayCheckoutService::class, function ($mock) use ($responseData): void {
        $mock->shouldReceive('fetchPaymentResult')
            ->once()
            ->with('checkout-test-123', 'entity-test')
            ->andReturn($responseData);

        $mock->shouldReceive('responseBelongsToBooking')
            ->once()
            ->andReturn(true);

        $mock->shouldReceive('getPaymentStatus')
            ->once()
            ->with('000.000.000')
            ->andReturn('success');

        $mock->shouldReceive('paymentReferenceId')
            ->once()
            ->with($responseData)
            ->andReturn('payment-abc');
    });

    $result = app(PatientPaymentCompletionService::class)->confirmIfPaid(
        $temp,
        Request::create('/', 'GET', [
            'checkoutId' => 'checkout-test-123',
            'entityId' => 'entity-test',
        ])
    );

    expect($result['state'])->toBe('paid')
        ->and($result['appointment'])->not->toBeNull();

    $temp->refresh();

    expect($temp->payment_status)->toBe('paid')
        ->and($temp->appointment_id)->not->toBeNull();
});

test('hyperpay pending payment returns pending state', function () {
    config([
        'payment.driver' => 'hyperpay',
        'hyperpay.token' => 'test-token',
        'hyperpay.entity_id_b2c' => 'entity-test',
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

    $merchantTransactionId = 'MSH_BOOK_2_202601011200005678';

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
        'payment_session_id' => 'checkout-pending',
        'payment_invoice_id' => $merchantTransactionId,
    ]);

    $this->mock(HyperpayCheckoutService::class, function ($mock) use ($merchantTransactionId): void {
        $mock->shouldReceive('fetchPaymentResult')
            ->once()
            ->andReturn([
                'merchantTransactionId' => $merchantTransactionId,
                'result' => ['code' => '000.200.000'],
            ]);

        $mock->shouldReceive('responseBelongsToBooking')->once()->andReturn(true);
        $mock->shouldReceive('getPaymentStatus')->once()->with('000.200.000')->andReturn('processing');
    });

    $result = app(PatientPaymentCompletionService::class)->confirmIfPaid(
        $temp,
        Request::create('/', 'GET', ['checkoutId' => 'checkout-pending'])
    );

    expect($result['state'])->toBe('pending');
});
