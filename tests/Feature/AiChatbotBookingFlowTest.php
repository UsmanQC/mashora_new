<?php

use App\Models\Doctor;
use App\Models\Speciality;
use App\Services\AiChatbot\AiChatbotBookingFlowService;
use App\Services\AiChatbot\AiChatbotToolManager;
use App\Services\DoctorAvailabilityService;
use App\Support\PendingPatientBooking;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('booking step endpoint returns degree options', function () {
    $response = $this->postJson(route('api.chat.booking.step'), [
        'step' => 'degree',
        'locale' => 'ar',
        'preferences' => [],
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'step',
            'prompt',
            'mode',
            'options',
            'next_step',
        ])
        ->assertJson([
            'step' => 'degree',
            'mode' => 'single',
            'next_step' => 'speciality',
        ]);

    expect(collect($response->json('options')))->not->toBeEmpty();
});

test('booking step returns specialities from database', function () {
    Speciality::query()->create([
        'title' => 'Anxiety',
        'title_ar' => 'القلق',
        'status' => true,
    ]);

    $this->postJson(route('api.chat.booking.step'), [
        'step' => 'speciality',
        'locale' => 'ar',
        'preferences' => ['degree_id' => '1'],
    ])
        ->assertSuccessful()
        ->assertJson([
            'step' => 'speciality',
            'mode' => 'multi',
            'allow_skip' => true,
        ])
        ->assertJsonFragment(['label' => 'القلق']);
});

test('booking step returns filtered doctors', function () {
    Doctor::factory()->create([
        'status' => 'approved',
        'name' => 'Dr. Test Specialist',
        'name_ar' => 'د. تجربة',
    ]);

    $response = $this->postJson(route('api.chat.booking.step'), [
        'step' => 'doctors',
        'locale' => 'en',
        'preferences' => [
            'gender_preference' => 'both',
            'language_preference' => 'both',
        ],
    ])
        ->assertSuccessful()
        ->assertJsonPath('step', 'doctors')
        ->assertJsonPath('mode', 'doctors');

    expect($response->json('doctors'))->not->toBeEmpty()
        ->and($response->json('doctors.0'))->toHaveKeys(['id', 'label', 'photo_url']);
});

test('booking complete stores session preferences and returns booking url', function () {
    $doctor = Doctor::factory()->create(['status' => 'approved']);

    $this->mock(DoctorAvailabilityService::class, function ($mock): void {
        $mock->shouldReceive('availableSlots')->andReturn(['12:15']);
    });

    $response = $this->postJson(route('api.chat.booking.complete'), [
        'locale' => 'ar',
        'preferences' => [
            'degree_id' => '1',
            'gender_preference' => 'both',
            'duration_minutes' => '30',
            'language_preference' => 'both',
            'subspecialties' => [],
            'doctor_id' => $doctor->id,
        ],
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'booking_url',
            'specialists_url',
            'filter_url',
            'preferences',
            'message',
            'nearest_slot',
        ]);

    expect(session('session_filter_preferences'))->toMatchArray([
        'degree_id' => '1',
        'gender_preference' => 'both',
        'duration_minutes' => '30',
        'language_preference' => 'both',
    ]);

    expect(PendingPatientBooking::get())->toMatchArray([
        'doctor_id' => $doctor->id,
        'time' => '12:15',
        'duration' => 30,
    ]);

    expect($response->json('booking_url'))->toContain('patient/book-appointments/'.$doctor->id)
        ->and($response->json('booking_url'))->toContain('duration=30');
});

test('booking confirm step stores pending booking for guest chatbot flow', function () {
    $doctor = Doctor::factory()->create(['status' => 'approved']);

    $this->mock(DoctorAvailabilityService::class, function ($mock): void {
        $mock->shouldReceive('availableSlots')->andReturn(['09:30']);
    });

    $this->postJson(route('api.chat.booking.step'), [
        'step' => 'confirm',
        'locale' => 'ar',
        'preferences' => [
            'degree_id' => '1',
            'gender_preference' => 'both',
            'duration_minutes' => '15',
            'language_preference' => 'both',
            'doctor_id' => $doctor->id,
        ],
    ])
        ->assertSuccessful()
        ->assertJsonPath('mode', 'link')
        ->assertJsonPath('nearest_slot.time', '09:30');

    expect(PendingPatientBooking::get()['doctor_id'])->toBe($doctor->id)
        ->and(PendingPatientBooking::get()['duration'])->toBe(15);
});

test('book appointment tool stores pending booking for guests with selected doctor', function () {
    $doctor = Doctor::factory()->create(['status' => 'approved']);

    $this->mock(DoctorAvailabilityService::class, function ($mock): void {
        $mock->shouldReceive('availableSlots')->andReturn(['16:00']);
    });

    $result = json_decode(app(AiChatbotToolManager::class)->execute('bookAppointment', [
        'doctor_id' => $doctor->id,
        'duration_minutes' => 30,
    ]), true);

    expect($result)->toMatchArray([
        'requires_login' => true,
    ])
        ->and($result['booking_url'])->toContain('patient/book-appointments/'.$doctor->id)
        ->and(PendingPatientBooking::get())->toMatchArray([
            'doctor_id' => $doctor->id,
            'time' => '16:00',
            'duration' => 30,
        ]);
});

test('booking step accepts preferences with empty string fields from client', function () {
    $this->postJson(route('api.chat.booking.step'), [
        'step' => 'duration',
        'locale' => 'ar',
        'preferences' => [
            'degree_id' => '1',
            'gender_preference' => '',
            'duration_minutes' => '',
            'language_preference' => '',
            'doctor_id' => null,
            'subspecialties' => [],
        ],
    ])
        ->assertSuccessful()
        ->assertJsonPath('step', 'duration');
});

test('booking flow service exposes ordered steps', function () {
    expect(AiChatbotBookingFlowService::STEPS)->toBe([
        'degree',
        'speciality',
        'duration',
        'gender',
        'language',
        'doctors',
        'confirm',
    ]);
});

test('homepage chatbot includes guided booking routes', function () {
    config(['ai_chatbot.enabled' => false]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('startBookingFlow', false)
        ->assertSee('bookingStep', false);
});
