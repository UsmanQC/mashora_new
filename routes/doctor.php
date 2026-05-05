<?php

use App\Http\Controllers\Doctor\DoctorSessionController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::get('locale/{locale}', function (string $locale): RedirectResponse {
    if (! in_array($locale, ['en', 'ar'], true)) {
        abort(404);
    }

    session(['patient_locale' => $locale]);

    return redirect()->back();
})->name('locale');

Route::middleware('doctor.guest')->group(function (): void {
    Route::livewire('/', 'pages::doctor.welcome')->name('welcome');
    Route::livewire('login', 'pages::doctor.login')->name('login');

    Route::livewire('register', 'pages::doctor.register')
        ->middleware(config('doctor.registration_invite_only') ? ['signed'] : [])
        ->name('register');
});

Route::middleware('auth:doctor')->group(function (): void {
    Route::post('logout', [DoctorSessionController::class, 'destroy'])->name('logout');

    Route::livewire('register/basic-info', 'pages::doctor.register-basic-info')
        ->middleware('doctor.active')
        ->name('register.basic.info');

    Route::middleware(['doctor.profile', 'doctor.active'])->group(function (): void {
        Route::livewire('dashboard', 'pages::doctor.dashboard')->name('dashboard');
        Route::livewire('appointments', 'pages::doctor.appointments')->name('appointments');

        Route::middleware('doctor.appointment')->group(function (): void {
            Route::livewire(
                'appointments/{appointment}/medical-history',
                'pages::doctor.appointment.medical-history',
            )->name('appointments.medical-history');
            Route::livewire(
                'appointments/{appointment}/diagnosis',
                'pages::doctor.appointment.diagnosis',
            )->name('appointments.diagnosis');
            Route::livewire(
                'appointments/{appointment}/prescription',
                'pages::doctor.appointment.prescription',
            )->name('appointments.prescription');
            Route::livewire(
                'appointments/{appointment}/conversation',
                'pages::doctor.appointment.conversation',
            )->name('appointments.conversation');
            Route::livewire(
                'appointments/{appointment}/chat-v2',
                'pages::doctor.appointment.chat-v2',
            )->name('appointments.conversation.chat-v2');
            Route::livewire(
                'appointments/{appointment}/chat',
                'pages::doctor.appointment.chat',
            )->name('appointments.conversation.chat');
            Route::livewire(
                'appointments/{appointment}/video',
                'pages::doctor.appointment.video',
            )->name('appointments.conversation.video');
            Route::livewire(
                'appointments/{appointment}/voice',
                'pages::doctor.appointment.voice',
            )->name('appointments.conversation.voice');
        });

        Route::livewire('ratings', 'pages::doctor.ratings')->name('ratings');
        Route::livewire('settings', 'pages::doctor.settings')->name('settings');
    });
});
