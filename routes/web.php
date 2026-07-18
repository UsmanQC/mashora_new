<?php

use App\Http\Controllers\AiChatbotBookingController;
use App\Http\Controllers\AiChatbotController;
use App\Http\Controllers\WebManifestController;
use App\Support\PortalDomains;
use App\Support\SpecialistCatalog;
use Illuminate\Support\Facades\Route;

Route::get('/manifest.webmanifest', WebManifestController::class)
    ->defaults('app', 'patient')
    ->name('manifest');

Route::get('/doctor/manifest.webmanifest', WebManifestController::class)
    ->defaults('app', 'doctor')
    ->name('manifest.doctor');

Route::get('/', function () {
    if (! session()->has('patient_locale')) {
        app()->setLocale('ar');
    }

    return view('frontend.home', [
        'featuredDoctors' => SpecialistCatalog::forMarketingHomepage(12),
        'doctorStats' => SpecialistCatalog::marketingStats(),
    ]);
})->name('home');

Route::prefix('api')->group(function () {
    Route::post('chat', [AiChatbotController::class, 'store'])
        ->middleware('throttle:ai-chatbot')
        ->name('api.chat');

    Route::delete('chat/history', [AiChatbotController::class, 'destroy'])
        ->middleware('throttle:ai-chatbot')
        ->name('api.chat.reset');

    Route::post('chat/booking/step', [AiChatbotBookingController::class, 'step'])
        ->middleware('throttle:ai-chatbot')
        ->name('api.chat.booking.step');

    Route::post('chat/booking/complete', [AiChatbotBookingController::class, 'complete'])
        ->middleware('throttle:ai-chatbot')
        ->name('api.chat.booking.complete');
});

Route::post('ai-chatbot/message', [AiChatbotController::class, 'store'])
    ->middleware('throttle:ai-chatbot')
    ->name('ai-chatbot.message');

Route::delete('ai-chatbot/history', [AiChatbotController::class, 'destroy'])
    ->middleware('throttle:ai-chatbot')
    ->name('ai-chatbot.reset');

/*
| Legacy path redirects when portals move to subdomains.
| awaan.io/patient/foo → patient.awaan.io/foo
| awaan.io/doctor/foo → doctor.awaan.io/foo
*/
if (PortalDomains::patientEnabled()) {
    Route::any('patient/{path?}', function (?string $path = null) {
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $base = $scheme.'://'.PortalDomains::patient();
        $target = $base.($path ? '/'.ltrim($path, '/') : '');
        $query = request()->getQueryString();

        if (filled($query)) {
            $target .= '?'.$query;
        }

        return redirect()->away($target, 302);
    })->where('path', '.*');
}

if (PortalDomains::doctorEnabled()) {
    Route::any('doctor/{path?}', function (?string $path = null) {
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $base = $scheme.'://'.PortalDomains::doctor();
        $target = $base.($path ? '/'.ltrim($path, '/') : '');
        $query = request()->getQueryString();

        if (filled($query)) {
            $target .= '?'.$query;
        }

        return redirect()->away($target, 302);
    })->where('path', '.*');
}

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return redirect()->route('patient.home');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
