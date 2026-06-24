<?php

use App\Http\Controllers\Patient\FollowUpPaymentController;
use App\Http\Controllers\Patient\PatientAppointmentRealtimeController;
use App\Http\Controllers\Patient\PatientPaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/patient')->name('home');

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

Route::get('patient/locale/{locale}', function (string $locale) {
    if (! in_array($locale, ['en', 'ar'], true)) {
        abort(404);
    }

    session(['patient_locale' => $locale]);

    return redirect()->back();
})->name('patient.locale');

Route::livewire('patient', 'pages::patient.home')
    ->middleware(['patient.public'])
    ->name('patient.home');

Route::livewire('patient/appointments', 'pages::patient.appointments')
    ->middleware(['patient.profile'])
    ->name('patient.appointments');

Route::livewire('patient/appointments/{appointment}/conversation', 'pages::patient.appointment.conversation')
    ->middleware(['patient.profile'])
    ->name('patient.appointments.conversation');

Route::livewire('patient/appointments/{appointment}/missed-reschedule', 'pages::patient.appointment.missed-reschedule')
    ->middleware(['patient.profile'])
    ->name('patient.appointments.missed-reschedule');

Route::view('patient/menu', 'patient.menu')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.menu');

Route::livewire('patient/notifications', 'pages::patient.notifications')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.notifications');

Route::livewire('patient/wallet', 'pages::patient.wallet')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.wallet');

Route::livewire('patient/follow-up/{appointment}', 'pages::patient.follow-up-confirm')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.follow-up.confirm');

Route::livewire('patient/follow-up/{appointment}/pay', 'pages::patient.follow-up-pay')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.follow-up.pay');

Route::get('patient/follow-up/payment/success/{appointment}', [FollowUpPaymentController::class, 'success'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.follow-up.payment.success');

Route::get('patient/follow-up/payment/failed/{appointment}', [FollowUpPaymentController::class, 'failed'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.follow-up.payment.failed');

Route::post('patient/follow-up/payment/execute/{appointment}', [FollowUpPaymentController::class, 'executePayment'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.follow-up.payment.execute');

Route::view('patient/medications', 'patient.section-empty', ['titleKey' => 'patient.menu.medications'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.medications');

Route::view('patient/favorites', 'patient.section-empty', ['titleKey' => 'patient.menu.favorites'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.favorites');

Route::livewire('patient/support', 'pages::patient.support')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.support');

Route::livewire('patient/support/create', 'pages::patient.support-create')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.support.create');

Route::livewire('patient/support/{ticket}', 'pages::patient.support-show')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.support.show');

Route::view('patient/privacy', 'patient.section-empty', ['titleKey' => 'patient.menu.privacy'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.privacy');

Route::livewire('patient/filter', 'pages::patient.schedule-session')
    ->middleware(['patient.public'])
    ->name('patient.schedule.filter');

Route::livewire('patient/specialists', 'pages::patient.schedule-specialists')
    ->middleware(['patient.public'])
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
    ->middleware(['patient.public'])
    ->name('patient.important-numbers');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/patient')->name('dashboard');
});

require __DIR__.'/settings.php';
