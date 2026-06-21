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
use App\Services\AppointmentRescheduleService;
use App\Services\DoctorAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function seedDoctorForReschedule(?string $forDate = null): Doctor
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

test('availability filters past slots using saudi local time not utc', function () {
    config(['app.timezone' => 'UTC']);

    Carbon::setTestNow(Carbon::parse('2026-06-02 11:00:00', 'UTC'));

    $doctor = seedDoctorForReschedule();

    $slots = app(DoctorAvailabilityService::class)->availableSlots(
        $doctor,
        '2026-06-02',
        30,
    );

    expect($slots)->not->toContain('11:00')
        ->and($slots)->not->toContain('13:00')
        ->and($slots)->toContain('14:15');
});

test('reschedule page refreshes slots when switching to today from a future date', function () {
    Carbon::setTestNow(now()->setTime(14, 0));

    $doctor = seedDoctorForReschedule();
    $user = User::factory()->create();

    $futureDate = now()->addDays(7)->format('Y-m-d');
    $today = now()->format('Y-m-d');

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'duration' => 30,
        'appointment_date' => $futureDate,
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    $this->actingAs($doctor, 'doctor');

    Livewire::test('pages::doctor.appointment.reschedule', ['appointment' => $appointment])
        ->assertSet('newDate', $futureDate)
        ->assertSee('10:00 am', false)
        ->set('newDate', $today)
        ->assertSet('newDate', $today)
        ->assertDontSee('11:00 am', false)
        ->assertSee('2:15 pm', false);
});

test('reschedule page hides past time slots when today is selected', function () {
    Carbon::setTestNow(now()->setTime(14, 0));

    $doctor = seedDoctorForReschedule();
    $user = User::factory()->create();

    $appointmentDate = now()->format('Y-m-d');

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'duration' => 30,
        'appointment_date' => $appointmentDate,
        'start_time' => '15:00:00',
        'end_time' => '15:30:00',
    ]);

    $this->actingAs($doctor, 'doctor');

    Livewire::test('pages::doctor.appointment.reschedule', ['appointment' => $appointment])
        ->assertSet('newDate', $appointmentDate)
        ->assertDontSee('11:00 am', false)
        ->assertDontSee('1:00 pm', false)
        ->assertSee('2:15 pm', false);
});

test('reschedule page preselects existing appointment time on future date', function () {
    Carbon::setTestNow(now()->setTime(8, 0));

    $appointmentDate = now()->addDays(21)->format('Y-m-d');
    $doctor = seedDoctorForReschedule($appointmentDate);
    $user = User::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'duration' => 30,
        'appointment_date' => $appointmentDate,
        'start_time' => '13:45:00',
        'end_time' => '14:15:00',
    ]);

    $this->actingAs($doctor, 'doctor');

    Livewire::test('pages::doctor.appointment.reschedule', ['appointment' => $appointment])
        ->assertSet('newDate', $appointmentDate)
        ->assertSet('selectedTime', '13:45')
        ->assertSee('1:45 pm', false);
});

test('reschedule page keeps current time selected when date matches appointment', function () {
    Carbon::setTestNow(now()->setTime(8, 0));

    $appointmentDate = now()->format('Y-m-d');
    $doctor = seedDoctorForReschedule($appointmentDate);
    $user = User::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'duration' => 30,
        'appointment_date' => $appointmentDate,
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    $this->actingAs($doctor, 'doctor');

    Livewire::test('pages::doctor.appointment.reschedule', ['appointment' => $appointment])
        ->set('newDate', $appointmentDate)
        ->assertSet('selectedTime', '10:00');
});

test('reschedule page shows available time slots for selected date', function () {
    Carbon::setTestNow(now()->setTime(8, 0));

    $doctor = seedDoctorForReschedule();
    $user = User::factory()->create();

    $appointmentDate = now()->format('Y-m-d');

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'duration' => 30,
        'appointment_date' => $appointmentDate,
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    $this->actingAs($doctor, 'doctor');

    Livewire::test('pages::doctor.appointment.reschedule', ['appointment' => $appointment])
        ->assertSet('newDate', $appointmentDate)
        ->assertSet('selectedTime', '10:00')
        ->assertSee('11:00', false);
});

test('doctor can reschedule appointment and patient receives notification', function () {
    Carbon::setTestNow(now()->setTime(8, 0));

    $doctor = seedDoctorForReschedule();
    $user = User::factory()->create();

    $appointmentDate = now()->format('Y-m-d');

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'new',
        'duration' => 30,
        'appointment_date' => $appointmentDate,
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
        'patient_name' => $user->name,
        'patient_phone' => $user->phone,
    ]);

    $this->actingAs($doctor, 'doctor');

    Livewire::test('pages::doctor.appointment.reschedule', ['appointment' => $appointment])
        ->set('newDate', $appointmentDate)
        ->set('selectedTime', '11:00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('doctor.appointments'));

    $appointment->refresh();

    expect($appointment->status)->toBe('rescheduled')
        ->and($appointment->start_time)->toBe('11:00:00')
        ->and($appointment->end_time)->toBe('11:30:00');

    $notification = Notification::query()
        ->where('userable_id', $user->id)
        ->where('type', 'appointment_rescheduled')
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->action)->toBe(route('patient.appointments', ['tab' => 'rescheduled']));
});

test('reschedule service rejects completed appointments', function () {
    $doctor = seedDoctorForReschedule();
    $user = User::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'duration' => 30,
        'appointment_date' => now()->format('Y-m-d'),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    $service = app(AppointmentRescheduleService::class);

    expect(fn () => $service->reschedule($doctor, $appointment, now()->format('Y-m-d'), '11:00'))
        ->toThrow(ValidationException::class);
});
