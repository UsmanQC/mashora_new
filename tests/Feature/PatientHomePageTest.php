<?php

use App\Livewire\PatientMoodPickerModal;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guest can view patient home', function () {
    $this->get(route('patient.home'))
        ->assertSuccessful()
        ->assertSee(__('patient.mood_section'), false);
});

test('authenticated patient home shows mood strip above spotlight cards', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    $content = $this->actingAs($user)
        ->get(route('patient.home'))
        ->assertSuccessful()
        ->getContent();

    expect(strpos($content, __('patient.mood_section')))
        ->toBeLessThan(strpos($content, __('patient.daily_balance')));
});

test('guest mood day click redirects to patient phone entry', function () {
    Livewire::test('pages::patient.home')
        ->call('selectMoodDay')
        ->assertRedirect(route('patient.phone'));
});

test('guest is redirected to patient phone from patient appointments', function () {
    $this->get(route('patient.appointments'))
        ->assertRedirect(route('patient.phone'));
});

test('guest is redirected to patient phone from important numbers', function () {
    $this->get(route('patient.important-numbers'))
        ->assertRedirect(route('patient.phone'));
});

test('authenticated patient navbar shows language switch', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    $this->actingAs($user)->get(route('patient.home'))
        ->assertSuccessful()
        ->assertSee('data-test="patient-navbar-language-switch"', false)
        ->assertSee(route('patient.locale', ['locale' => 'en']), false)
        ->assertSee(route('patient.locale', ['locale' => 'ar']), false)
        ->assertSee(__('patient.menu.locale_en'), false)
        ->assertSee(__('patient.menu.locale_ar_short'), false);
});

test('authenticated patient sidebar shows dock navigation links', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    $response = $this->actingAs($user)->get(route('patient.home'));

    $response->assertSuccessful()
        ->assertSee(__('patient.nav.home'), false)
        ->assertSee(__('patient.nav.appointments'), false)
        ->assertSee(route('patient.menu'), false);
});

test('authenticated patient home renders arabic strings when locale is ar', function () {
    app()->setLocale('ar');
    $user = User::factory()->create([
        'name' => 'User Example',
        'profile_completed' => true,
    ]);

    $this->actingAs($user)->get(route('patient.home'))
        ->assertSuccessful()
        ->assertSee(__('patient.portal_greeting', ['name' => 'User']), false);
});

test('mood week strip opens mood picker for authenticated patients', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    $this->actingAs($user)
        ->get(route('patient.home'))
        ->assertSuccessful()
        ->assertSee('wire:click="selectMoodDay"', false);
});

test('clicking mood day on home dispatches open patient mood picker event for authenticated patients', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test('pages::patient.home')
        ->call('selectMoodDay')
        ->assertDispatched('open-patient-mood-picker');
});

test('saving mood from modal refreshes home mood week strip', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test(PatientMoodPickerModal::class)
        ->set('showMoodModal', true)
        ->call('setMood', 'happy')
        ->call('saveMood')
        ->assertDispatched('patient-mood-saved');
});

test('signed-in patient home links both session cards to schedule filter', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $filterUrl = route('patient.schedule.filter');

    $response = $this->actingAs($user)->get(route('patient.home'))->assertSuccessful();

    expect(substr_count($response->content(), $filterUrl))->toBe(2);
});

test('signed-in patient navbar exposes account menu with logout', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    $this->actingAs($user)->get(route('patient.home'))
        ->assertSuccessful()
        ->assertSee('data-test="patient-account-menu-button"', false)
        ->assertSee('data-test="patient-logout-button"', false)
        ->assertSee(route('logout'), false)
        ->assertSee(route('profile.edit'), false);
});

test('patient ongoing appointment shows countdown timer next to waiting status', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();
    $startsAt = now()->addHours(3);

    Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'patient_name' => 'Patient Test',
        'status' => 'new',
        'appointment_date' => $startsAt->toDateString(),
        'start_time' => $startsAt->format('H:i:s'),
        'scheduled_at' => $startsAt,
    ]);

    $this->actingAs($user)
        ->get(route('patient.appointments'))
        ->assertSuccessful()
        ->assertSee(__('patient.appointments.status_new'), false)
        ->assertSee('appointmentStartTimer', false)
        ->assertSee($startsAt->toIso8601String(), false);
});

test('patient appointments ongoing tab shows only new and in process for authenticated user', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $otherUser = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();

    Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'patient_name' => 'Patient Ongoing New',
        'status' => 'new',
    ]);

    Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'patient_name' => 'Patient Ongoing In Process',
        'status' => 'in_process',
    ]);

    Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'patient_name' => 'Patient Completed Hidden',
        'status' => 'completed',
    ]);

    Appointment::factory()->create([
        'user_id' => $otherUser->id,
        'doctor_id' => $doctor->id,
        'patient_name' => 'Other User Ongoing Hidden',
        'status' => 'new',
    ]);

    $this->actingAs($user)->get(route('patient.appointments'))
        ->assertSuccessful()
        ->assertSee('Patient Ongoing New', false)
        ->assertSee('Patient Ongoing In Process', false)
        ->assertDontSee('Patient Completed Hidden', false)
        ->assertDontSee('Other User Ongoing Hidden', false);
});

test('patient appointments rescheduled tab shows only rescheduled for authenticated user', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();

    Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'patient_name' => 'Patient Rescheduled Visible',
        'status' => 'rescheduled',
    ]);

    Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'patient_name' => 'Patient Ongoing Hidden',
        'status' => 'new',
    ]);

    $this->actingAs($user)->get(route('patient.appointments', ['tab' => 'rescheduled']))
        ->assertSuccessful()
        ->assertSee('Patient Rescheduled Visible', false)
        ->assertDontSee('Patient Ongoing Hidden', false);
});

test('patient appointments cancelled tab shows only cancelled for authenticated user', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();

    Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'patient_name' => 'Patient Cancelled Visible',
        'status' => 'cancelled',
    ]);

    Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'patient_name' => 'Patient Ongoing Hidden',
        'status' => 'new',
    ]);

    $this->actingAs($user)->get(route('patient.appointments', ['tab' => 'cancelled']))
        ->assertSuccessful()
        ->assertSee('Patient Cancelled Visible', false)
        ->assertDontSee('Patient Ongoing Hidden', false);
});

test('patient appointments completed tab shows only completed for authenticated user', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();

    Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'patient_name' => 'Patient Ongoing Hidden',
        'status' => 'in_process',
    ]);

    Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'patient_name' => 'Patient Completed Visible',
        'status' => 'completed',
    ]);

    $this->actingAs($user)->get(route('patient.appointments', ['tab' => 'completed']))
        ->assertSuccessful()
        ->assertSee('Patient Completed Visible', false)
        ->assertDontSee('Patient Ongoing Hidden', false);
});

test('patient appointments show join session link after doctor starts', function () {
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
        ->assertSee(route('patient.appointments.conversation', ['appointment' => $appointment->id]), false)
        ->assertSee(__('patient.appointments.join_session'), false);
});

test('patient appointments renders realtime notification scripts', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();

    Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'status' => 'new',
    ]);

    $this->actingAs($user)
        ->get(route('patient.appointments'))
        ->assertSuccessful()
        ->assertSee('patient-appointments-realtime-bootstrap', false)
        ->assertSee('https://js.pusher.com/8.2.0/pusher.min.js', false);
});

test('patient can open own appointment conversation and cannot open others', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $otherUser = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();

    $mine = Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'status' => 'new',
    ]);

    $other = Appointment::factory()->create([
        'user_id' => $otherUser->id,
        'doctor_id' => $doctor->id,
        'status' => 'new',
    ]);

    $this->actingAs($user)
        ->get(route('patient.appointments.conversation', ['appointment' => $mine->id]))
        ->assertSuccessful()
        ->assertSee(__('patient.appointments.type_message'), false);

    $this->actingAs($user)
        ->get(route('patient.appointments.conversation', ['appointment' => $other->id]))
        ->assertForbidden();
});
