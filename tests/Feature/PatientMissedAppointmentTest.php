<?php

use App\Models\Appointment;
use App\Models\Communication;
use App\Models\Degree;
use App\Models\Doctor;
use App\Models\Duration;
use App\Models\Notification;
use App\Models\Speciality;
use App\Models\User;
use App\Models\WorkingDay;
use App\Models\WorkingHour;
use App\Services\AppointmentMissedService;
use App\Services\PatientMissedAppointmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function seedDoctorForMissedReschedule(?string $forDate = null): Doctor
{
    foreach (['chat', 'voice_call', 'video_call'] as $channel) {
        Communication::query()->firstOrCreate(
            ['communication' => $channel],
            ['title' => $channel, 'title_ar' => $channel]
        );
    }

    $degree = Degree::query()->create(['title' => 'Specialist', 'title_ar' => 'أخصائي', 'status' => true]);
    Speciality::query()->create(['title' => 'Psychology', 'title_ar' => 'نفس', 'status' => true]);

    $doctor = Doctor::factory()->create([
        'degree_id' => $degree->id,
        'status' => 'approved',
        'profile_completed' => true,
    ]);

    Duration::query()->create(['duration' => 30, 'title' => '30 min']);
    $doctor->durations()->attach(30, ['price' => 250.0]);

    $weekday = $forDate !== null
        ? strtolower(Carbon::parse($forDate)->englishDayOfWeek)
        : strtolower(now()->englishDayOfWeek);

    $workingDay = WorkingDay::query()->create([
        'doctor_id' => $doctor->id,
        'day_of_week' => $weekday,
        'is_working' => true,
    ]);

    WorkingHour::query()->create([
        'working_day_id' => $workingDay->id,
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
    ]);

    return $doctor;
}

test('overdue appointment where doctor never started is marked missed without auto refund', function () {
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

test('patient can request refund for doctor missed appointment', function () {
    Carbon::setTestNow('2026-06-23 14:00:00');

    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $user = User::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'not_attended',
        'cancel_status' => 'doctor_missed',
        'appointment_date' => '2026-06-23',
        'start_time' => '12:00:00',
        'end_time' => '12:30:00',
        'duration' => 30,
        'total' => 150,
        'doctor_share' => 105,
        'mashora_share' => 45,
        'wallet_amount' => 150,
    ]);

    $doctor->depositFloat(105.00);

    app(PatientMissedAppointmentService::class)->refund($user, $appointment);

    expect((float) $user->fresh()->balanceFloat)->toBe(150.0)
        ->and((float) $doctor->fresh()->balanceFloat)->toBe(-45.0);
});

test('patient can reschedule doctor missed appointment', function () {
    Carbon::setTestNow('2026-06-24 08:00:00');

    $doctor = seedDoctorForMissedReschedule('2026-06-25');
    $user = User::factory()->create(['profile_completed' => true]);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'not_attended',
        'cancel_status' => 'doctor_missed',
        'appointment_date' => '2026-06-23',
        'start_time' => '12:00:00',
        'end_time' => '12:30:00',
        'duration' => 30,
        'total' => 150,
        'doctor_share' => 105,
        'mashora_share' => 45,
        'wallet_amount' => 150,
    ]);

    $updated = app(PatientMissedAppointmentService::class)->reschedule(
        $user,
        $appointment,
        '2026-06-25',
        '09:00',
    );

    expect($updated->status)->toBe('rescheduled')
        ->and($updated->cancel_status)->toBeNull()
        ->and($updated->appointment_date?->toDateString())->toBe('2026-06-25')
        ->and((string) $updated->start_time)->toBe('09:00:00');
});

test('patient missed tab shows reschedule and refund actions', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create(['status' => 'approved']);

    $appointment = Appointment::factory()->create([
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
        'doctor_share' => 84,
    ]);

    $this->actingAs($user)
        ->get(route('patient.appointments', ['tab' => 'missed']))
        ->assertSuccessful()
        ->assertSee(__('patient.missed.reschedule'), false)
        ->assertSee(__('patient.missed.refund'), false)
        ->assertSee(route('patient.appointments.missed-reschedule', $appointment), false);
});

test('missed follow-up appointments cannot be rescheduled or refunded', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create(['status' => 'approved']);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'not_attended',
        'cancel_status' => 'doctor_missed',
        'is_follow_up' => true,
        'patient_name' => $user->name,
        'appointment_date' => now()->subDay()->toDateString(),
        'start_time' => '11:00:00',
        'end_time' => '11:30:00',
        'total' => 0,
        'wallet_amount' => 0,
        'doctor_share' => 0,
    ]);

    expect(app(PatientMissedAppointmentService::class)->canResolve($appointment))->toBeFalse();

    $this->actingAs($user)
        ->get(route('patient.appointments', ['tab' => 'missed']))
        ->assertSuccessful()
        ->assertDontSee(__('patient.missed.prompt'), false)
        ->assertDontSee(route('patient.appointments.missed-reschedule', $appointment), false);

    expect(fn () => app(PatientMissedAppointmentService::class)->refund($user, $appointment->fresh()))
        ->toThrow(ValidationException::class);
});

test('patient can refund missed appointment from appointments page', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create(['status' => 'approved']);

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'not_attended',
        'cancel_status' => 'doctor_missed',
        'appointment_date' => now()->subDay()->toDateString(),
        'start_time' => '11:00:00',
        'end_time' => '11:30:00',
        'total' => 100,
        'wallet_amount' => 100,
        'doctor_share' => 70,
        'mashora_share' => 30,
    ]);

    $doctor->depositFloat(70.00);

    Livewire::actingAs($user)
        ->test('pages::patient.appointments')
        ->call('refundMissed', $appointment->id)
        ->assertHasNoErrors();

    expect((float) $user->fresh()->balanceFloat)->toBe(100.0);
});
