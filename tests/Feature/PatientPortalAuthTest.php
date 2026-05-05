<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);
test('guest is redirected from legacy patient start url to phone entry', function () {
    $this->get(route('patient.auth.start'))
        ->assertRedirect('/patient/phone');
});

test('guest can open patient phone entry screen', function () {
    $this->get(route('patient.phone'))
        ->assertSuccessful()
        ->assertSee(__('patient_auth.phone_heading'), false);
});

test('guest can open patient sign in screen', function () {
    $this->get(route('patient.auth.sign-in'))
        ->assertSuccessful()
        ->assertSee(__('patient_auth.login_title'));
});

test('signed patient sign up url responds', function () {
    $url = URL::temporarySignedRoute('patient.auth.sign-up', now()->addHour(), ['phone' => '966512345670']);

    $this->get($url)
        ->assertSuccessful()
        ->assertSee(__('patient_auth.cta_register'), false);
});

test('unsigned patient sign up url fails', function () {
    $this->get(route('patient.auth.sign-up', ['phone' => '966512345670']))
        ->assertForbidden();
});

test('incomplete profile cannot browse patient appointments shell', function () {
    /** @var User $user */
    $user = User::factory()->create([
        'profile_completed' => false,
        'phone' => '966576543210',
    ]);

    $this->actingAs($user)->get(route('patient.appointments'))
        ->assertRedirect(route('patient.profile.basic'));
});

test('guest can browse patient appointments placeholder', function () {
    $this->get(route('patient.appointments'))
        ->assertSuccessful();
});
