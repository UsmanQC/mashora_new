<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest is redirected from patient notifications', function () {
    $this->get(route('patient.notifications'))
        ->assertRedirect(route('login'));
});

test('authenticated patient sees no data on notifications page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('patient.notifications'))
        ->assertSuccessful()
        ->assertSee(__('patient.menu.notifications'), false)
        ->assertSee(__('patient.menu.no_record_found'), false);
});
