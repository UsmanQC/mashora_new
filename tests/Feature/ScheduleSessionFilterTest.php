<?php

use App\Models\User;
use Database\Seeders\SpecialitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot open schedule session filter page', function () {
    $this->get(route('patient.schedule.filter'))
        ->assertRedirect();
});

test('authenticated patient missing profile basics is redirected from schedule filter', function () {
    $user = User::factory()->create(['profile_completed' => false]);

    $this->actingAs($user)->get(route('patient.schedule.filter'))
        ->assertRedirect(route('patient.profile.basic'));
});

test('authenticated patient can open schedule session filter page', function () {
    app()->setLocale('en');

    $this->seed(SpecialitySeeder::class);

    $user = User::factory()->create(['profile_completed' => true]);

    $this->actingAs($user)->get(route('patient.schedule.filter'))
        ->assertSuccessful()
        ->assertSee(__('session_filter.filter_heading'), false)
        ->assertSee('Obsessive', false)
        ->assertSee('Substance Abuse', false)
        ->assertDontSee('Anorexia', false);
});
