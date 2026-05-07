<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

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

test('existing mobile number opens password step on phone page', function () {
    User::factory()->create([
        'phone' => '966512345678',
        'profile_completed' => true,
    ]);

    Livewire::test('pages::patient-auth.phone')
        ->set('countryIso', 'SA')
        ->set('phone', '512345678')
        ->call('continueGuest')
        ->assertSet('loginPhoneE164', '966512345678')
        ->assertSee(__('patient_auth.cta_login'), false);
});

test('legacy patient sign-in url redirects to phone entry', function () {
    $this->get(route('patient.auth.sign-in'))
        ->assertRedirect(route('patient.phone'));
});

test('legacy patient sign-in url preserves phone query string', function () {
    $this->get(route('patient.auth.sign-in', ['phone' => '966512345670']))
        ->assertRedirect(route('patient.phone', ['phone' => '966512345670']));
});

test('signed patient sign up url responds', function () {
    $phone = '966512345670';
    $url = URL::temporarySignedRoute('patient.auth.sign-up', now()->addHour(), ['phone' => $phone]);

    $this->get($url)
        ->assertSuccessful()
        ->assertSee(__('patient_auth.cta_register'), false)
        ->assertSee(route('patient.phone', ['phone' => $phone]), false);
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

test('guest is redirected to patient phone from patient appointments', function () {
    $this->get(route('patient.appointments'))
        ->assertRedirect(route('patient.phone'));
});

test('patient logout redirects back to patient home', function () {
    $user = User::factory()->create([
        'profile_completed' => true,
    ]);

    $this->from(route('patient.home'))
        ->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('patient.home'));
});

test('patient flow login always redirects to patient home for completed profiles', function () {
    $user = User::factory()->create([
        'phone' => '966500111222',
        'password' => 'password',
        'profile_completed' => true,
    ]);

    $this->post(route('login.store'), [
        'patient_flow' => 1,
        'email' => $user->phone,
        'password' => 'password',
    ])->assertRedirect(route('patient.home'));
});
