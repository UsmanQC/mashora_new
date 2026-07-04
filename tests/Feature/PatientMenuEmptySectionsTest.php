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
        ->assertSee('data-test="patient-luxury-menu"', false)
        ->assertSee('data-test="patient-menu-header"', false)
        ->assertSee('data-test="patient-luxury-menu-sections"', false)
        ->assertSeeText(__('patient.sidebar.group_account'))
        ->assertSeeText(__('patient.menu.account_settings'))
        ->assertSeeText(__('patient.menu.support'))
        ->assertSee(route('patient.wallet'), false);
});

test('authenticated patient sees luxury header on menu sub-pages', function () {
    $user = User::factory()->create(['profile_completed' => true]);

    $this->actingAs($user)->get(route('patient.medications'))
        ->assertSuccessful()
        ->assertSee('data-test="patient-luxury-section-empty-medications"', false)
        ->assertSee('data-test="patient-section-empty-header-medications"', false);

    $this->actingAs($user)->get(route('patient.favorites'))
        ->assertSuccessful()
        ->assertSee('data-test="patient-luxury-favorites"', false)
        ->assertSee('data-test="patient-favorites-header"', false)
        ->assertSee(__('patient.menu.favorites_browse'), false);

    $this->actingAs($user)->get(route('patient.support'))
        ->assertSuccessful()
        ->assertSee('data-test="patient-luxury-support"', false)
        ->assertSee('data-test="patient-support-header"', false);
});

test('authenticated patient sees luxury notifications page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('patient.notifications'))
        ->assertSuccessful()
        ->assertSee('data-test="patient-luxury-notifications"', false)
        ->assertSee('data-test="patient-notifications-header"', false)
        ->assertSee(__('patient.menu.notifications'), false)
        ->assertSee(__('patient.notifications.empty'), false)
        ->assertSee(__('patient.notifications.empty_hint'), false);
});
