<?php

use App\Models\Doctor;
use App\Models\Duration;
use App\Models\User;
use App\Support\PendingPatientBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createBookableDoctor(): Doctor
{
    Duration::query()->create(['duration' => 15, 'title' => '15 min']);

    $doctor = Doctor::query()->create([
        'name' => 'Nada Alghamdi',
        'name_ar' => 'ندى الغامدي',
        'status' => 'approved',
        'spoken_languages' => 'ar_en',
        'gender' => 'female',
    ]);

    $doctor->durations()->attach(15, ['price' => 100.0]);

    return $doctor;
}

function sampleBookingQuery(): string
{
    return http_build_query([
        'date' => '2026-05-05',
        'time' => '12:15',
        'duration' => 15,
    ]);
}

test('guest is redirected when visiting book appointments page', function () {
    $this->get('/patient/book-appointments/1?date=2026-05-05&time=12:15&duration=15')
        ->assertRedirect(route('patient.phone'));
});

test('guest booking attempt stores pending booking in session', function () {
    $doctor = createBookableDoctor();

    $this->get(route('patient.book-appointments', ['doctor' => $doctor->id]).'?'.sampleBookingQuery())
        ->assertRedirect(route('patient.phone'));

    expect(PendingPatientBooking::get())->toMatchArray([
        'doctor_id' => $doctor->id,
        'date' => '2026-05-05',
        'time' => '12:15',
        'duration' => 15,
    ]);
});

test('patient login resumes pending booking after guest slot selection', function () {
    $doctor = createBookableDoctor();

    $user = User::factory()->create([
        'phone' => '966500555666',
        'password' => 'password',
        'profile_completed' => true,
    ]);

    $this->get(route('patient.book-appointments', ['doctor' => $doctor->id]).'?'.sampleBookingQuery())
        ->assertRedirect(route('patient.phone'));

    $this->post(route('login.store'), [
        'patient_flow' => 1,
        'email' => $user->phone,
        'password' => 'password',
    ])->assertRedirect(
        route('patient.book-appointments', ['doctor' => $doctor->id]).'?'.sampleBookingQuery()
    );

    $this->assertAuthenticatedAs($user);
});

test('patient sign up resumes pending booking after guest slot selection', function () {
    $doctor = createBookableDoctor();
    $phone = '966512400099';

    PendingPatientBooking::store($doctor->id, '2026-05-05', '12:15', 15);
    session(['patient_otp_verified_phone' => $phone]);

    Livewire::withQueryParams(['phone' => $phone])
        ->test('pages::patient-auth.sign-up')
        ->set('name', 'Booking Patient')
        ->set('email', 'booking-patient@example.com')
        ->set('gender', 'female')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('registerPatient')
        ->assertHasNoErrors()
        ->assertRedirect(
            route('patient.book-appointments', ['doctor' => $doctor->id]).'?'.sampleBookingQuery()
        );
});

test('incomplete profile login keeps pending booking until profile is completed', function () {
    $doctor = createBookableDoctor();

    $user = User::factory()->create([
        'phone' => '966500777888',
        'password' => 'password',
        'profile_completed' => false,
    ]);

    $this->get(route('patient.book-appointments', ['doctor' => $doctor->id]).'?'.sampleBookingQuery())
        ->assertRedirect(route('patient.phone'));

    $this->post(route('login.store'), [
        'patient_flow' => 1,
        'email' => $user->phone,
        'password' => 'password',
    ])->assertRedirect(route('patient.profile.basic'));

    expect(PendingPatientBooking::get()['doctor_id'])->toBe($doctor->id);

    Livewire::actingAs($user)
        ->test('pages::patient-auth.profile-basic')
        ->set('gender', 'female')
        ->call('saveBasics')
        ->assertHasNoErrors()
        ->assertRedirect(
            route('patient.book-appointments', ['doctor' => $doctor->id]).'?'.sampleBookingQuery()
        );
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
        ->assertSee('data-test="patient-luxury-booking"', false)
        ->assertSee('data-test="patient-booking-header"', false)
        ->assertSee('data-test="patient-booking-step-intake"', false)
        ->assertSee('data-test="patient-navbar-language-switch"', false)
        ->assertSee(__('patient_booking.luxury.intake_title'), false)
        ->assertSee(__('patient_booking.for_self'), false)
        ->assertSee(__('patient_booking.for_other'), false)
        ->assertSee('data-test="patient-booking-continue"', false);
});

test('authenticated patient can select multiple communication methods on booking', function () {
    $user = User::factory()->create([
        'profile_completed' => true,
        'name' => 'Patient Test',
        'phone' => '966500111222',
    ]);

    Duration::query()->create(['duration' => 15, 'title' => '15 min']);

    $doctor = Doctor::factory()->create(['status' => 'approved']);
    $doctor->durations()->attach(15, ['price' => 200.0]);

    Livewire::actingAs($user)
        ->withQueryParams([
            'date' => '2026-05-05',
            'time' => '12:15',
            'duration' => 15,
        ])
        ->test('pages::patient.book-appointments', ['doctor' => $doctor])
        ->assertSet('bookingChannels', ['chat', 'video', 'voice'])
        ->call('toggleCommunication', 'video')
        ->assertSet('bookingChannels', ['chat', 'voice'])
        ->call('toggleCommunication', 'voice')
        ->assertSet('bookingChannels', ['chat', 'video', 'voice']);
});

test('authenticated patient can advance booking to payment summary step on mobile', function () {
    $user = User::factory()->create([
        'profile_completed' => true,
        'name' => 'Patient Test',
        'phone' => '966500111222',
    ]);

    Duration::query()->create(['duration' => 15, 'title' => '15 min']);

    $doctor = Doctor::query()->create([
        'name' => 'Nada Alghamdi',
        'name_ar' => 'ندى الغامدي',
        'status' => 'approved',
        'spoken_languages' => 'ar_en',
        'gender' => 'female',
    ]);

    $doctor->durations()->attach(15, ['price' => 200.0]);

    Livewire::actingAs($user)
        ->withQueryParams([
            'date' => '2026-05-05',
            'time' => '12:15',
            'duration' => 15,
        ])
        ->test('pages::patient.book-appointments', ['doctor' => $doctor])
        ->call('goToSummaryStep')
        ->assertSet('mobileStep', 2)
        ->assertSee(__('patient_booking.luxury.summary_title'), false)
        ->assertSee('data-test="patient-booking-step-summary"', false)
        ->assertSee('data-test="patient-booking-confirm-pay"', false);
});
