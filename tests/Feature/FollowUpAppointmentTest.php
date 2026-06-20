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
use App\Services\FollowUpPaymentCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function seedDoctorWithSlots(): Doctor
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
        'day_of_week' => strtolower(now()->addDays(15)->englishDayOfWeek),
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
    $doctor = seedDoctorWithSlots();
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

    $preferredWithoutHours = now()->addDays(3)->format('Y-m-d');
    $firstWithHours = now()->addDays(15)->format('Y-m-d');

    $this->actingAs($doctor, 'doctor');

    Livewire::test('pages::doctor.appointment.follow-up', ['appointment' => $appointment])
        ->assertSet('newDate', $firstWithHours)
        ->assertSee('10:00', false);
});

test('doctor can schedule follow-up and patient receives notification', function () {
    $doctor = seedDoctorWithSlots();
    $user = User::factory()->create(['profile_completed' => true]);

    $followUpDate = now()->addDays(15)->format('Y-m-d');

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
        ->and($followUp->patient_confirmed_at)->toBeNull()
        ->and((float) $followUp->total)->toBe(250.0);

    expect(Notification::query()
        ->where('userable_id', $user->id)
        ->where('type', 'follow_up_appointment')
        ->exists())->toBeTrue();
});

test('patient confirms follow-up then completes booking via wallet', function () {
    $doctor = seedDoctorWithSlots();
    $user = User::factory()->create(['profile_completed' => true]);

    $parent = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'duration' => 30,
    ]);

    $followUpDate = now()->addDays(15)->format('Y-m-d');

    $followUp = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'parent_id' => $parent->id,
        'status' => 'pending_follow_up',
        'duration' => 30,
        'appointment_date' => $followUpDate,
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
        'patient_name' => $user->name,
        'patient_phone' => $user->phone,
        'amount' => 250,
        'total' => 250,
        'wallet_amount' => 0,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::patient.follow-up-confirm', ['appointment' => $followUp])
        ->call('confirmAndPay')
        ->assertRedirect(route('patient.follow-up.pay', $followUp));

    $followUp->refresh();
    expect($followUp->patient_confirmed_at)->not->toBeNull();

    $followUp->update(['wallet_amount' => 250]);
    $user->depositFloat(500);

    $booked = app(FollowUpPaymentCompletionService::class)->completeWithWalletOnly($followUp->fresh(['doctor']));

    expect($booked)->toBeInstanceOf(Appointment::class)
        ->and($booked->parent_id)->toBe($parent->id)
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

    expect(fn () => $service->create($doctor, $parent, now()->addDays(16)->format('Y-m-d'), '10:00'))
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
    ]);

    Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'parent_id' => $parent->id,
        'status' => 'pending_follow_up',
        'duration' => 30,
        'appointment_date' => now()->addDays(15)->format('Y-m-d'),
        'start_time' => '11:00:00',
        'end_time' => '11:30:00',
        'amount' => 250,
        'total' => 250,
    ]);

    $service = app(FollowUpAppointmentService::class);

    expect(fn () => $service->create($doctor, $parent, now()->addDays(16)->format('Y-m-d'), '10:00'))
        ->toThrow(ValidationException::class);
});
