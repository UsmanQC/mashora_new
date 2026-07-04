<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Notification;
use App\Models\User;
use App\Services\AppointmentMissedService;
use App\Services\PatientMissedAppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('overdue appointment where doctor never started is marked missed and awaits patient action', function () {
    Carbon::setTestNow('2026-06-23 14:00:00');

    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $user = User::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'rescheduled',
        'patient_name' => $user->name,
        'appointment_date' => '2026-06-23',
        'start_time' => '13:00:00',
        'end_time' => '13:30:00',
        'duration' => 30,
        'total' => 200,
        'doctor_share' => 140,
        'mashora_share' => 60,
        'wallet_amount' => 200,
    ]);

    $doctor->depositFloat(140.00);

    app(AppointmentMissedService::class)->markDoctorMissed($appointment);

    $appointment->refresh();

    expect($appointment->status)->toBe('not_attended')
        ->and($appointment->cancel_status)->toBe('doctor_missed')
        ->and((float) $user->fresh()->balanceFloat)->toBe(0.0)
        ->and((float) $doctor->fresh()->balanceFloat)->toBe(140.0);

    $notification = Notification::query()
        ->where('userable_id', $user->id)
        ->where('type', 'appointment_missed')
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->action)->toContain('tab=missed')
        ->and($notification->title)->toBe(__('patient.notifications.missed_title'))
        ->and($notification->message)->toContain('200.00');
});

test('missed notification with stored translation keys displays resolved copy', function () {
    app()->setLocale('en');

    $doctor = Doctor::factory()->create(['status' => 'approved', 'name' => 'Sara Al-Qahtani']);
    $user = User::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'not_attended',
        'cancel_status' => 'doctor_missed',
        'appointment_date' => '2026-06-23',
        'start_time' => '13:00:00',
        'end_time' => '13:30:00',
        'total' => 150,
    ]);

    $notification = Notification::query()->create([
        'type' => 'appointment_missed',
        'title' => 'patient.notifications.missed_title',
        'message' => 'patient.notifications.missed_message',
        'userable_type' => User::class,
        'userable_id' => $user->id,
        'senderable_type' => Doctor::class,
        'senderable_id' => $doctor->id,
        'action' => route('patient.appointments', ['tab' => 'missed']),
    ]);

    expect($notification->displayTitle())->toBe('Session missed')
        ->and($notification->displayMessage())->toContain('Dr. Sara Al-Qahtani')
        ->and($notification->displayMessage())->toContain('150.00');

    expect($notification->repairStoredCopy())->toBeTrue();

    $notification->refresh();

    expect($notification->title)->toBe('Session missed')
        ->and($notification->message)->toContain('150.00');
});

test('future appointments are not marked missed', function () {
    config(['appointments.relaxed_session_limits' => false]);

    Carbon::setTestNow('2026-06-23 10:00:00');

    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $user = User::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'scheduled_at' => '2026-06-23 13:00:00',
        'appointment_date' => '2026-06-23',
        'start_time' => '13:00:00',
        'end_time' => '13:30:00',
        'duration' => 30,
        'total' => 150,
        'wallet_amount' => 150,
    ]);

    $processed = app(AppointmentMissedService::class)->processDueMissedAppointments();

    expect($processed)->toBe(0)
        ->and($appointment->fresh()->status)->toBe('new');
});

test('appointment is marked missed ten minutes after scheduled start when doctor never joined', function () {
    config([
        'appointments.doctor_missed_grace_minutes' => 10,
    ]);

    Carbon::setTestNow('2026-06-23 13:10:00');

    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $user = User::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'scheduled_at' => '2026-06-23 13:00:00',
        'appointment_date' => '2026-06-23',
        'start_time' => '13:00:00',
        'end_time' => '13:30:00',
        'duration' => 30,
        'total' => 150,
        'wallet_amount' => 150,
    ]);

    $processed = app(AppointmentMissedService::class)->processDueMissedAppointments();

    expect($processed)->toBe(1)
        ->and($appointment->fresh()->status)->toBe('not_attended')
        ->and($appointment->fresh()->cancel_status)->toBe('doctor_missed');
});

test('appointment is not marked missed before ten minute grace elapses', function () {
    config([
        'appointments.doctor_missed_grace_minutes' => 10,
    ]);

    Carbon::setTestNow('2026-06-23 13:09:00');

    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $user = User::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'scheduled_at' => '2026-06-23 13:00:00',
        'appointment_date' => '2026-06-23',
        'start_time' => '13:00:00',
        'end_time' => '13:30:00',
        'duration' => 30,
        'total' => 150,
        'wallet_amount' => 150,
    ]);

    $processed = app(AppointmentMissedService::class)->processDueMissedAppointments();

    expect($processed)->toBe(0)
        ->and($appointment->fresh()->status)->toBe('new');
});

test('missed appointment uses start_time not scheduled_at when they differ', function () {
    config(['appointments.doctor_missed_grace_minutes' => 10]);

    Carbon::setTestNow('2026-06-23 14:41:00');

    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $user = User::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'scheduled_at' => '2026-06-23 16:00:00',
        'appointment_date' => '2026-06-23',
        'start_time' => '14:30:00',
        'end_time' => '15:00:00',
        'duration' => 30,
        'total' => 150,
        'wallet_amount' => 150,
    ]);

    $processed = app(AppointmentMissedService::class)->processDueMissedAppointments();

    expect($processed)->toBe(1)
        ->and($appointment->fresh()->status)->toBe('not_attended')
        ->and($appointment->fresh()->cancel_status)->toBe('doctor_missed');
});

test('missed appointment processing runs even when relaxed session limits are enabled', function () {
    config([
        'appointments.relaxed_session_limits' => true,
        'appointments.doctor_missed_grace_minutes' => 10,
    ]);

    Carbon::setTestNow('2026-06-23 14:41:00');

    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $user = User::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'appointment_date' => '2026-06-23',
        'start_time' => '14:30:00',
        'end_time' => '15:00:00',
        'duration' => 30,
        'total' => 150,
        'wallet_amount' => 150,
    ]);

    $processed = app(AppointmentMissedService::class)->processDueMissedAppointments();

    expect($processed)->toBe(1)
        ->and($appointment->fresh()->status)->toBe('not_attended');
});

test('missed appointment processing is idempotent for refunds', function () {
    Carbon::setTestNow('2026-06-23 14:00:00');

    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $user = User::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'scheduled_at' => '2026-06-23 12:00:00',
        'appointment_date' => '2026-06-23',
        'start_time' => '12:00:00',
        'end_time' => '12:30:00',
        'duration' => 30,
        'total' => 100,
        'doctor_share' => 70,
        'mashora_share' => 30,
        'wallet_amount' => 100,
    ]);

    $doctor->depositFloat(70.00);

    app(AppointmentMissedService::class)->processDueMissedAppointments();
    app(AppointmentMissedService::class)->processDueMissedAppointments();

    expect((float) $user->fresh()->balanceFloat)->toBe(0.0);

    app(PatientMissedAppointmentService::class)->refund($user, $appointment->fresh());

    expect((float) $user->fresh()->balanceFloat)->toBe(100.0);

    app(PatientMissedAppointmentService::class)->refund($user, $appointment->fresh());

    expect((float) $user->fresh()->balanceFloat)->toBe(100.0);
});

test('doctor appointments page shows missed status label', function () {
    Carbon::setTestNow('2026-06-23 14:00:00');
    app()->setLocale('en');

    $doctor = Doctor::factory()->create(['status' => 'approved', 'profile_completed' => true]);

    Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => User::factory()->create()->id,
        'status' => 'not_attended',
        'cancel_status' => 'doctor_missed',
        'appointment_date' => '2026-06-22',
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
        'patient_name' => 'Patient Test',
    ]);

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.appointments', ['status' => 'not_attended']))
        ->assertSuccessful()
        ->assertSee(__('doctor.appointment_status.not_attended'), false);
});

test('patient appointments page shows missed tab entries', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create(['status' => 'approved']);

    Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'not_attended',
        'cancel_status' => 'doctor_missed',
        'patient_name' => $user->name,
        'appointment_date' => now()->subDay()->toDateString(),
        'start_time' => '11:00:00',
        'end_time' => '11:30:00',
        'total' => 120,
        'wallet_amount' => 120,
    ]);

    $this->actingAs($user)
        ->get(route('patient.appointments', ['tab' => 'missed']))
        ->assertSuccessful()
        ->assertSee(__('patient.appointments.status_missed'), false)
        ->assertSee(__('patient.missed.reschedule'), false)
        ->assertSee(__('patient.missed.refund'), false)
        ->assertSee('data-test="patient-missed-resolution"', false);
});
