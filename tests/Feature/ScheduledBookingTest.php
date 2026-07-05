<?php

use App\Models\User;
use Database\Seeders\CommunicationSeeder;
use Database\Seeders\DegreeSeeder;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\DurationSeeder;
use Database\Seeders\SpecialitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        SpecialitySeeder::class,
        DegreeSeeder::class,
        DurationSeeder::class,
        CommunicationSeeder::class,
        DoctorSeeder::class,
    ]);
});

test('guest can open scheduled session filter', function () {
    app()->setLocale('en');

    $this->get(route('patient.schedule.filter'))
        ->assertSuccessful()
        ->assertSee(__('session_filter.scheduled_title'), false)
        ->assertSee(__('session_filter.scheduled_subtitle'), false);
});

test('scheduled specialists without preferences redirects to filter', function () {
    $this->get(route('patient.schedule.specialists'))
        ->assertRedirect(route('patient.schedule.filter'));
});

test('scheduled filter flow shows specialists with date picker after preferences', function () {
    app()->setLocale('en');
    $this->travelTo(now()->startOfDay()->addHours(10)->addMinutes(5));

    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test('pages::patient.schedule-session')
        ->set('degree_id', '1')
        ->set('genderPreference', 'both')
        ->set('durationMinutes', '30')
        ->set('languagePreference', 'both')
        ->call('proceedNext')
        ->assertRedirect(route('patient.schedule.specialists'));

    $this->actingAs($user)
        ->get(route('patient.schedule.specialists'))
        ->assertSuccessful()
        ->assertSee(__('specialist_results.page_heading'), false)
        ->assertDontSee(__('specialist_results.page_heading_instant'), false)
        ->assertSee(__('specialist_results.today_short'), false);

    expect(session('instant_booking'))->toBeNull();
});

test('marketing scheduled session card links to filter for guests', function () {
    app()->setLocale('ar');

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee(route('patient.schedule.filter'), false)
        ->assertSee(__('patient.book_title'), false);
});
