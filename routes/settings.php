<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'patient.profile'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});
