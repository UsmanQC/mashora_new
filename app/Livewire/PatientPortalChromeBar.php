<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PatientPortalChromeBar extends Component
{
    /**
     * Unread notifications count placeholder until backend wiring exists.
     */
    public int $notificationCount = 0;

    public function openMoodPicker(): void
    {
        $this->dispatch('open-patient-mood-picker');
    }

    public function render(): View
    {
        return view('livewire.patient-portal-chrome-bar', [
            'user' => Auth::user(),
        ]);
    }
}
