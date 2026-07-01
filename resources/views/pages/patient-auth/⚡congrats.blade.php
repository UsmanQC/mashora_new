<?php

use App\Models\User;
use App\Support\PendingPatientBooking;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient-auth')] #[Title('You are all set')] class extends Component
{
    public string $continueUrl = '';

    public function mount(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('patient.phone'));

            return;
        }

        $user = Auth::user();

        if (! $user instanceof User || ! $user->profile_completed) {
            $this->redirect(route('patient.profile.basic'));

            return;
        }

        if ($bookingUrl = PendingPatientBooking::url()) {
            $this->redirect($bookingUrl);

            return;
        }

        $this->continueUrl = route('patient.home');
    }
}; ?>

<div class="space-y-6 text-center">
    <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
        <flux:icon name="check-circle" variant="mini" class="size-10" />
    </div>
    <flux:heading size="xl" class="patient-auth-heading">{{ __('patient_auth.congrats_title') }}</flux:heading>
    <flux:text class="mx-auto max-w-md text-balance">{{ __('patient_auth.congrats_sub') }}</flux:text>

    <flux:button :href="$continueUrl" variant="primary" class="patient-auth-primary-btn mx-auto mt-4 w-full sm:w-auto" wire:navigate icon="home">
        {{ __('patient_auth.go_home') }}
    </flux:button>
</div>
