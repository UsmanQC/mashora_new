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

test('guest cannot open schedule session filter page', function () {
    $this->get(route('patient.schedule.filter'))
        ->assertRedirect();
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
        ->assertSee(__('session_filter.title'), false)
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
