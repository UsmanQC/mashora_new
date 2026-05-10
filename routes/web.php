<?php

use App\Http\Controllers\Patient\PatientAppointmentRealtimeController;
use App\Http\Controllers\Patient\PatientPaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['patient.redirect'])->group(function () {
    Route::redirect('patient/start', '/patient/phone', 302)
        ->name('patient.auth.start');

    Route::livewire('patient/phone', 'pages::patient-auth.phone')
        ->name('patient.phone');
});

Route::get('patient/sign-in', function (Request $request) {
    return redirect()->route('patient.phone', $request->query());
})->middleware(['patient.redirect'])->name('patient.auth.sign-in');

Route::livewire('patient/verify-phone', 'pages::patient-auth.verify-phone')
    ->middleware(['patient.redirect', 'signed'])
    ->name('patient.auth.verify-phone');

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

Route::livewire('patient/appointments', 'pages::patient.appointments')
    ->middleware(['patient.profile'])
    ->name('patient.appointments');

Route::livewire('patient/appointments/{appointment}/conversation', 'pages::patient.appointment.conversation')
    ->middleware(['patient.profile'])
    ->name('patient.appointments.conversation');

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

Route::livewire('patient/book-appointments/{doctor}', 'pages::patient.book-appointments')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.book-appointments');

Route::livewire('patient/checkout-demo', 'pages::patient.checkout-demo')
    ->name('patient.checkout.demo');

Route::livewire('patient/checkout/{temporaryAppointment}', 'pages::patient.checkout')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.checkout');

Route::get('patient/payment/success/{temporaryAppointment}', [PatientPaymentController::class, 'success'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.payment.success');

Route::get('patient/payment/failed/{temporaryAppointment}', [PatientPaymentController::class, 'failed'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.payment.failed');

Route::post('patient/payment/execute/{temporaryAppointment}', [PatientPaymentController::class, 'executePayment'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.payment.execute');

Route::post('patient/appointments/{appointment}/realtime/notify-call', [PatientAppointmentRealtimeController::class, 'notifyCall'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.appointments.realtime.notify-call');

Route::post('patient/appointments/{appointment}/realtime/agora-token', [PatientAppointmentRealtimeController::class, 'refreshAgoraToken'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.appointments.realtime.agora-token');

Route::view('patient/important-numbers', 'patient.important-numbers')
    ->middleware(['patient.profile'])
    ->name('patient.important-numbers');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
