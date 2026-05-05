<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['patient.redirect'])->group(function () {
    Route::redirect('patient/start', '/patient/phone', 302)
        ->name('patient.auth.start');

    Route::livewire('patient/phone', 'pages::patient-auth.phone')
        ->name('patient.phone');
});

Route::view('patient/sign-in', 'patient.auth.sign-in')
    ->middleware(['patient.redirect'])
    ->name('patient.auth.sign-in');

Route::livewire('patient/sign-up', 'pages::patient-auth.sign-up')
    ->middleware(['patient.redirect', 'signed'])
    ->name('patient.auth.sign-up');

Route::view('patient/forgot-password', 'patient.auth.forgot-password')
    ->middleware(['patient.redirect'])
    ->name('patient.auth.forgot-password');

Route::livewire('patient/profile/basic', 'pages::patient-auth.profile-basic')
    ->middleware('auth')
    ->name('patient.profile.basic');

Route::livewire('patient/register/done', 'pages::patient-auth.congrats')
    ->middleware('auth')
    ->name('patient.register.done');

Route::livewire('patient', 'pages::patient.home')
    ->middleware(['patient.profile'])
    ->name('patient.home');

Route::view('patient/appointments', 'patient.appointments')
    ->middleware(['patient.profile'])
    ->name('patient.appointments');

Route::middleware(['patient.profile'])
    ->get('patient/locale/{locale}', function (string $locale) {
        if (! in_array($locale, ['en', 'ar'], true)) {
            abort(404);
        }

        session(['patient_locale' => $locale]);

        return redirect()->back();
    })
    ->name('patient.locale');

Route::view('patient/menu', 'patient.menu')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.menu');

Route::view('patient/notifications', 'patient.section-empty', ['titleKey' => 'patient.menu.notifications'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.notifications');

Route::view('patient/medications', 'patient.section-empty', ['titleKey' => 'patient.menu.medications'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.medications');

Route::view('patient/favorites', 'patient.section-empty', ['titleKey' => 'patient.menu.favorites'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.favorites');

Route::view('patient/support', 'patient.section-empty', ['titleKey' => 'patient.menu.support'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.support');

Route::view('patient/privacy', 'patient.section-empty', ['titleKey' => 'patient.menu.privacy'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.privacy');

Route::livewire('patient/filter', 'pages::patient.schedule-session')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.schedule.filter');

Route::livewire('patient/specialists', 'pages::patient.schedule-specialists')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.schedule.specialists');

Route::view('patient/important-numbers', 'patient.important-numbers')
    ->middleware(['patient.profile'])
    ->name('patient.important-numbers');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
