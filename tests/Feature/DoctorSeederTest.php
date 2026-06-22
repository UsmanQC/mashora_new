<?php

use App\Models\Doctor;
use App\Models\Duration;
use Database\Seeders\CommunicationSeeder;
use Database\Seeders\DegreeSeeder;
use Database\Seeders\DoctorSeeder;
use Database\Seeders\DurationSeeder;
use Database\Seeders\SpecialitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        SpecialitySeeder::class,
        DegreeSeeder::class,
        DurationSeeder::class,
        CommunicationSeeder::class,
    ]);
});

test('duration seeder is idempotent', function () {
    $this->seed(DurationSeeder::class);
    $this->seed(DurationSeeder::class);

    expect(Duration::query()->count())->toBe(4);
});

test('doctor seeder creates ten profile doctors', function () {
    $this->seed(DoctorSeeder::class);

    expect(Doctor::query()->count())->toBe(DoctorSeeder::SEEDED_DOCTOR_COUNT)
        ->and(Doctor::query()->whereIn('phone', DoctorSeeder::profileDoctorPhones())->count())
        ->toBe(DoctorSeeder::SEEDED_DOCTOR_COUNT);
});

test('doctor seeder gives every profile doctor all specialities', function () {
    $this->seed(DoctorSeeder::class);

    $expectedSpecialityIds = DoctorSeeder::allSpecialityIds();

    foreach (DoctorSeeder::profileDoctorPhones() as $phone) {
        $doctor = Doctor::query()->where('phone', $phone)->first();

        expect($doctor)->not->toBeNull()
            ->and($doctor->specialities()->count())->toBe(DoctorSeeder::SEEDED_DOCTOR_SPECIALITY_COUNT)
            ->and($doctor->specialities()->pluck('specialities.id')->sort()->values()->all())
            ->toBe($expectedSpecialityIds)
            ->and($doctor->speciality_id)->toBe(DoctorSeeder::TEST_DOCTOR_PRIMARY_SPECIALITY_ID);
    }

    expect(DB::table('doctor_speciality')
        ->whereIn('doctor_id', Doctor::query()->whereIn('phone', DoctorSeeder::profileDoctorPhones())->pluck('id'))
        ->count())
        ->toBe(DoctorSeeder::SEEDED_DOCTOR_COUNT * DoctorSeeder::SEEDED_DOCTOR_SPECIALITY_COUNT);
});

test('doctor seeder creates a known test doctor with portal relations', function () {
    $this->seed(DoctorSeeder::class);

    $doctor = Doctor::query()->where('phone', DoctorSeeder::TEST_DOCTOR_PHONE)->first();

    expect($doctor)->not->toBeNull()
        ->and($doctor->status)->toBe('approved')
        ->and($doctor->profile_completed)->toBeTrue()
        ->and($doctor->degree_id)->not->toBeNull()
        ->and($doctor->speciality_id)->toBe(DoctorSeeder::TEST_DOCTOR_PRIMARY_SPECIALITY_ID)
        ->and($doctor->specialities()->count())->toBe(DoctorSeeder::SEEDED_DOCTOR_SPECIALITY_COUNT)
        ->and($doctor->durations()->count())->toBeGreaterThanOrEqual(2)
        ->and($doctor->communications()->count())->toBe(2)
        ->and($doctor->workingDays()->where('is_working', true)->count())->toBe(7);
});

test('seeded test doctor can sign in to the doctor portal', function () {
    $this->seed(DoctorSeeder::class);

    Livewire::withQueryParams(['phone' => DoctorSeeder::TEST_DOCTOR_PHONE])
        ->test('pages::doctor.login')
        ->set('password', DoctorSeeder::TEST_DOCTOR_PASSWORD)
        ->call('login')
        ->assertRedirect(route('doctor.dashboard'));

    expect(Auth::guard('doctor')->check())->toBeTrue();
});

test('doctor seeder is idempotent', function () {
    $this->seed(DoctorSeeder::class);
    $this->seed(DoctorSeeder::class);

    expect(Doctor::query()->count())->toBe(DoctorSeeder::SEEDED_DOCTOR_COUNT)
        ->and(Doctor::query()->where('phone', DoctorSeeder::TEST_DOCTOR_PHONE)->count())->toBe(1);

    foreach (DoctorSeeder::profileDoctorPhones() as $phone) {
        $doctor = Doctor::query()->where('phone', $phone)->first();

        expect($doctor->specialities()->count())->toBe(DoctorSeeder::SEEDED_DOCTOR_SPECIALITY_COUNT);
    }
});
