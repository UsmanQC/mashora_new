<?php

use App\Models\User;
use Database\Seeders\SpecialitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SpecialitySeeder::class);
});

test('guest cannot open specialist results', function () {
    $this->get(route('patient.schedule.specialists'))
        ->assertRedirect();
});

test('patient with completed profile sees catalog specialists when no session filters', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['profile_completed' => true]);

    $this->actingAs($user)
        ->get(route('patient.schedule.specialists'))
        ->assertSuccessful()
        ->assertSee(__('specialist_results.page_heading'), false)
        ->assertSee('Nada Alghamdi', false)
        ->assertSee('Dr. Khalid Mohammed', false);
});

test('session filter preferences narrow which specialists are listed', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['profile_completed' => true]);

    session()->put('session_filter_preferences', [
        'specialist_role' => 'psychiatrist',
        'gender_preference' => 'male',
        'duration_minutes' => '15',
        'language_preference' => 'both',
        'subspecialties' => [],
    ]);

    $this->actingAs($user)
        ->get(route('patient.schedule.specialists'))
        ->assertSuccessful()
        ->assertSee('Dr. Khalid Mohammed', false)
        ->assertDontSee('Dr. Fatima Noor', false)
        ->assertDontSee('Nada Alghamdi', false);
});

test('specialists page shows empty state when no catalog entry matches filters', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['profile_completed' => true]);

    session()->put('session_filter_preferences', [
        'specialist_role' => 'psychiatrist',
        'gender_preference' => 'both',
        'duration_minutes' => '15',
        'language_preference' => 'both',
        'subspecialties' => ['26'],
    ]);

    $this->actingAs($user)
        ->get(route('patient.schedule.specialists'))
        ->assertSuccessful()
        ->assertSee(__('specialist_results.no_results_title'), false)
        ->assertSee(__('specialist_results.adjust_filters'), false);
});
