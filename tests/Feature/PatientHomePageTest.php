<?php

use App\Livewire\PatientMoodPickerModal;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('guest is redirected to patient phone from patient home', function () {
    $this->get(route('patient.home'))
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

test('mood week strip opens mood picker instead of linking to phone entry', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    $this->actingAs($user)
        ->get(route('patient.home'))
        ->assertSuccessful()
        ->assertSee('wire:click="openMoodPicker"', false)
        ->assertDontSee(__('patient.mood_strip_phone_link_aria'), false);
});

test('clicking mood day on home dispatches open patient mood picker event', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test('pages::patient.home')
        ->call('openMoodPicker')
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

test('signed-in patient layout exposes account menu logout control', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    $this->actingAs($user)->get(route('patient.home'))
        ->assertSuccessful()
        ->assertSee('data-test="patient-logout-button"', false)
        ->assertSee(route('logout'), false);
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

test('patient appointments show start session link for ongoing records', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::factory()->create();

    $appointment = Appointment::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'status' => 'in_process',
    ]);

    $this->actingAs($user)->get(route('patient.appointments'))
        ->assertSuccessful()
        ->assertSee(route('patient.appointments.conversation', ['appointment' => $appointment->id]), false)
        ->assertSee(__('patient.appointments.start_session'), false);
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
