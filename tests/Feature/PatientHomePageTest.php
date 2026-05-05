<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest can load the patient home livewire page', function () {
    $this->get(route('patient.home'))
        ->assertSuccessful()
        ->assertSee(route('patient.phone'), false);
});

test('guest can load patient appointments page', function () {
    $this->get(route('patient.appointments'))
        ->assertSuccessful()
        ->assertSee(__('patient.appointments.title'), false)
        ->assertSee(__('patient.appointments.tab_ongoing'), false)
        ->assertSee(__('patient.appointments.book_new'), false);
});

test('guest can load important numbers illustration', function () {
    $this->get(route('patient.important-numbers'))
        ->assertSuccessful()
        ->assertSee(__('patient.nav.important_numbers'), false)
        ->assertSee(asset('images/important-numbers.svg'), false);
});

test('patient home renders arabic strings when locale is ar', function () {
    app()->setLocale('ar');

    $this->get(route('patient.home'))
        ->assertSuccessful()
        ->assertSee(__('patient.welcome_guest'), false);
});

test('signed-in patient home links both session cards to schedule filter', function () {
    $user = User::factory()->create(['profile_completed' => true]);
    $filterUrl = route('patient.schedule.filter');

    $response = $this->actingAs($user)->get(route('patient.home'))->assertSuccessful();

    expect(substr_count($response->content(), $filterUrl))->toBe(2);
});

test('signed-in patient layout exposes account menu logout control', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    $this->actingAs($user)->get(route('patient.home'))
        ->assertSuccessful()
        ->assertSee('data-test="patient-logout-button"', false)
        ->assertSee(route('logout'), false);
});
