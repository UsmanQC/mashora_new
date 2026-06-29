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
use App\Support\DoctorAppointmentWorkflow;
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

test('doctor appointments upcoming follow ups tab counts confirmed follow ups', function () {
    app()->setLocale('en');

    $doctor = seedDoctorWithSlots();
    $user = User::factory()->create();

    Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'parent_id' => Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'user_id' => $user->id,
            'status' => 'completed',
            'duration' => 30,
            'appointment_date' => now()->subDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '10:30:00',
        ])->id,
        'is_follow_up' => true,
        'status' => 'new',
        'patient_confirmed_at' => now(),
        'duration' => 30,
        'patient_name' => $user->name,
        'appointment_date' => now()->addDay()->format('Y-m-d'),
        'start_time' => '12:00:00',
        'end_time' => '12:30:00',
        'amount' => 0,
        'total' => 0,
    ]);

    $this->actingAs($doctor, 'doctor');

    Livewire::test('pages::doctor.appointments')
        ->assertSet('statusCounts.pending_follow_up', 1)
        ->assertSet('statusCounts.new', 0)
        ->set('status', 'pending_follow_up')
        ->assertSee(__('doctor.appointment_status.follow_up'), false)
        ->assertSee($user->name, false);
});

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
    config(['appointments.follow_up_skip_patient_confirmation' => false]);

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

test('follow up is booked immediately when patient confirmation is skipped', function () {
    config(['appointments.follow_up_skip_patient_confirmation' => true]);

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
        ->assertHasNoErrors();

    $followUp = Appointment::query()->where('parent_id', $appointment->id)->first();

    expect($followUp)->not->toBeNull()
        ->and($followUp->status)->toBe('new')
        ->and($followUp->patient_confirmed_at)->not->toBeNull();
});

test('doctor can schedule follow up during in process session when relaxed', function () {
    config([
        'appointments.relaxed_session_limits' => true,
        'appointments.follow_up_skip_patient_confirmation' => true,
    ]);

    $doctor = seedDoctorWithSlots(7);
    $user = User::factory()->create(['profile_completed' => true]);

    $followUpDate = now()->addDays(14)->format('Y-m-d');

    $appointment = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'in_process',
        'duration' => 30,
        'appointment_date' => now()->format('Y-m-d'),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
        'patient_name' => $user->name,
        'patient_phone' => $user->phone,
        'actual_start_at' => now(),
    ]);

    expect(app(FollowUpAppointmentService::class)->parentCanScheduleFollowUp($appointment))->toBeTrue();

    $this->actingAs($doctor, 'doctor');

    Livewire::test('pages::doctor.appointment.follow-up', ['appointment' => $appointment])
        ->set('newDate', $followUpDate)
        ->set('selectedTime', '10:00')
        ->call('save')
        ->assertHasNoErrors();

    $followUp = Appointment::query()->where('parent_id', $appointment->id)->first();

    expect($followUp)->not->toBeNull()
        ->and($followUp->status)->toBe('new')
        ->and($followUp->is_follow_up)->toBeTrue();
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

test('doctor appointments list shows follow up badge for confirmed follow up sessions', function () {
    app()->setLocale('en');

    $doctor = seedDoctorWithSlots();
    $user = User::factory()->create();

    $parent = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'duration' => 30,
        'patient_name' => $user->name,
        'appointment_date' => now()->format('Y-m-d'),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    $followUp = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'parent_id' => $parent->id,
        'is_follow_up' => true,
        'status' => 'new',
        'duration' => 30,
        'appointment_date' => now()->addDays(7)->format('Y-m-d'),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
        'patient_name' => $user->name,
        'patient_phone' => $user->phone,
        'amount' => 0,
        'total' => 0,
        'patient_confirmed_at' => now(),
    ]);

    $this->actingAs($doctor, 'doctor');

    Livewire::test('pages::doctor.appointments')
        ->assertSee(__('doctor.appointment_status.follow_up'), false);
});

test('doctor cancelling free follow up shows cancel without refund wording', function () {
    app()->setLocale('en');

    $doctor = seedDoctorWithSlots();
    $user = User::factory()->create();

    $parent = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'status' => 'completed',
        'duration' => 30,
        'patient_name' => $user->name,
        'appointment_date' => now()->format('Y-m-d'),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    $followUp = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'parent_id' => $parent->id,
        'is_follow_up' => true,
        'status' => 'new',
        'duration' => 30,
        'appointment_date' => now()->addDays(7)->format('Y-m-d'),
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
        'patient_name' => $user->name,
        'patient_phone' => $user->phone,
        'amount' => 0,
        'total' => 0,
        'patient_confirmed_at' => now(),
    ]);

    $this->actingAs($doctor, 'doctor');

    Livewire::test('pages::doctor.appointments')
        ->assertSee(__('doctor.appointments.cancel_appointment'), false)
        ->assertDontSee(__('doctor.appointments.cancel_refund'), false)
        ->call('promptCancelAppointment', $followUp->id)
        ->call('confirmCancelAppointment')
        ->assertHasNoErrors();

    expect($followUp->fresh()->status)->toBe('cancelled')
        ->and((float) $user->fresh()->balanceFloat)->toBe(0.0);
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
        'is_follow_up' => true,
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

test('follow up service rejects second follow up after first is booked', function () {
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
        'is_follow_up' => true,
        'status' => 'new',
        'duration' => 30,
        'appointment_date' => now()->addDays(7)->format('Y-m-d'),
        'start_time' => '11:00:00',
        'end_time' => '11:30:00',
        'amount' => 0,
        'total' => 0,
        'patient_confirmed_at' => now(),
    ]);

    $service = app(FollowUpAppointmentService::class);

    expect($service->parentCanScheduleFollowUp($parent))->toBeFalse();

    expect(fn () => $service->create($doctor, $parent, now()->addDays(8)->format('Y-m-d'), '10:00'))
        ->toThrow(ValidationException::class);
});

test('legacy doctor reschedule url redirects to follow up page', function () {
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

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.appointments.reschedule', $appointment))
        ->assertRedirect(route('doctor.appointments.follow-up', $appointment));
});

test('completed appointment workspace shows follow up tab not reschedule', function () {
    app()->setLocale('en');

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

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.appointments.follow-up', $appointment))
        ->assertSuccessful()
        ->assertSee(__('doctor.workspace.tab_follow_up'), false)
        ->assertSee(__('doctor.follow_up.free_hint'), false)
        ->assertSee(__('doctor.follow_up.option_schedule_title'), false)
        ->assertSee(__('doctor.follow_up.option_no_need_title'), false)
        ->assertDontSee(__('doctor.reschedule.title'), false);
});

test('doctor can mark follow up as not needed with toggle', function () {
    app()->setLocale('en');

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

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.appointment.follow-up', ['appointment' => $appointment])
        ->set('followUpNotNeeded', true)
        ->assertSet('followUpNotNeeded', true)
        ->assertSee(__('doctor.follow_up.no_need_title'), false);

    expect(app(FollowUpAppointmentService::class)->parentDeclinedFollowUp($appointment))->toBeTrue();

    $workflow = app(DoctorAppointmentWorkflow::class);
    $steps = collect($workflow->steps($appointment->fresh(), 'follow_up'));
    $followUpStep = $steps->firstWhere('key', 'follow_up');

    expect($followUpStep)->not->toBeNull()
        ->and($followUpStep['complete'])->toBeTrue();

    expect(app(FollowUpAppointmentService::class)->parentCanScheduleFollowUp($appointment->fresh()))->toBeFalse();
});

test('doctor cannot schedule follow up after marking not needed', function () {
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
    $service->markFollowUpNotNeeded($doctor, $parent);

    expect($service->parentCanScheduleFollowUp($parent))->toBeFalse();

    expect(fn () => $service->create($doctor, $parent, now()->addDay()->format('Y-m-d'), '10:00'))
        ->toThrow(ValidationException::class);
});

test('follow-up appointment page shows finished message instead of schedule form', function () {
    app()->setLocale('en');

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

    $followUp = Appointment::factory()->create([
        'doctor_id' => $doctor->id,
        'user_id' => $user->id,
        'parent_id' => $parent->id,
        'is_follow_up' => true,
        'status' => 'completed',
        'duration' => 30,
        'appointment_date' => now()->addDay()->format('Y-m-d'),
        'start_time' => '11:00:00',
        'end_time' => '11:30:00',
    ]);

    $this->actingAs($doctor, 'doctor')
        ->get(route('doctor.appointments.follow-up', $followUp))
        ->assertSuccessful()
        ->assertSee(__('doctor.follow_up.session_finished'), false)
        ->assertDontSee(__('doctor.follow_up.complete_session_first'), false)
        ->assertDontSee(__('doctor.follow_up.submit'), false);
});
