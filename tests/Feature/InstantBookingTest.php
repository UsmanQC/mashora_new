<?php

use App\Models\Doctor;
use App\Models\User;
use App\Services\DoctorAvailabilityService;
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

test('instant availability only includes slots starting within the configured window', function () {
    $this->travelTo(now()->startOfDay()->addHours(10)->addMinutes(5));

    $doctor = Doctor::query()->where('name', 'Dr. Test Doctor')->firstOrFail();

    /** @var DoctorAvailabilityService $availability */
    $availability = app(DoctorAvailabilityService::class);

    $instantSlots = $availability->availableSlotsWithinInstantWindow($doctor, 30);
    $todaySlots = $availability->availableSlots($doctor, now()->toDateString(), 30);

    expect($instantSlots)->not->toBeEmpty();
    expect($todaySlots)->not->toBeEmpty();
    expect(count($instantSlots))->toBeLessThan(count($todaySlots));
    expect($instantSlots)->each->toBeIn($todaySlots);
});

test('instant specialists page only lists doctors with slots in the next hour', function () {
    app()->setLocale('en');
    $this->travelTo(now()->startOfDay()->addHours(10)->addMinutes(5));

    session()->put('session_filter_preferences', [
        'degree_id' => '',
        'gender_preference' => 'both',
        'duration_minutes' => '30',
        'language_preference' => 'both',
        'subspecialties' => [],
        'instant_booking' => true,
    ]);
    session()->put('instant_booking', true);

    Livewire::test('pages::patient.schedule-specialists')
        ->assertSet('instantBooking', true)
        ->assertSet('selectedDate', now()->toDateString())
        ->assertSee(__('specialist_results.page_heading_instant'), false)
        ->assertSee(__('specialist_results.instant_window_hint', ['minutes' => 60]), false)
        ->assertSee('Dr. Test Doctor', false);
});

test('instant specialists page reads instant flag from query string when preferences exist', function () {
    app()->setLocale('en');
    $this->travelTo(now()->startOfDay()->addHours(10)->addMinutes(5));

    session()->put('session_filter_preferences', [
        'degree_id' => '1',
        'gender_preference' => 'both',
        'duration_minutes' => '30',
        'language_preference' => 'both',
        'subspecialties' => [],
        'instant_booking' => true,
    ]);
    session()->put('instant_booking', true);

    Livewire::withQueryParams(['instant' => '1'])
        ->test('pages::patient.schedule-specialists')
        ->assertSet('instantBooking', true)
        ->assertSee(__('specialist_results.page_heading_instant'), false);
});

test('instant specialists without preferences redirects to filter', function () {
    $this->get(route('patient.schedule.instant'))
        ->assertRedirect(route('patient.schedule.filter', ['instant' => 1]));
});

test('instant specialists direct link activates instant mode after filter', function () {
    app()->setLocale('en');
    $this->travelTo(now()->startOfDay()->addHours(10)->addMinutes(5));

    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test('pages::patient.schedule-session')
        ->set('instantBooking', true)
        ->set('degree_id', '1')
        ->set('genderPreference', 'both')
        ->set('durationMinutes', '30')
        ->set('languagePreference', 'both')
        ->call('proceedNext')
        ->assertRedirect(route('patient.schedule.instant'));

    $this->actingAs($user)
        ->get(route('patient.schedule.instant'))
        ->assertSuccessful()
        ->assertSee(__('specialist_results.page_heading_instant'), false)
        ->assertDontSee(__('specialist_results.page_heading'), false);

    expect(session('instant_booking'))->toBeTrue();
});

test('instant specialists before working hours hide doctors outside the window', function () {
    app()->setLocale('en');
    $this->travelTo(now()->startOfDay()->addHours(1)->addMinutes(24));

    session()->put('session_filter_preferences', [
        'degree_id' => '1',
        'gender_preference' => 'both',
        'duration_minutes' => '30',
        'language_preference' => 'both',
        'subspecialties' => [],
        'instant_booking' => true,
    ]);
    session()->put('instant_booking', true);

    $this->get(route('patient.schedule.instant'))
        ->assertSuccessful()
        ->assertSee(__('specialist_results.page_heading_instant'), false)
        ->assertSee(__('specialist_results.no_results_title'), false);
});

test('schedule filter with instant query flag stores instant booking session', function () {
    $this->get(route('patient.schedule.filter', ['instant' => 1]))
        ->assertSuccessful();

    expect(session('instant_booking'))->toBeTrue();
});

test('instant filter redirect includes instant query on specialists page', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    Livewire::actingAs($user)
        ->test('pages::patient.schedule-session')
        ->set('instantBooking', true)
        ->set('degree_id', '1')
        ->set('genderPreference', 'both')
        ->set('durationMinutes', '30')
        ->set('languagePreference', 'both')
        ->call('proceedNext')
        ->assertRedirect(route('patient.schedule.instant'));
});
