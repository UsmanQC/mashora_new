<?php

use App\Events\AppointmentIncomingCallAnnounced;
use App\Events\AppointmentSessionStarted;
use App\Events\PatientAppointmentSessionStarted;
use App\Events\PatientSessionJoinRequested;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Notification;
use App\Models\User;
use App\Services\AppointmentSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('doctor can start session for new or rescheduled appointments', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true, 'name' => 'Test Doctor']);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'rescheduled',
        'duration' => 30,
        'appointment_date' => now()->toDateString(),
        'scheduled_at' => now(),
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.conversation', ['appointment' => $appointment])
        ->call('startSession');

    $fresh = $appointment->fresh();

    expect($fresh->status)->toBe('in_process')
        ->and($fresh->actual_start_at)->not->toBeNull()
        ->and($fresh->extend_at)->not->toBeNull();

    expect(Notification::query()
        ->where('userable_id', $user->id)
        ->where('type', 'session_started')
        ->exists())->toBeTrue();
});

test('doctor starting session broadcasts patient session started event', function () {
    Event::fake([
        AppointmentSessionStarted::class,
        PatientAppointmentSessionStarted::class,
    ]);

    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'duration' => 30,
        'appointment_date' => now()->toDateString(),
        'scheduled_at' => now(),
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.conversation', ['appointment' => $appointment])
        ->call('startSession');

    Event::assertDispatched(PatientAppointmentSessionStarted::class, function (PatientAppointmentSessionStarted $event) use ($user, $appointment): bool {
        return $event->userId === $user->id && $event->appointmentId === $appointment->id;
    });
});

test('patient cannot start session through realtime notify endpoint', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
    ]);

    $this->actingAs($user)
        ->postJson(route('patient.appointments.realtime.notify-call', $appointment))
        ->assertForbidden();

    expect($appointment->fresh()->status)->toBe('new');
});

test('patient conversation refresh picks up doctor started session', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();

    $appointment = Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'status' => 'new',
    ]);

    Livewire::actingAs($user)
        ->test('pages::patient.appointment.conversation', ['appointment' => $appointment])
        ->assertSet('appointment.status', 'new')
        ->call('refreshAppointmentSession')
        ->assertSet('appointment.status', 'new');

    $appointment->update([
        'status' => 'in_process',
        'actual_start_at' => now(),
        'extend_at' => now()->addMinutes(30),
    ]);

    Livewire::actingAs($user)
        ->test('pages::patient.appointment.conversation', ['appointment' => $appointment])
        ->call('refreshAppointmentSession')
        ->assertSet('appointment.status', 'in_process');
});

test('patient conversation shows attend-only call ui without outbound call buttons', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();

    $appointment = Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'status' => 'in_process',
        'actual_start_at' => now(),
        'extend_at' => now()->addMinutes(30),
    ]);

    Livewire::actingAs($user)
        ->test('pages::patient.appointment.conversation', ['appointment' => $appointment])
        ->assertDontSee('id="btn-patient-video"', false)
        ->assertSee('id="incoming-call-accept"', false)
        ->assertSee('id="incoming-call-banner"', false)
        ->assertSee('id="patient-session-join-call-btn"', false)
        ->assertSee(__('patient.appointments.incoming_call_title'), false)
        ->assertSee('data-label-no-active-call', false)
        ->assertSee(__('patient.appointments.waiting_for_specialist_call'));
});

test('doctor notify call stores pending incoming call for patient fetch', function () {
    config([
        'broadcasting.default' => 'pusher',
        'agora.AGORA_APP_ID' => 'test-app-id',
        'agora.AGORA_APP_CERTIFICATE' => str_repeat('a', 32),
    ]);

    Event::fake([
        AppointmentIncomingCallAnnounced::class,
        PatientSessionJoinRequested::class,
    ]);

    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'in_process',
        'actual_start_at' => now(),
        'extend_at' => now()->addMinutes(30),
    ]);

    $this->actingAs($doctor, 'doctor')
        ->postJson(route('doctor.appointments.realtime.notify-call', $appointment), [
            'agora_app_id' => 'test-app-id',
            'agora_token' => 'test-token',
            'agora_channel' => 'video_call_'.$appointment->id,
            'call_type' => 'video',
        ])
        ->assertOk();

    $this->actingAs($user)
        ->getJson(route('patient.appointments.realtime.pending-call', $appointment))
        ->assertSuccessful()
        ->assertJson([
            'pending' => true,
            'appointment_id' => $appointment->id,
            'call_type' => 'video',
            'agora_app_id' => 'test-app-id',
            'agora_channel' => 'video_call_'.$appointment->id,
        ]);
});

test('patient cannot fetch agora token before doctor starts session', function () {
    config([
        'agora.AGORA_APP_ID' => 'test-app-id',
        'agora.AGORA_APP_CERTIFICATE' => str_repeat('a', 32),
    ]);

    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
    ]);

    $this->actingAs($user)
        ->postJson(route('patient.appointments.realtime.agora-token', $appointment))
        ->assertForbidden();
});

test('patient can fetch agora token after doctor starts session', function () {
    config([
        'agora.AGORA_APP_ID' => 'test-app-id',
        'agora.AGORA_APP_CERTIFICATE' => str_repeat('a', 32),
    ]);

    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'in_process',
        'actual_start_at' => now(),
    ]);

    $this->actingAs($user)
        ->postJson(route('patient.appointments.realtime.agora-token', $appointment))
        ->assertSuccessful()
        ->assertJsonStructure(['agora_app_id', 'agora_token', 'agora_channel']);
});

test('patient appointments show waiting state before doctor starts session', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create(['name' => 'Test Doctor']);

    Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'status' => 'new',
        'appointment_date' => now()->addDay()->toDateString(),
        'start_time' => '10:00:00',
        'patient_name' => 'Patient Test',
    ]);

    Livewire::actingAs($user)
        ->test('pages::patient.appointments')
        ->assertSee(__('patient.appointments.waiting_for_doctor'), false);
});

test('patient appointments show join session after doctor starts', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();

    $appointment = Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'status' => 'in_process',
        'actual_start_at' => now(),
    ]);

    $this->actingAs($user)->get(route('patient.appointments'))
        ->assertSuccessful()
        ->assertSee(__('patient.appointments.join_session'), false)
        ->assertSee(route('patient.appointments.conversation', ['appointment' => $appointment->id]), false);
});

test('appointment session service rejects patient-side start attempts', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'appointment_date' => now()->toDateString(),
        'start_time' => now()->format('H:i:s'),
    ]);

    $service = app(AppointmentSessionService::class);

    expect($service->canPatientJoin($appointment))->toBeFalse()
        ->and($service->canDoctorStart($appointment))->toBeTrue();
});

test('doctor cannot start session before scheduled time', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'scheduled_at' => null,
        'appointment_date' => now()->addDay()->toDateString(),
        'start_time' => '10:00:00',
    ]);

    $service = app(AppointmentSessionService::class);

    expect($service->canDoctorStart($appointment))->toBeFalse();

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.conversation', ['appointment' => $appointment])
        ->call('startSession');

    expect($appointment->fresh()->status)->toBe('new');
});

test('doctor appointments list disables open session before scheduled time', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'patient_name' => 'Patient Test',
        'scheduled_at' => null,
        'appointment_date' => now()->addDay()->toDateString(),
        'start_time' => '10:00:00',
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointments')
        ->assertSee(__('doctor.appointments.starts_in_label'), false)
        ->assertSee(__('doctor.appointments.open_session_wait'), false)
        ->assertSee('cursor-not-allowed', false);
});

test('doctor appointments list shows starts in timer for upcoming new sessions', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'patient_name' => 'Patient Test',
        'scheduled_at' => null,
        'appointment_date' => now()->addDay()->toDateString(),
        'start_time' => '10:00:00',
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointments')
        ->assertSee(__('doctor.appointments.starts_in_label'), false)
        ->assertSee('appointmentStartTimer', false)
        ->assertSee(__('doctor.appointments.open_session'), false);
});
