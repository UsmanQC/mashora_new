<?php

use App\Events\AppointmentCallEnded;
use App\Events\AppointmentIncomingCallAnnounced;
use App\Events\AppointmentSessionStarted;
use App\Events\PatientAppointmentSessionStarted;
use App\Events\PatientSessionJoinRequested;
use App\Events\PatientSessionStartRequested;
use App\Http\Controllers\Patient\PatientAppointmentRealtimeController;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Notification;
use App\Models\User;
use App\Services\AppointmentSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('doctor can start session for new or rescheduled appointments after patient approval', function () {
    config(['appointments.relaxed_session_limits' => false]);

    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true, 'name' => 'Test Doctor']);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'rescheduled',
        'duration' => 30,
        'appointment_date' => now()->toDateString(),
        'start_time' => now()->subMinute()->format('H:i:s'),
        'session_start_requested_at' => now(),
        'session_start_approved_at' => now(),
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
    config(['appointments.relaxed_session_limits' => false]);

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
        'start_time' => now()->subMinute()->format('H:i:s'),
        'session_start_requested_at' => now(),
        'session_start_approved_at' => now(),
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
        ->assertDontSee('id="patient-session-join-call-btn"', false)
        ->assertSee('id="patient-agora-toggle-mic"', false)
        ->assertSee('id="patient-agora-toggle-video"', false)
        ->assertSee(__('patient.appointments.mic'), false)
        ->assertSee(__('patient.appointments.camera'), false)
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

test('pending incoming voice call returns audio call type for patient join', function () {
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
            'call_type' => 'audio',
        ])
        ->assertOk();

    $this->actingAs($user)
        ->getJson(route('patient.appointments.realtime.pending-call', $appointment))
        ->assertSuccessful()
        ->assertJson([
            'pending' => true,
            'appointment_id' => $appointment->id,
            'call_type' => 'audio',
        ]);
});

test('doctor end call clears pending incoming call and broadcasts call ended', function () {
    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true]);
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'in_process',
        'actual_start_at' => now(),
    ]);

    PatientAppointmentRealtimeController::storePendingIncomingCall(
        (int) $user->id,
        (int) $appointment->id,
        [
            'call_type' => 'video',
            'agora_app_id' => 'test-app-id',
            'agora_token' => 'token',
            'agora_channel' => 'video_call_'.$appointment->id,
        ],
    );

    Event::fake([AppointmentCallEnded::class]);

    $this->actingAs($doctor, 'doctor')
        ->postJson(route('doctor.appointments.realtime.end-call', $appointment))
        ->assertOk();

    Event::assertDispatched(AppointmentCallEnded::class, function (AppointmentCallEnded $event) use ($appointment, $user): bool {
        return $event->appointmentId === $appointment->id && $event->patientUserId === $user->id;
    });

    $this->actingAs($user)
        ->getJson(route('patient.appointments.realtime.pending-call', $appointment))
        ->assertSuccessful()
        ->assertJson(['pending' => false]);
});

test('patient end call clears pending incoming call and broadcasts call ended', function () {
    Event::fake([AppointmentCallEnded::class]);

    $user = User::factory()->create();
    $doctor = Doctor::factory()->create();
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'in_process',
        'actual_start_at' => now(),
    ]);

    PatientAppointmentRealtimeController::storePendingIncomingCall(
        (int) $user->id,
        (int) $appointment->id,
        [
            'call_type' => 'video',
            'agora_app_id' => 'test-app-id',
            'agora_token' => 'token',
            'agora_channel' => 'video_call_'.$appointment->id,
        ],
    );

    $this->actingAs($user)
        ->postJson(route('patient.appointments.realtime.end-call', $appointment))
        ->assertOk();

    Event::assertDispatched(AppointmentCallEnded::class, function (AppointmentCallEnded $event) use ($appointment, $user): bool {
        return $event->appointmentId === $appointment->id && $event->patientUserId === $user->id;
    });

    $this->actingAs($user)
        ->getJson(route('patient.appointments.realtime.pending-call', $appointment))
        ->assertSuccessful()
        ->assertJson(['pending' => false]);
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
    config(['appointments.relaxed_session_limits' => false]);

    $user = User::factory()->create();
    $doctor = Doctor::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'appointment_date' => now()->toDateString(),
        'start_time' => now()->subMinute()->format('H:i:s'),
    ]);

    $service = app(AppointmentSessionService::class);

    // At scheduled time the doctor may start; the patient still cannot join until in_process.
    expect($service->canPatientJoin($appointment))->toBeFalse()
        ->and($service->canDoctorStart($appointment))->toBeTrue();
});

test('doctor cannot start or request session more than one hour before scheduled time', function () {
    config(['appointments.relaxed_session_limits' => false]);

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

    expect($service->canDoctorStart($appointment))->toBeFalse()
        ->and($service->canDoctorOfferSessionStart($appointment))->toBeFalse();

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.conversation', ['appointment' => $appointment])
        ->call('startSession');

    expect($appointment->fresh()->status)->toBe('new')
        ->and($appointment->fresh()->session_start_requested_at)->toBeNull();
});

test('doctor conversation refreshes start session button after patient approval without remounting', function () {
    app()->setLocale('en');
    config(['appointments.relaxed_session_limits' => false]);

    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'duration' => 15,
        'scheduled_at' => null,
        'appointment_date' => now()->addDay()->toDateString(),
        'start_time' => '10:00:00',
        'session_start_requested_at' => now(),
        'session_start_approved_at' => null,
    ]);

    $doctorPage = Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.conversation', ['appointment' => $appointment])
        ->assertSee(__('doctor.conversation.start_session_pending'), false);

    $appointment->update(['session_start_approved_at' => now()]);

    $doctorPage
        ->dispatch('session-start-approved', appointmentId: $appointment->id)
        ->assertSee(__('doctor.conversation.start_session'), false)
        ->assertDontSee(__('doctor.conversation.start_session_pending'), false);
});

test('doctor can request early session start before one hour window and patient approval allows session to begin', function () {
    config(['appointments.relaxed_session_limits' => false]);

    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'duration' => 15,
        'scheduled_at' => null,
        'appointment_date' => now()->toDateString(),
        'start_time' => now()->addMinutes(30)->format('H:i:s'),
    ]);

    Event::fake([PatientSessionStartRequested::class]);

    $doctorPage = Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.conversation', ['appointment' => $appointment])
        ->call('startSession');

    $fresh = $appointment->fresh();
    expect($fresh->status)->toBe('new')
        ->and($fresh->session_start_requested_at)->not->toBeNull()
        ->and($fresh->session_start_approved_at)->toBeNull();

    Event::assertDispatched(PatientSessionStartRequested::class, function (PatientSessionStartRequested $event) use ($user, $appointment): bool {
        return $event->userId === $user->id && $event->appointmentId === $appointment->id;
    });

    Livewire::actingAs($user)
        ->test('pages::patient.appointment.conversation', ['appointment' => $appointment])
        ->call('approveSessionStart');

    Livewire::actingAs($doctor, 'doctor');

    $doctorPage
        ->call('refreshAppointmentSessionState')
        ->assertSee(__('doctor.conversation.start_session'), false)
        ->assertDontSee(__('doctor.conversation.start_session_pending'), false)
        ->call('startSession');

    $started = $appointment->fresh();
    expect($started->status)->toBe('in_process')
        ->and($started->actual_start_at)->not->toBeNull()
        ->and($started->extend_at?->equalTo($started->actual_start_at->copy()->addMinutes(15)))->toBeTrue();
});

test('doctor must request patient approval within one hour before scheduled time', function () {
    config(['appointments.relaxed_session_limits' => false]);

    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'duration' => 15,
        'scheduled_at' => null,
        'appointment_date' => now()->toDateString(),
        'start_time' => now()->addMinutes(30)->format('H:i:s'),
    ]);

    $service = app(AppointmentSessionService::class);

    expect($service->canDoctorStart($appointment))->toBeFalse()
        ->and($service->canDoctorStartWithoutPatientApproval($appointment))->toBeFalse()
        ->and($appointment->isDoctorChatOpen())->toBeTrue();

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.conversation', ['appointment' => $appointment])
        ->call('startSession');

    expect($appointment->fresh()->session_start_requested_at)->not->toBeNull()
        ->and($appointment->fresh()->status)->toBe('new');
});

test('doctor can start session at scheduled time without patient approval', function () {
    config(['appointments.relaxed_session_limits' => false]);

    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'duration' => 15,
        'scheduled_at' => null,
        'appointment_date' => now()->toDateString(),
        'start_time' => now()->subMinute()->format('H:i:s'),
    ]);

    $service = app(AppointmentSessionService::class);

    expect($service->canDoctorStart($appointment))->toBeTrue()
        ->and($service->canDoctorStartWithoutPatientApproval($appointment))->toBeTrue()
        ->and($appointment->isDoctorChatOpen())->toBeTrue();

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.conversation', ['appointment' => $appointment])
        ->call('startSession');

    $fresh = $appointment->fresh();
    expect($fresh->status)->toBe('in_process')
        ->and($fresh->session_start_requested_at)->toBeNull()
        ->and($fresh->actual_start_at)->not->toBeNull();
});

test('pending early approval is cleared when doctor starts at scheduled time', function () {
    config(['appointments.relaxed_session_limits' => false]);

    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'duration' => 15,
        'scheduled_at' => null,
        'appointment_date' => now()->toDateString(),
        'start_time' => now()->subMinute()->format('H:i:s'),
        'session_start_requested_at' => now()->subMinutes(10),
        'session_start_approved_at' => null,
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.conversation', ['appointment' => $appointment])
        ->call('startSession');

    $fresh = $appointment->fresh();
    expect($fresh->status)->toBe('in_process')
        ->and($fresh->session_start_requested_at)->toBeNull()
        ->and($fresh->actual_start_at)->not->toBeNull();
});

test('session start request creates patient notification with approve action', function () {
    app()->setLocale('en');
    config(['appointments.relaxed_session_limits' => false]);

    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true, 'name' => 'Test Doctor']);

    // Early start window (within 1 hour, before clock time) — approval required.
    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'scheduled_at' => null,
        'appointment_date' => now()->toDateString(),
        'start_time' => now()->addMinutes(30)->format('H:i:s'),
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.conversation', ['appointment' => $appointment])
        ->call('startSession');

    $notification = Notification::query()
        ->where('userable_type', User::class)
        ->where('userable_id', $user->id)
        ->where('type', 'session_start_request')
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->action)->toBe(route('patient.appointments.conversation', $appointment));
});

test('patient can approve session start from appointments list', function () {
    app()->setLocale('en');
    config(['appointments.relaxed_session_limits' => false]);

    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'scheduled_at' => null,
        'appointment_date' => now()->addDay()->toDateString(),
        'start_time' => '10:00:00',
        'session_start_requested_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::patient.appointments', ['tab' => 'ongoing'])
        ->assertSee(__('patient.appointments.session_start_request_approve'), false)
        ->call('approveSessionStart', $appointment->id);

    expect($appointment->fresh()->session_start_approved_at)->not->toBeNull();
});

test('doctor cannot start session before scheduled time without patient approval', function () {
    config(['appointments.relaxed_session_limits' => false]);

    $user = User::factory()->create();
    $doctor = Doctor::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'scheduled_at' => null,
        'appointment_date' => now()->toDateString(),
        'start_time' => now()->addMinutes(30)->format('H:i:s'),
    ]);

    $service = app(AppointmentSessionService::class);

    expect($service->canDoctorStart($appointment))->toBeFalse();
});

test('relaxed session limits allow doctor to start before scheduled time', function () {
    config(['appointments.relaxed_session_limits' => true]);

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

    expect($service->canDoctorStart($appointment))->toBeTrue();

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.conversation', ['appointment' => $appointment])
        ->call('startSession');

    expect($appointment->fresh()->status)->toBe('in_process');
});

test('doctor appointments list allows opening conversation before chat window for approval request', function () {
    config(['appointments.relaxed_session_limits' => false]);

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
        ->assertSee(__('doctor.appointments.open_session'), false)
        ->assertDontSee('cursor-not-allowed', false);
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
