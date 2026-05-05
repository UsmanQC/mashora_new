<?php

use App\Http\Middleware\EnsurePatientPortalProfileComplete;
use App\Http\Middleware\RedirectAuthenticatedPatientVisitor;
use App\Http\Middleware\SetLocaleFromSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocaleFromSession::class,
        ]);

        $middleware->alias([
            'patient.redirect' => RedirectAuthenticatedPatientVisitor::class,
            'patient.profile' => EnsurePatientPortalProfileComplete::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
