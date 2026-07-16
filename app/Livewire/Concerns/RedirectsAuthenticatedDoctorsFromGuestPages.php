<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;

trait RedirectsAuthenticatedDoctorsFromGuestPages
{
    protected function redirectAuthenticatedDoctorAwayFromGuestPages(): bool
    {
        if (Auth::guard('doctor')->check()) {
            $this->redirect(route('doctor.dashboard'), navigate: true);

            return true;
        }

        return false;
    }
}
