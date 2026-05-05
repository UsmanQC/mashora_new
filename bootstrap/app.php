<?php

use App\Http\Middleware\EnsureDoctorOwnsAppointment;
use App\Http\Middleware\EnsureDoctorPortalActive;
use App\Http\Middleware\EnsureDoctorProfileCompleted;
use App\Http\Middleware\EnsurePatientPortalProfileComplete;
use App\Http\Middleware\RedirectAuthenticatedPatientVisitor;
use App\Http\Middleware\RedirectIfDoctorAuthenticated;
use App\Http\Middleware\SetLocaleFromSession;
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
            Route::middleware('web')
                ->prefix('doctor')
                ->name('doctor.')
                ->group(base_path('routes/doctor.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocaleFromSession::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('doctor', 'doctor/*')) {
                return route('doctor.login');
            }

            return route('login');
        });

        $middleware->alias([
            'patient.redirect' => RedirectAuthenticatedPatientVisitor::class,
            'patient.profile' => EnsurePatientPortalProfileComplete::class,
            'doctor.profile' => EnsureDoctorProfileCompleted::class,
            'doctor.active' => EnsureDoctorPortalActive::class,
            'doctor.guest' => RedirectIfDoctorAuthenticated::class,
            'doctor.appointment' => EnsureDoctorOwnsAppointment::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
