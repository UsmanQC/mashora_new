<?php

use App\Models\User;
use App\Models\VerifyPhoneNumber;
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
        ->assertSee(__('patient.brand'), false)
        ->assertDontSee('images/logo.png', false)
        ->assertSee(__('patient_auth.phone_heading'), false)
        ->assertSee(__('patient_auth.phone_label'), false)
        ->assertSee('data-test="patient-navbar-language-switch"', false)
        ->assertSee(route('patient.locale', ['locale' => 'ar']), false)
        ->assertSee(__('patient.menu.locale_ar_short'), false)
        ->assertSee('data-test="patient-auth-back"', false)
        ->assertSee('id="patient-auth-swipe-surface"', false)
        ->assertSee('data-back-url="'.route('home').'"', false)
        ->assertSee(__('patient_auth.back'), false)
        ->assertSee('href="'.route('home').'"', false)
        ->assertDontSee('data-test="patient-auth-back" wire:navigate', false)
        ->assertSee('patient-auth-content', false)
        ->assertSee('intlTelInput.css', false)
        ->assertSee('intlTelInput.min.js', false)
        ->assertSee('patientPhoneField', false)
        ->assertSee('Thmanyah Sans', false);
});

test('guest can switch patient locale from auth pages', function () {
    $response = $this->from(route('patient.phone'))
        ->get(route('patient.locale', ['locale' => 'ar']));

    $response
        ->assertRedirect(route('patient.phone'))
        ->assertSessionHas('patient_locale', 'ar');

    $this->get(route('patient.phone'))
        ->assertSuccessful()
        ->assertSee('dir="rtl"', false);
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
        ->assertRedirect(route('patient.phone', ['phone' => '966512345678']));

    $this->get(route('patient.phone', ['phone' => '966512345678']))
        ->assertSuccessful()
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

    $this->withSession(['patient_otp_verified_phone' => $phone])
        ->get($url)
        ->assertSuccessful()
        ->assertSee(__('patient_auth.register_title'), false)
        ->assertSee(__('patient_auth.register_sub'), false)
        ->assertSee(__('patient_auth.cta_register'), false)
        ->assertSee(__('patient_auth.email'), false)
        ->assertSee(__('patient_auth.gender'), false)
        ->assertSee(route('patient.phone', ['phone' => $phone]), false);
});

test('patient sign up requires otp verified session', function () {
    $phone = '966512345670';
    $url = URL::temporarySignedRoute('patient.auth.sign-up', now()->addHour(), ['phone' => $phone]);

    $this->get($url)->assertForbidden();
});

test('unsigned patient sign up url fails', function () {
    $this->get(route('patient.auth.sign-up', ['phone' => '966512345670']))
        ->assertForbidden();
});

test('new patient number redirects to phone verification', function () {
    Livewire::test('pages::patient-auth.phone')
        ->set('countryIso', 'SA')
        ->set('phone', '512399988')
        ->call('continueGuest')
        ->assertHasNoErrors()
        ->assertRedirect(URL::temporarySignedRoute(
            'patient.auth.verify-phone',
            now()->addHour(),
            ['phone' => '966512399988'],
        ));
});

test('patient verify phone page shows visible otp inputs and test code 1111', function () {
    $phone = '966512400002';

    Livewire::withQueryParams(['phone' => $phone])
        ->test('pages::patient-auth.verify-phone')
        ->assertSee(__('patient_auth.otp_heading'), false)
        ->assertSee(__('patient_auth.otp_label'), false)
        ->assertSee(__('patient_auth.otp_resend'), false)
        ->assertSee('data-flux-otp-input', false)
        ->assertSet('devOtpDisplay', '1111');
});

test('patient can verify otp and reach sign up', function () {
    $phone = '966512400001';

    Livewire::withQueryParams(['phone' => $phone])
        ->test('pages::patient-auth.verify-phone')
        ->assertSet('phone', $phone);

    $code = VerifyPhoneNumber::query()
        ->where('phone', $phone)
        ->where('user_type', 'patient')
        ->value('verification_code');

    expect($code)->not->toBeNull();

    Livewire::withQueryParams(['phone' => $phone])
        ->test('pages::patient-auth.verify-phone')
        ->set('code', (string) $code)
        ->call('verifyOtp')
        ->assertHasNoErrors()
        ->assertRedirect(URL::temporarySignedRoute(
            'patient.auth.sign-up',
            now()->addHour(),
            ['phone' => $phone],
        ));

    expect(session('patient_otp_verified_phone'))->toBe($phone);
});

test('patient sign up requires profile fields', function () {
    $phone = '966512400005';

    session(['patient_otp_verified_phone' => $phone]);

    Livewire::withQueryParams(['phone' => $phone])
        ->test('pages::patient-auth.sign-up')
        ->call('registerPatient')
        ->assertHasErrors([
            'name' => 'required',
            'email' => 'required',
            'gender' => 'required',
            'password' => 'required',
        ]);
});

test('patient sign up requires gender selection', function () {
    $phone = '966512400004';

    session(['patient_otp_verified_phone' => $phone]);

    Livewire::withQueryParams(['phone' => $phone])
        ->test('pages::patient-auth.sign-up')
        ->set('name', 'Test Patient')
        ->set('email', 'patient-missing-gender@example.com')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('registerPatient')
        ->assertHasErrors(['gender' => 'required']);
});

test('patient can complete sign up with profile details and reach dashboard', function () {
    $phone = '966512400003';

    session(['patient_otp_verified_phone' => $phone]);

    Livewire::withQueryParams(['phone' => $phone])
        ->test('pages::patient-auth.sign-up')
        ->set('name', 'Test Patient')
        ->set('email', 'patient@example.com')
        ->set('gender', 'male')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('registerPatient')
        ->assertHasNoErrors()
        ->assertRedirect(route('patient.home'));

    $user = User::query()->where('phone', $phone)->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Test Patient')
        ->and($user->email)->toBe('patient@example.com')
        ->and($user->gender)->toBe('male')
        ->and($user->profile_completed)->toBeTrue();

    $this->assertAuthenticatedAs($user);
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

test('failed patient login keeps password step and shows error', function () {
    $user = User::factory()->create([
        'phone' => '966500333444',
        'password' => 'password',
        'profile_completed' => true,
    ]);

    $response = $this->from(route('patient.phone', ['phone' => $user->phone]))
        ->post(route('login.store'), [
            'patient_flow' => 1,
            'email' => $user->phone,
            'password' => 'wrong-password',
        ]);

    $response
        ->assertRedirect(route('patient.phone', ['phone' => $user->phone]))
        ->assertSessionHasErrors('email');

    $this->get(route('patient.phone', ['phone' => $user->phone]))
        ->assertSuccessful()
        ->assertSee(__('patient_auth.cta_login'), false);
});

test('patient login from protected intended url redirects to home not phone page', function () {
    $user = User::factory()->create([
        'phone' => '966500555666',
        'password' => 'password',
        'profile_completed' => true,
    ]);

    $this->get(route('patient.appointments'))
        ->assertRedirect(route('patient.phone'));

    $this->post(route('login.store'), [
        'patient_flow' => 1,
        'email' => $user->phone,
        'password' => 'password',
    ])
        ->assertRedirect(route('patient.home'));

    $this->assertAuthenticatedAs($user);
});
