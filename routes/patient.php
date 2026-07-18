<?php

use App\Http\Controllers\Patient\DeviceTokenController;
use App\Http\Controllers\Patient\FollowUpPaymentController;
use App\Http\Controllers\Patient\PatientAppointmentRealtimeController;
// use App\Http\Controllers\Patient\PatientDiagnosisController;
use App\Http\Controllers\Patient\PatientPaymentController;
use App\Http\Controllers\Patient\PatientPrescriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
| Patient portal routes (paths without the "patient/" prefix).
| Registered either on PATIENT_DOMAIN or under /patient on the main host.
*/

Route::middleware(['patient.redirect'])->group(function () {
    Route::redirect('start', 'phone', 302)
        ->name('patient.auth.start');

    Route::livewire('phone', 'pages::patient-auth.phone')
        ->name('patient.phone');
});

Route::get('sign-in', function (Request $request) {
    return redirect()->route('patient.phone', $request->query());
})->middleware(['patient.redirect'])->name('patient.auth.sign-in');

Route::livewire('verify-phone', 'pages::patient-auth.verify-phone')
    ->middleware(['patient.redirect', 'signed'])
    ->name('patient.auth.verify-phone');

Route::livewire('sign-up', 'pages::patient-auth.sign-up')
    ->middleware(['patient.redirect', 'signed'])
    ->name('patient.auth.sign-up');

Route::view('forgot-password', 'patient.auth.forgot-password')
    ->middleware(['patient.redirect'])
    ->name('patient.auth.forgot-password');

Route::livewire('profile/basic', 'pages::patient-auth.profile-basic')
    ->middleware('auth')
    ->name('patient.profile.basic');

Route::livewire('register/done', 'pages::patient-auth.congrats')
    ->middleware('auth')
    ->name('patient.register.done');

Route::get('locale/{locale}', function (string $locale) {
    if (! in_array($locale, ['en', 'ar'], true)) {
        abort(404);
    }

    session(['patient_locale' => $locale]);

    return redirect()->back();
})->name('patient.locale');

Route::livewire('/', 'pages::patient.home')
    ->middleware(['patient.public'])
    ->name('patient.home');

Route::livewire('appointments', 'pages::patient.appointments')
    ->middleware(['patient.profile'])
    ->name('patient.appointments');

Route::livewire('appointments/{appointment}/conversation', 'pages::patient.appointment.conversation')
    ->middleware(['patient.profile'])
    ->name('patient.appointments.conversation');

Route::livewire('appointments/{appointment}/missed-reschedule', 'pages::patient.appointment.missed-reschedule')
    ->middleware(['patient.profile'])
    ->name('patient.appointments.missed-reschedule');

Route::livewire('appointments/{appointment}/payment-missed-reschedule', 'pages::patient.appointment.payment-missed-reschedule')
    ->middleware(['patient.profile'])
    ->name('patient.appointments.payment-missed-reschedule');

Route::view('menu', 'patient.menu')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.menu');

Route::livewire('notifications', 'pages::patient.notifications')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.notifications');

Route::post('device-token', [DeviceTokenController::class, 'store'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.device-token.store');

Route::delete('device-token', [DeviceTokenController::class, 'destroy'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.device-token.destroy');

Route::livewire('wallet', 'pages::patient.wallet')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.wallet');

Route::livewire('follow-up/{appointment}', 'pages::patient.follow-up-confirm')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.follow-up.confirm');

Route::livewire('follow-up/{appointment}/pay', 'pages::patient.follow-up-pay')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.follow-up.pay');

Route::get('follow-up/payment/success/{appointment}', [FollowUpPaymentController::class, 'success'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.follow-up.payment.success');

Route::get('follow-up/payment/failed/{appointment}', [FollowUpPaymentController::class, 'failed'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.follow-up.payment.failed');

Route::post('follow-up/payment/execute/{appointment}', [FollowUpPaymentController::class, 'executePayment'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.follow-up.payment.execute');

Route::livewire('medications', 'pages::patient.medications')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.medications');

// Diagnosis reports temporarily hidden from the patient app.
// Route::livewire('diagnoses', 'pages::patient.diagnoses')
//     ->middleware(['auth', 'patient.profile'])
//     ->name('patient.diagnoses');
//
// Route::get('diagnoses/{appointment}/preview', [PatientDiagnosisController::class, 'preview'])
//     ->middleware(['auth', 'patient.profile'])
//     ->name('patient.diagnoses.preview');
//
// Route::get('diagnoses/{appointment}/pdf', [PatientDiagnosisController::class, 'download'])
//     ->middleware(['auth', 'patient.profile'])
//     ->name('patient.diagnoses.pdf');

Route::get('prescriptions/{appointment}/preview', [PatientPrescriptionController::class, 'preview'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.prescriptions.preview');

Route::get('prescriptions/{appointment}/pdf', [PatientPrescriptionController::class, 'download'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.prescriptions.pdf');

Route::livewire('favorites', 'pages::patient.favorites')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.favorites');

Route::livewire('support', 'pages::patient.support')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.support');

Route::livewire('support/create', 'pages::patient.support-create')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.support.create');

Route::livewire('support/{ticket}', 'pages::patient.support-show')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.support.show');

Route::view('privacy', 'patient.section-empty', ['titleKey' => 'patient.menu.privacy'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.privacy');

Route::livewire('filter', 'pages::patient.schedule-session')
    ->middleware(['patient.public'])
    ->name('patient.schedule.filter');

Route::livewire('specialists', 'pages::patient.schedule-specialists')
    ->middleware(['patient.public'])
    ->name('patient.schedule.specialists');

Route::livewire('available-now', 'pages::patient.schedule-specialists')
    ->middleware(['patient.public'])
    ->name('patient.schedule.instant');

Route::livewire('book-appointments/{doctor}', 'pages::patient.book-appointments')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.book-appointments');

Route::livewire('checkout-demo', 'pages::patient.checkout-demo')
    ->name('patient.checkout.demo');

Route::livewire('checkout/{temporaryAppointment}', 'pages::patient.checkout')
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.checkout');

Route::get('payment/success/{temporaryAppointment}', [PatientPaymentController::class, 'success'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.payment.success');

Route::get('payment/failed/{temporaryAppointment}', [PatientPaymentController::class, 'failed'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.payment.failed');

Route::post('payment/execute/{temporaryAppointment}', [PatientPaymentController::class, 'executePayment'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.payment.execute');

Route::post('payment/embedded/{temporaryAppointment}', [PatientPaymentController::class, 'completeEmbedded'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.payment.embedded');

Route::post('appointments/{appointment}/realtime/notify-call', [PatientAppointmentRealtimeController::class, 'notifyCall'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.appointments.realtime.notify-call');

Route::get('appointments/{appointment}/realtime/pending-call', [PatientAppointmentRealtimeController::class, 'pendingIncomingCall'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.appointments.realtime.pending-call');

Route::post('appointments/{appointment}/realtime/end-call', [PatientAppointmentRealtimeController::class, 'endCall'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.appointments.realtime.end-call');

Route::post('appointments/{appointment}/realtime/agora-token', [PatientAppointmentRealtimeController::class, 'refreshAgoraToken'])
    ->middleware(['auth', 'patient.profile'])
    ->name('patient.appointments.realtime.agora-token');

Route::view('important-numbers', 'patient.important-numbers')
    ->middleware(['patient.public'])
    ->name('patient.important-numbers');
