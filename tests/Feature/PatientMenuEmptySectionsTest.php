<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest is redirected from patient notifications', function () {
    $this->get(route('patient.notifications'))
        ->assertRedirect(route('login'));
});

test('authenticated patient menu shows grouped shortcuts', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    $response = $this->actingAs($user)->get(route('patient.menu'));

    $response->assertSuccessful()
        ->assertSeeText(__('patient.sidebar.group_account'))
        ->assertSeeText(__('patient.menu.account_settings'))
        ->assertSeeText(__('patient.menu.support'))
        ->assertSee(route('patient.wallet'), false)
        ->assertSee('snap-x', false);
});

test('authenticated patient sees no data on notifications page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('patient.notifications'))
        ->assertSuccessful()
        ->assertSee(__('patient.menu.notifications'), false)
        ->assertSee(__('patient.menu.no_record_found'), false);
});
