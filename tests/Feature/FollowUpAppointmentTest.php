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
use App\Services\FollowUpAppointmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function seedDoctorWithSlots(int $slotDayOffset = 7): Doctor
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
        'day_of_week' => strtolower(now()->addDays($slotDayOffset)->englishDayOfWeek),
        'is_working' => true,
    ]);

    WorkingHour::query()->create([
        'working_day_id' => $workingDay->id,
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
    ]);

    return $doctor;
}

test('doctor appointments list shows follow-up action for completed sessions', function () {
    app()->setLocale('en');

    $doctor = seedDoctorWithSlots();
    $user = User::factory()->create();

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'duration' => 30,
        'patient_name' => $user->name,
        'appointment_date' => now()->format('Y-m-d'),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    $this->actingAs($doctor, 'doctor');

    Livewire::test('pages::doctor.appointments')
        ->assertSee(__('doctor.workspace.tab_follow_up'), false)
        ->assertSee(route('doctor.appointments.follow-up', $appointment), false);
});

test('follow-up page picks first date with working hours when suggested day has none', function () {
    $doctor = seedDoctorWithSlots(7);
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

    $firstWithHours = now()->addDays(7)->format('Y-m-d');

    $this->actingAs($doctor, 'doctor');

    Livewire::test('pages::doctor.appointment.follow-up', ['appointment' => $appointment])
        ->assertSet('newDate', $firstWithHours)
        ->assertSee('10:00', false);
});

test('doctor can schedule free follow-up and patient receives notification', function () {
    $doctor = seedDoctorWithSlots(7);
    $user = User::factory()->create(['profile_completed' => true]);

    $followUpDate = now()->addDays(7)->format('Y-m-d');

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'duration' => 30,
        'appointment_date' => now()->format('Y-m-d'),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
        'patient_name' => $user->name,
        'patient_phone' => $user->phone,
    ]);

    $this->actingAs($doctor, 'doctor');

    Livewire::test('pages::doctor.appointment.follow-up', ['appointment' => $appointment])
        ->set('newDate', $followUpDate)
        ->set('selectedTime', '10:00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('doctor.appointments'));

    $followUp = Appointment::query()->where('parent_id', $appointment->id)->first();

    expect($followUp)->not->toBeNull()
        ->and($followUp->status)->toBe('pending_follow_up')
        ->and($followUp->is_follow_up)->toBeTrue()
        ->and($followUp->patient_confirmed_at)->toBeNull()
        ->and((float) $followUp->total)->toBe(0.0);

    expect(Notification::query()
        ->where('userable_id', $user->id)
        ->where('type', 'follow_up_appointment')
        ->exists())->toBeTrue();
});

test('patient confirms free follow-up without payment', function () {
    $doctor = seedDoctorWithSlots(7);
    $user = User::factory()->create(['profile_completed' => true]);

    $parent = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'duration' => 30,
    ]);

    $followUpDate = now()->addDays(7)->format('Y-m-d');

    $followUp = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'parent_id' => $parent->id,
        'is_follow_up' => true,
        'status' => 'pending_follow_up',
        'duration' => 30,
        'appointment_date' => $followUpDate,
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
        'patient_name' => $user->name,
        'patient_phone' => $user->phone,
        'amount' => 0,
        'total' => 0,
        'wallet_amount' => 0,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::patient.follow-up-confirm', ['appointment' => $followUp])
        ->call('confirmAndPay')
        ->assertRedirect(route('patient.appointments'));

    $booked = $followUp->fresh();

    expect($booked->patient_confirmed_at)->not->toBeNull()
        ->and($booked->status)->toBe('new');

    expect(Notification::query()
        ->where('userable_id', $user->id)
        ->where('type', 'follow_up_booked')
        ->exists())->toBeTrue();
});

test('follow up service rejects scheduling before session is completed', function () {
    $doctor = seedDoctorWithSlots();
    $user = User::factory()->create();

    $parent = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'in_process',
        'duration' => 30,
    ]);

    $service = app(FollowUpAppointmentService::class);

    expect(fn () => $service->create($doctor, $parent, now()->addDays(7)->format('Y-m-d'), '10:00'))
        ->toThrow(ValidationException::class);
});

test('follow up service rejects dates outside the follow-up window', function () {
    $doctor = seedDoctorWithSlots();
    $user = User::factory()->create();

    $parent = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'duration' => 30,
        'appointment_date' => now()->format('Y-m-d'),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    $service = app(FollowUpAppointmentService::class);

    expect(fn () => $service->create($doctor, $parent, now()->addDays(15)->format('Y-m-d'), '10:00'))
        ->toThrow(ValidationException::class);
});

test('follow up service rejects duplicate pending invitation', function () {
    $doctor = seedDoctorWithSlots();
    $user = User::factory()->create();

    $parent = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'duration' => 30,
        'appointment_date' => now()->format('Y-m-d'),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'parent_id' => $parent->id,
        'status' => 'pending_follow_up',
        'duration' => 30,
        'appointment_date' => now()->addDays(7)->format('Y-m-d'),
        'start_time' => '11:00:00',
        'end_time' => '11:30:00',
        'amount' => 0,
        'total' => 0,
    ]);

    $service = app(FollowUpAppointmentService::class);

    expect(fn () => $service->create($doctor, $parent, now()->addDays(8)->format('Y-m-d'), '10:00'))
        ->toThrow(ValidationException::class);
});
