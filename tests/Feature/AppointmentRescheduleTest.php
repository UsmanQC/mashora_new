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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function seedDoctorForReschedule(): Doctor
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

    $workingDay = WorkingDay::query()->create([
        'doctor_id' => $doctor->id,
        'day_of_week' => strtolower(now()->englishDayOfWeek),
        'is_working' => true,
    ]);

    WorkingHour::query()->create([
        'working_day_id' => $workingDay->id,
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
    ]);

    return $doctor;
}

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
