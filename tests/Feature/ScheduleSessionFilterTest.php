<?php

use App\Models\User;
use Database\Seeders\DegreeSeeder;
use Database\Seeders\SpecialitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        SpecialitySeeder::class,
        DegreeSeeder::class,
    ]);
});

test('guest can open schedule session filter page', function () {
    $this->get(route('patient.schedule.filter'))
        ->assertSuccessful()
        ->assertSee('session-filter-mobile', false)
        ->assertSee('data-test="patient-schedule-filter-header"', false)
        ->assertSee(__('session_filter.mobile_steps.degree'), false)
        ->assertSee(__('session_filter.step_of', ['current' => 1, 'total' => 5]), false);
});

test('mobile filter header updates when step changes', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test('pages::patient.schedule-session')
        ->assertSee(__('session_filter.mobile_steps.degree'), false)
        ->set('mobileStep', 3)
        ->assertSee(__('session_filter.mobile_steps.duration'), false)
        ->assertSee(__('session_filter.step_of', ['current' => 3, 'total' => 5]), false)
        ->assertDontSee(__('session_filter.mobile_steps.degree'), false);
});

test('mobile filter swipe back goes to previous step before leaving', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test('pages::patient.schedule-session')
        ->set('mobileStep', 2)
        ->call('goBackMobile')
        ->assertSet('mobileStep', 1);
});

test('mobile filter swipe back on first step redirects guest home', function () {
    Livewire::test('pages::patient.schedule-session')
        ->call('goBackMobile')
        ->assertRedirect(route('home'));
});

test('authenticated patient missing profile basics is redirected from schedule filter', function () {
    $user = User::factory()->create(['profile_completed' => false]);

    $this->actingAs($user)->get(route('patient.schedule.filter'))
        ->assertRedirect(route('patient.profile.basic'));
});

test('authenticated patient can open schedule session filter page', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['profile_completed' => true]);

    $this->actingAs($user)->get(route('patient.schedule.filter'))
        ->assertSuccessful()
        ->assertSee('data-test="patient-schedule-filter-header"', false)
        ->assertSee('data-test="patient-navbar-language-switch"', false)
        ->assertSee(__('session_filter.mobile_steps.degree'), false)
        ->assertSee(__('session_filter.subtitle'), false)
        ->assertSee(__('session_filter.filter_heading'), false)
        ->assertSee('session-filter-chip', false)
        ->assertSee('Obsessive', false)
        ->assertSee('Substance Abuse', false)
        ->assertDontSee('Anorexia', false);
});

test('schedule session filter starts with no preselected options', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test('pages::patient.schedule-session')
        ->assertSet('degree_id', '')
        ->assertSet('genderPreference', '')
        ->assertSet('durationMinutes', '')
        ->assertSet('languagePreference', '');
});

test('patient must choose all required filters before continuing', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test('pages::patient.schedule-session')
        ->call('proceedNext')
        ->assertHasErrors([
            'degree_id' => 'required',
            'genderPreference' => 'required',
            'durationMinutes' => 'required',
            'languagePreference' => 'required',
        ]);
});

test('patient can continue after choosing all required filters', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test('pages::patient.schedule-session')
        ->set('degree_id', '1')
        ->set('genderPreference', 'both')
        ->set('durationMinutes', '30')
        ->set('languagePreference', 'both')
        ->call('proceedNext')
        ->assertRedirect(route('patient.schedule.specialists'));

    expect(session('session_filter_preferences'))->toMatchArray([
        'degree_id' => '1',
        'gender_preference' => 'both',
        'duration_minutes' => '30',
        'language_preference' => 'both',
        'subspecialties' => [],
    ]);
});

test('mobile wizard auto advances when selecting an option', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test('pages::patient.schedule-session')
        ->call('selectMobileDegree', '1')
        ->assertSet('mobileStep', 2)
        ->assertSet('degree_id', '1')
        ->call('selectMobileGender', 'both')
        ->assertSet('mobileStep', 3);
});

test('mobile wizard advances step by step when required fields are chosen', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test('pages::patient.schedule-session')
        ->assertSet('mobileStep', 1)
        ->call('goToNextMobileStep')
        ->assertHasErrors(['degree_id'])
        ->set('degree_id', '1')
        ->call('goToNextMobileStep')
        ->assertSet('mobileStep', 2)
        ->set('genderPreference', 'both')
        ->call('goToNextMobileStep')
        ->assertSet('mobileStep', 3)
        ->set('durationMinutes', '30')
        ->call('goToNextMobileStep')
        ->assertSet('mobileStep', 4)
        ->set('languagePreference', 'both')
        ->call('goToNextMobileStep')
        ->assertSet('mobileStep', 5);
});

test('mobile wizard can finish from subspecialties step', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test('pages::patient.schedule-session')
        ->set('degree_id', '1')
        ->set('genderPreference', 'both')
        ->set('durationMinutes', '30')
        ->set('languagePreference', 'both')
        ->set('mobileStep', 5)
        ->call('goToNextMobileStep')
        ->assertRedirect(route('patient.schedule.specialists'));
});

test('schedule filter page includes mobile wizard markup', function () {
    app()->setLocale('ar');

    $user = User::factory()->create(['profile_completed' => true]);

    $this->actingAs($user)->get(route('patient.schedule.filter'))
        ->assertSuccessful()
        ->assertSee('session-filter-mobile', false)
        ->assertSee('session-filter-mobile-option', false)
        ->assertSee(__('session_filter.step_of', ['current' => 1, 'total' => 5]), false);
});
