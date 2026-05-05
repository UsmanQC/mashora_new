<?php

use App\Models\Doctor;
use App\Models\Duration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest is redirected when visiting book appointments page', function () {
    $this->get('/patient/book-appointments/1?date=2026-05-05&time=12:15&duration=15')
        ->assertRedirect();
});

test('authenticated patient receives 404 when booking query params are incomplete', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    Duration::query()->create(['duration' => 15, 'title' => '15 min']);

    $doctor = Doctor::query()->create([
        'name' => 'Test Specialist',
        'name_ar' => 'اختبار',
        'status' => 'approved',
        'spoken_languages' => 'ar',
        'gender' => 'male',
    ]);

    $doctor->durations()->attach(15, ['price' => 100.0]);

    $this->actingAs($user)
        ->get(route('patient.book-appointments', ['doctor' => $doctor->id]).'?'.http_build_query([
            'date' => '2026-05-05',
        ]))
        ->assertNotFound();
});

test('authenticated patient receives 404 when duration is not offered for doctor', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    Duration::query()->create(['duration' => 15, 'title' => '15 min']);
    Duration::query()->create(['duration' => 30, 'title' => '30 min']);

    $doctor = Doctor::query()->create([
        'name' => 'Test Specialist',
        'name_ar' => 'اختبار',
        'status' => 'approved',
        'spoken_languages' => 'ar',
        'gender' => 'male',
    ]);

    $doctor->durations()->attach(15, ['price' => 100.0]);

    $this->actingAs($user)
        ->get(route('patient.book-appointments', ['doctor' => $doctor->id]).'?'.http_build_query([
            'date' => '2026-05-05',
            'time' => '12:15',
            'duration' => 30,
        ]))
        ->assertNotFound();
});

test('authenticated patient can load book appointments page with valid query', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    Duration::query()->create(['duration' => 15, 'title' => '15 min']);

    $doctor = Doctor::query()->create([
        'name' => 'Nada Alghamdi',
        'name_ar' => 'ندى الغامدي',
        'status' => 'approved',
        'spoken_languages' => 'ar_en',
        'gender' => 'female',
    ]);

    $doctor->durations()->attach(15, ['price' => 100.0]);

    $query = http_build_query([
        'date' => '2026-05-05',
        'time' => '12:15',
        'duration' => 15,
    ]);

    $this->actingAs($user)
        ->get(route('patient.book-appointments', ['doctor' => $doctor->id]).'?'.$query)
        ->assertSuccessful()
        ->assertSee(__('patient_booking.title'), false);
});
