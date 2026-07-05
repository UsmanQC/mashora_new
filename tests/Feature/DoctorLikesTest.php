<?php

use App\Models\Doctor;
use App\Models\Like;
use App\Models\User;
use App\Support\SpecialistCatalog;
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

    session()->put('session_filter_preferences', [
        'degree_id' => '',
        'gender_preference' => 'both',
        'duration_minutes' => '30',
        'language_preference' => 'both',
        'subspecialties' => [],
    ]);
});

test('guest toggling like redirects to patient phone login', function () {
    app()->setLocale('en');

    Livewire::test('pages::patient.schedule-specialists')
        ->call('toggleLike', 'doctor-1')
        ->assertRedirect(route('patient.phone'));
});

test('authenticated patient can like a specialist once and unlike again', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::query()->where('name', 'Dr. Test Doctor')->firstOrFail();
    $cardId = 'doctor-'.$doctor->id;

    Livewire::actingAs($user)
        ->test('pages::patient.schedule-specialists')
        ->assertSet('likeCounts.'.$cardId, 0)
        ->assertSet('likedByUser.'.$cardId, false)
        ->call('toggleLike', $cardId)
        ->assertSet('likeCounts.'.$cardId, 1)
        ->assertSet('likedByUser.'.$cardId, true);

    expect(Like::query()->where('user_id', $user->id)->where('doctor_id', $doctor->id)->exists())->toBeTrue();

    Livewire::actingAs($user)
        ->test('pages::patient.schedule-specialists')
        ->call('toggleLike', $cardId)
        ->assertSet('likeCounts.'.$cardId, 0)
        ->assertSet('likedByUser.'.$cardId, false);

    expect(Like::query()->where('user_id', $user->id)->where('doctor_id', $doctor->id)->exists())->toBeFalse();
});

test('liked specialist card renders a solid green heart', function () {
    app()->setLocale('en');

    $doctor = Doctor::query()->where('name', 'Dr. Test Doctor')->firstOrFail();
    $doctor->update(['profile_photo_path' => 'doctors/test-profile.jpg']);

    $specialist = collect(SpecialistCatalog::all())
        ->first(fn (array $card): bool => ($card['id'] ?? '') === 'doctor-'.$doctor->id);

    $html = view('partials.patient-specialist-result-card', [
        'specialist' => $specialist,
        'likes' => 1,
        'likedByUser' => true,
    ])->render();

    expect($html)->toContain('aria-pressed="true"');
    expect($html)->toContain('fill="currentColor"');
    expect($html)->toContain('text-[#10B981]');
});

test('doctor dashboard shows total favourites count', function () {
    app()->setLocale('en');

    $doctor = Doctor::query()->where('name', 'Dr. Test Doctor')->firstOrFail();
    $doctor->update(['status' => 'approved', 'profile_completed' => true]);
    $patient = User::factory()->create(['profile_completed' => true]);

    Like::factory()->create([
        'user_id' => $patient->id,
        'doctor_id' => $doctor->id,
    ]);

    Livewire::actingAs($doctor, 'doctor')
        ->test('pages::doctor.dashboard')
        ->assertSet('likesCount', 1);
});

test('favorites page lists doctors the patient has liked', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['profile_completed' => true]);
    $doctor = Doctor::query()->where('name', 'Dr. Test Doctor')->firstOrFail();

    Like::factory()->create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
    ]);

    $this->actingAs($user)
        ->get(route('patient.favorites'))
        ->assertSuccessful()
        ->assertSee('data-test="patient-favorites-list"', false)
        ->assertSee('Dr. Test Doctor', false)
        ->assertDontSee('data-test="patient-favorites-empty"', false);
});

test('favorites page shows empty state when patient has no likes', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['profile_completed' => true]);

    $this->actingAs($user)
        ->get(route('patient.favorites'))
        ->assertSuccessful()
        ->assertSee('data-test="patient-favorites-empty"', false)
        ->assertSee(__('patient.menu.favorites_empty_title'), false);
});
