<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot open specialist results', function () {
    $this->get(route('patient.schedule.specialists'))
        ->assertRedirect();
});

test('patient with completed profile can load specialist demo cards', function () {
    app()->setLocale('en');

    $user = User::factory()->create(['profile_completed' => true]);

    $this->actingAs($user)
        ->get(route('patient.schedule.specialists'))
        ->assertSuccessful()
        ->assertSee(__('specialist_results.page_heading'), false)
        ->assertSee('Nada Alghamdi', false);
});
