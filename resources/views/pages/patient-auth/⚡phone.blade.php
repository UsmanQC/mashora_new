<?php

use App\Models\User;
use App\Support\CountryPhoneTerritories;
use App\Support\PatientPhone;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient-auth')] #[Title('Phone')] class extends Component
{
    public string $countryIso = 'SA';

    public string $phone = '';

    public function continueGuest(): void
    {
        $this->countryIso = strtoupper(trim($this->countryIso));

        $isos = array_column(config('country_phone_territories'), 'iso');

        $this->validate([
            'countryIso' => ['required', 'string', 'size:2', Rule::in($isos)],
            'phone' => ['required', 'string', 'min:8', 'max:24'],
        ]);

        $dial = CountryPhoneTerritories::dialForIso($this->countryIso);
        if ($dial === null) {
            $this->addError('countryIso', __('validation.in', ['attribute' => 'countryIso']));

            return;
        }

        $normalized = PatientPhone::combineInternational($dial, $this->phone);

        if (strlen($normalized) < 10) {
            $this->addError('phone', __('patient_auth.phone_invalid_length'));

            return;
        }

        if (User::query()->where('phone', $normalized)->exists()) {
            $this->redirect(route('patient.auth.sign-in', ['phone' => $normalized]));

            return;
        }

        $this->redirect(URL::temporarySignedRoute(
            'patient.auth.sign-up',
            now()->addHour(),
            ['phone' => $normalized],
        ));
    }
}; ?>

<div>
    <flux:heading size="xl" class="patient-auth-heading">{{ __('patient_auth.phone_heading') }}</flux:heading>
    <flux:text class="mt-2">{{ __('patient_auth.phone_lead') }}</flux:text>

    <form wire:submit="continueGuest" class="mt-8 space-y-6">
        @include('partials.patient-unified-phone-field')

        @error('countryIso')
            <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
        @enderror

        @error('phone')
            <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
        @enderror

        <flux:button variant="primary" type="submit" class="patient-auth-primary-btn w-full">
            {{ __('patient_auth.next') }}
        </flux:button>
    </form>
</div>
