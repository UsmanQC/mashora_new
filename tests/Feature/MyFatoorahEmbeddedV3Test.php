<?php

use App\Models\Doctor;
use App\Models\Duration;
use App\Models\TemporaryAppointment;
use App\Models\User;
use App\Services\MyFatoorahEmbeddedV3Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('myfatoorah embedded v3 creates session for card only and reports api errors', function () {
    config([
        'payment.driver' => 'myfatoorah',
        'myfatoorah.api_key' => 'test-api-key',
        'myfatoorah.is_test' => true,
        'myfatoorah.vc_code' => 'SAU',
    ]);

    Http::fake([
        'apitest.myfatoorah.com/v3/sessions' => Http::response([
            'IsSuccess' => true,
            'Message' => 'Created Successfully!',
            'Data' => [
                'SessionId' => 'SAU-test-session-123',
                'EncryptionKey' => 'm2QTkGqSxy24hpRGmoJ50vk6cfz4VJITNxGe5/uO+Qo=',
            ],
        ], 201),
    ]);

    $user = User::factory()->create(['profile_completed' => true]);
    Duration::query()->create(['duration' => 30, 'title' => '30 min']);
    $doctor = Doctor::factory()->create(['status' => 'approved']);

    $temp = TemporaryAppointment::query()->create([
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
        'communications' => ['chat'],
        'amount' => 20,
        'discount' => 0,
        'tax' => 0,
        'total' => 20,
        'wallet_amount' => 0,
        'payment_status' => 'unpaid',
    ]);

    $session = app(MyFatoorahEmbeddedV3Service::class)->createCompletePaymentSession($temp, 20.0, $user);

    expect($session['ok'])->toBeTrue()
        ->and($session['session_id'])->toBe('SAU-test-session-123');

    Http::assertSent(fn ($request): bool => ($request->data()['SupportedPaymentMethods'] ?? null) === ['card', 'applepay', 'googlepay']);
});

test('myfatoorah embedded v3 returns api message when session fails', function () {
    config([
        'myfatoorah.api_key' => 'test-api-key',
        'myfatoorah.is_test' => true,
        'myfatoorah.vc_code' => 'SAU',
    ]);

    Http::fake([
        'apitest.myfatoorah.com/v3/sessions' => Http::response([
            'IsSuccess' => false,
            'Message' => 'Invalid token country',
            'ValidationErrors' => null,
        ], 400),
    ]);

    $user = User::factory()->create();
    Duration::query()->create(['duration' => 30, 'title' => '30 min']);
    $doctor = Doctor::factory()->create(['status' => 'approved']);

    $temp = TemporaryAppointment::query()->create([
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
        'communications' => ['chat'],
        'amount' => 20,
        'discount' => 0,
        'tax' => 0,
        'total' => 20,
        'wallet_amount' => 0,
        'payment_status' => 'unpaid',
    ]);

    $session = app(MyFatoorahEmbeddedV3Service::class)->createCompletePaymentSession($temp, 20.0, $user);

    expect($session['ok'])->toBeFalse()
        ->and($session['message'])->toBe('Invalid token country');
});

test('myfatoorah embedded v3 skips ssl verify only in local', function () {
    Http::fake([
        'apitest.myfatoorah.com/v3/sessions' => Http::response([
            'IsSuccess' => true,
            'Message' => 'Created Successfully!',
            'Data' => [
                'SessionId' => 'SAU-test-session-ssl',
                'EncryptionKey' => 'm2QTkGqSxy24hpRGmoJ50vk6cfz4VJITNxGe5/uO+Qo=',
            ],
        ], 201),
    ]);

    config([
        'myfatoorah.api_key' => 'test-api-key',
        'myfatoorah.is_test' => true,
        'myfatoorah.vc_code' => 'SAU',
        'app.env' => 'local',
    ]);

    $user = User::factory()->create();
    Duration::query()->create(['duration' => 30, 'title' => '30 min']);
    $doctor = Doctor::factory()->create(['status' => 'approved']);

    $temp = TemporaryAppointment::query()->create([
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
        'communications' => ['chat'],
        'amount' => 20,
        'discount' => 0,
        'tax' => 0,
        'total' => 20,
        'wallet_amount' => 0,
        'payment_status' => 'unpaid',
    ]);

    $session = app(MyFatoorahEmbeddedV3Service::class)->createCompletePaymentSession($temp, 20.0, $user);

    expect($session['ok'])->toBeTrue();
});
