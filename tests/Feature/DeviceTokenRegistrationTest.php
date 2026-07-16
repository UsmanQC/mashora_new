<?php

use App\Models\DeviceToken;
use App\Models\Doctor;
use App\Models\User;
use App\Services\FcmPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('authenticated patient can register and remove an fcm device token', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    $this->actingAs($user)
        ->postJson(route('patient.device-token.store'), [
            'device_token' => 'patient-fcm-token-1234567890',
        ])
        ->assertSuccessful()
        ->assertJson(['message' => 'Device token registered.']);

    expect(DeviceToken::query()->where([
        'userable_type' => User::class,
        'userable_id' => $user->id,
        'device_token' => 'patient-fcm-token-1234567890',
    ])->exists())->toBeTrue();

    $this->actingAs($user)
        ->deleteJson(route('patient.device-token.destroy'), [
            'device_token' => 'patient-fcm-token-1234567890',
        ])
        ->assertSuccessful();

    expect(DeviceToken::query()->where('device_token', 'patient-fcm-token-1234567890')->exists())->toBeFalse();
});

test('authenticated doctor can register an fcm device token', function () {
    $doctor = Doctor::factory()->create(['status' => 'approved']);

    $this->actingAs($doctor, 'doctor')
        ->postJson(route('doctor.device-token.store'), [
            'device_token' => 'doctor-fcm-token-1234567890',
        ])
        ->assertSuccessful();

    expect($doctor->deviceTokens()->where('device_token', 'doctor-fcm-token-1234567890')->exists())->toBeTrue();
});

test('guest cannot register a device token', function () {
    $this->postJson(route('patient.device-token.store'), [
        'device_token' => 'guest-fcm-token-1234567890',
    ])->assertUnauthorized();
});

test('fcm push sends to registered device tokens with legacy authorization header', function () {
    config(['push.firebase_server_key' => 'test-fcm-server-key']);

    Http::fake([
        'https://fcm.googleapis.com/fcm/send' => Http::response(['success' => 1, 'failure' => 0], 200),
    ]);

    $user = User::factory()->create();

    DeviceToken::query()->create([
        'userable_type' => User::class,
        'userable_id' => $user->id,
        'device_token' => 'push-token-abc',
    ]);

    app(FcmPushService::class)->sendToUser($user, 'Hello', 'Body', ['type' => 'test']);

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://fcm.googleapis.com/fcm/send'
            && $request->hasHeader('Authorization', 'key=test-fcm-server-key')
            && $request['registration_ids'] === ['push-token-abc']
            && $request['notification']['title'] === 'Hello'
            && $request['data']['type'] === 'test';
    });
});

test('registering the same token for another user moves ownership', function () {
    $first = User::factory()->create(['profile_completed' => true]);
    $second = User::factory()->create(['profile_completed' => true]);
    $token = 'shared-fcm-token-1234567890';

    $this->actingAs($first)
        ->postJson(route('patient.device-token.store'), ['device_token' => $token])
        ->assertSuccessful();

    $this->actingAs($second)
        ->postJson(route('patient.device-token.store'), ['device_token' => $token])
        ->assertSuccessful();

    expect(DeviceToken::query()->where('device_token', $token)->count())->toBe(1)
        ->and(DeviceToken::query()->where([
            'userable_id' => $second->id,
            'device_token' => $token,
        ])->exists())->toBeTrue();
});
