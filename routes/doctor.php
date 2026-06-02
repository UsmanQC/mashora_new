<?php

use App\Http\Controllers\Doctor\DoctorAppointmentRealtimeController;
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

    Route::livewire('verify-phone', 'pages::doctor.verify-phone')
        ->middleware(config('doctor.registration_invite_only') ? ['signed'] : [])
        ->name('verify-phone');

    Route::livewire('register', 'pages::doctor.register')
        ->middleware(config('doctor.registration_invite_only') ? ['signed'] : [])
        ->name('register');
});

Route::middleware('auth:doctor')->group(function (): void {
    Route::post('logout', [DoctorSessionController::class, 'destroy'])->name('logout');

    Route::livewire('register/basic-info', 'pages::doctor.register-basic-info')
        ->middleware('doctor.active')
        ->name('register.basic.info');

    Route::livewire('register/duration', 'pages::doctor.register-duration')
        ->middleware('doctor.active')
        ->name('register.duration');

    Route::livewire('register/working-hours', 'pages::doctor.register-working-hours')
        ->middleware('doctor.active')
        ->name('register.working-hours');

    Route::livewire('account-status', 'pages::doctor.account-status')
        ->middleware(['doctor.profile', 'doctor.active'])
        ->name('account-status');

    Route::middleware(['doctor.profile', 'doctor.active', 'doctor.approved'])->group(function (): void {
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
            Route::post(
                'appointments/{appointment}/realtime/notify-call',
                [DoctorAppointmentRealtimeController::class, 'notifyCall'],
            )->name('appointments.realtime.notify-call');
            Route::post(
                'appointments/{appointment}/realtime/agora-token',
                [DoctorAppointmentRealtimeController::class, 'refreshAgoraToken'],
            )->name('appointments.realtime.agora-token');
        });

        Route::livewire('ratings', 'pages::doctor.ratings')->name('ratings');
        Route::livewire('settings', 'pages::doctor.settings')->name('settings');
        Route::livewire('settings/profile', 'pages::doctor.settings.profile')->name('settings.profile');
        Route::livewire('settings/notifications', 'pages::doctor.settings.notifications')->name('settings.notifications');
        Route::livewire('settings/bank-account', 'pages::doctor.settings.bank-account')->name('settings.bank-account');
        Route::livewire('settings/support', 'pages::doctor.settings.support')->name('settings.support');
        Route::livewire('settings/privacy-policy', 'pages::doctor.settings.privacy-policy')->name('settings.privacy-policy');
        Route::livewire('settings/invoices', 'pages::doctor.settings.invoices')->name('settings.invoices');
        Route::livewire('settings/working-hours', 'pages::doctor.settings.working-hours')->name('settings.working-hours');
        Route::livewire('settings/duration', 'pages::doctor.settings.duration')->name('settings.duration');
    });
});
