<?php

use App\Http\Middleware\AllowGuestPatientPortalView;
use App\Http\Middleware\AuthenticateBroadcastParticipant;
use App\Http\Middleware\EnsureDoctorApproved;
use App\Http\Middleware\EnsureDoctorOwnsAppointment;
use App\Http\Middleware\EnsureDoctorPortalActive;
use App\Http\Middleware\EnsureDoctorProfileCompleted;
use App\Http\Middleware\EnsurePatientPortalProfileComplete;
use App\Http\Middleware\RedirectAuthenticatedPatientVisitor;
use App\Http\Middleware\RedirectIfDoctorAuthenticated;
use App\Http\Middleware\SetLocaleFromSession;
use App\Support\PendingPatientBooking;
use App\Support\PortalDomains;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            $patientRoutes = function (): void {
                require base_path('routes/patient.php');
            };

            $doctorRoutes = function (): void {
                require base_path('routes/doctor.php');
            };

            if (PortalDomains::patientEnabled()) {
                Route::domain((string) PortalDomains::patient())
                    ->middleware('web')
                    ->group($patientRoutes);
            } else {
                Route::middleware('web')
                    ->prefix('patient')
                    ->group($patientRoutes);
            }

            if (PortalDomains::doctorEnabled()) {
                Route::domain((string) PortalDomains::doctor())
                    ->middleware('web')
                    ->name('doctor.')
                    ->group($doctorRoutes);
            } else {
                Route::middleware('web')
                    ->prefix('doctor')
                    ->name('doctor.')
                    ->group($doctorRoutes);
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocaleFromSession::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if (PortalDomains::isDoctorPortalRequest($request)) {
                return route('doctor.login');
            }

            if (PortalDomains::isPatientPortalRequest($request)) {
                PendingPatientBooking::captureFromRequest($request);

                return route('patient.phone');
            }

            return route('login');
        });

        $middleware->alias([
            'patient.redirect' => RedirectAuthenticatedPatientVisitor::class,
            'patient.profile' => EnsurePatientPortalProfileComplete::class,
            'patient.public' => AllowGuestPatientPortalView::class,
            'doctor.profile' => EnsureDoctorProfileCompleted::class,
            'doctor.active' => EnsureDoctorPortalActive::class,
            'doctor.approved' => EnsureDoctorApproved::class,
            'doctor.guest' => RedirectIfDoctorAuthenticated::class,
            'doctor.appointment' => EnsureDoctorOwnsAppointment::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('appointments:mark-missed')->everyFiveMinutes();
        $schedule->command('appointments:expire-pending-payments')->everyMinute();
        $schedule->command('invoices:generate-monthly')->monthlyOn(1, '01:00');
    })
    ->withBroadcasting(
        channels: __DIR__.'/../routes/channels.php',
        attributes: ['middleware' => ['web', AuthenticateBroadcastParticipant::class]],
    )
    ->create();
