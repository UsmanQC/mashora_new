<?php

use App\Models\Appointment;
use App\Models\DeviceToken;
use App\Models\Doctor;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('doctor cancelling appointment notifies patient and shows in cancelled tab', function () {
    config(['push.firebase_server_key' => 'test-fcm-key']);

    Http::fake([
        'https://fcm.googleapis.com/fcm/send' => Http::response(['success' => 1, 'failure' => 0], 200),
    ]);

    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $user = User::factory()->create(['profile_completed' => true]);

    DeviceToken::query()->create([
        'userable_type' => User::class,
        'userable_id' => $user->id,
        'device_token' => 'fake-device-token',
    ]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'patient_name' => $user->name,
        'appointment_date' => now()->addDays(3)->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
        'scheduled_at' => now()->addDays(3)->format('Y-m-d').' 10:00:00',
        'total' => 200,
    ]);

    $this->actingAs($doctor, 'doctor');

    Livewire::test('pages::doctor.appointments')
        ->call('cancelAppointment', $appointment->id)
        ->assertHasNoErrors();

    $appointment->refresh();

    expect($appointment->status)->toBe('cancelled')
        ->and($appointment->cancel_status)->toBe('doctor');

    $notification = Notification::query()
        ->where('userable_id', $user->id)
        ->where('type', 'appointment_cancelled')
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->action)->toContain('tab=cancelled');

    Http::assertSent(fn ($request) => $request->url() === 'https://fcm.googleapis.com/fcm/send');

    $this->actingAs($user)
        ->get(route('patient.appointments', ['tab' => 'cancelled']))
        ->assertSuccessful()
        ->assertSee((string) $appointment->patient_name, false);
});
