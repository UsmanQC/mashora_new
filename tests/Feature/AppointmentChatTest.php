<?php

use App\Models\Appointment;
use App\Models\ChMessage;
use App\Models\Doctor;
use App\Models\Notification;
use App\Models\User;
use App\Services\FollowUpAppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('completed appointment chat stays open within follow up window', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'appointment_date' => now()->subDays(3)->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    expect($appointment->isChatOpen())->toBeTrue();
});

test('completed appointment chat closes after follow up window', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'appointment_date' => now()->subDays(FollowUpAppointmentService::windowDays() + 1)->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    expect($appointment->isChatOpen())->toBeFalse();
});

test('doctor and patient can chat after session is completed within window', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create([
        'profile_completed' => true,
    ]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'appointment_date' => now()->subDay()->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.conversation', ['appointment' => $appointment])
        ->set('draft', 'Follow-up note from doctor')
        ->call('sendMessage');

    Livewire::actingAs($user)
        ->test('pages::patient.appointment.conversation', ['appointment' => $appointment])
        ->set('draft', 'Thanks doctor')
        ->call('sendMessage');

    expect(ChMessage::query()->where('appointment_id', $appointment->id)->count())->toBe(2);
});

test('patient completed tab shows open chat for sessions within follow up window', function () {
    app()->setLocale('en');

    $user = User::factory()->create();
    $doctor = Doctor::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'appointment_date' => now()->subDays(3)->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    Livewire::actingAs($user)
        ->test('pages::patient.appointments', ['tab' => 'completed'])
        ->assertSee(__('patient.appointments.open_chat'), false)
        ->assertSee(route('patient.appointments.conversation', $appointment), false);
});

test('confirmed follow up appointment keeps chat open within parent follow up window', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create();

    $parent = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'appointment_date' => now()->subDays(2)->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    $followUp = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'parent_id' => $parent->id,
        'is_follow_up' => true,
        'status' => 'new',
        'appointment_date' => now()->addDays(7)->toDateString(),
        'start_time' => '12:00:00',
        'end_time' => '12:30:00',
        'patient_confirmed_at' => now(),
    ]);

    expect($followUp->isChatOpen())->toBeTrue()
        ->and($followUp->allowsPatientCalls())->toBeFalse();
});

test('patient ongoing tab shows follow up label and open chat for confirmed follow up', function () {
    app()->setLocale('en');

    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['name' => 'Test Doctor']);

    $parent = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'appointment_date' => now()->subDays(2)->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    $followUp = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'parent_id' => $parent->id,
        'is_follow_up' => true,
        'status' => 'new',
        'appointment_date' => now()->addDays(7)->toDateString(),
        'start_time' => '12:00:00',
        'end_time' => '12:30:00',
        'patient_name' => $user->name,
        'patient_confirmed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::patient.appointments', ['tab' => 'ongoing'])
        ->assertSee(__('patient.follow_up.confirmed_badge'), false)
        ->assertSee(__('patient.appointments.open_chat'), false)
        ->assertSee(route('patient.appointments.conversation', $followUp), false);
});

test('patient chat message creates doctor notification', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['name' => 'Patient Test']);
    $doctor = Doctor::factory()->create(['name' => 'Test Doctor']);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'appointment_date' => now()->subDay()->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
        'patient_name' => $user->name,
    ]);

    Livewire::actingAs($user)
        ->test('pages::patient.appointment.conversation', ['appointment' => $appointment])
        ->set('draft', 'Hello doctor')
        ->call('sendMessage');

    $notification = Notification::query()
        ->where('userable_type', Doctor::class)
        ->where('userable_id', $doctor->id)
        ->where('type', 'appointment_chat_message')
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->message)->toContain('Hello doctor')
        ->and($notification->action)->toBe(route('doctor.appointments.conversation', $appointment));
});

test('doctor chat message creates patient notification', function () {
    app()->setLocale('en');

    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['name' => 'Test Doctor', 'profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'appointment_date' => now()->subDay()->toDateString(),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.conversation', ['appointment' => $appointment])
        ->set('draft', 'Follow-up instructions')
        ->call('sendMessage');

    $notification = Notification::query()
        ->where('userable_type', User::class)
        ->where('userable_id', $user->id)
        ->where('type', 'appointment_chat_message')
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->message)->toContain('Follow-up instructions')
        ->and($notification->action)->toBe(route('patient.appointments.conversation', $appointment));
});
