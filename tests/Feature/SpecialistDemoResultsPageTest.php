<?php

use App\Models\Degree;
use App\Models\User;
use App\Support\SpecialistCatalog;
use Database\Seeders\CommunicationSeeder;
use Database\Seeders\DegreeSeeder;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\DurationSeeder;
use Database\Seeders\SpecialitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SpecialitySeeder::class);
    $this->seed(DegreeSeeder::class);
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
        ->assertSee('data-test="patient-luxury-specialists"', false)
        ->assertSee('data-test="patient-specialists-header"', false)
        ->assertSee('data-test="patient-navbar-language-switch"', false)
        ->assertSee(__('specialist_results.page_heading'), false)
        ->assertSee(__('specialist_results.page_sub_default'), false)
        ->assertSee('Nada Alghamdi', false)
        ->assertSee('Dr. Khalid Mohammed', false);
});

test('seeded doctors remain visible when session filter requests thirty minute sessions', function () {
    app()->setLocale('en');

    $this->seed([
        DurationSeeder::class,
        CommunicationSeeder::class,
        DoctorSeeder::class,
    ]);

    $user = User::factory()->create(['profile_completed' => true]);

    session()->put('session_filter_preferences', [
        'degree_id' => '',
        'gender_preference' => 'both',
        'duration_minutes' => '30',
        'language_preference' => 'both',
        'subspecialties' => [],
    ]);

    $filtered = SpecialistCatalog::filtered(session('session_filter_preferences'));

    expect($filtered)->toHaveCount(DoctorSeeder::SEEDED_DOCTOR_COUNT);

    $this->actingAs($user)
        ->get(route('patient.schedule.specialists'))
        ->assertSuccessful()
        ->assertSee('Dr. Test Doctor', false)
        ->assertSee('Dr. Nada Alghamdi', false)
        ->assertSee('Dr. Amira Zayed', false);
});

test('session filter preferences narrow which specialists are listed', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['profile_completed' => true]);
    $specialistDegreeId = (string) Degree::query()
        ->where('title', 'Doctor (Specialist)')
        ->value('id');

    session()->put('session_filter_preferences', [
        'specialist_role' => $specialistDegreeId,
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
    $specialistDegreeId = (string) Degree::query()
        ->where('title', 'Doctor (Specialist)')
        ->value('id');

    session()->put('session_filter_preferences', [
        'specialist_role' => $specialistDegreeId,
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
