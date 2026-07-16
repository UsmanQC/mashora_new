<?php

use App\Livewire\Concerns\RedirectsAuthenticatedDoctorsFromGuestPages;
use App\Models\Doctor;
use App\Support\CountryPhoneTerritories;
use App\Support\PatientPhone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor-guest')] #[Title('Doctor portal')] class extends Component
{
    use RedirectsAuthenticatedDoctorsFromGuestPages;

    public string $countryIso = 'SA';

    public string $phone = '';

    public function mount(): void
    {
        $this->redirectAuthenticatedDoctorAwayFromGuestPages();
    }

    public function proceed(): void
    {
        if ($this->redirectAuthenticatedDoctorAwayFromGuestPages()) {
            return;
        }

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

        $nationalDigits = preg_replace('/\D/', '', $this->phone) ?? '';
        $normalized = str_starts_with($nationalDigits, $dial)
            ? $nationalDigits
            : PatientPhone::combineInternational($dial, $this->phone);
        if (strlen($normalized) < 10) {
            $this->addError('phone', __('patient_auth.phone_invalid_length'));

            return;
        }

        $doctorExists = Doctor::query()
            ->where('phone', $normalized)
            ->where(static fn (Builder $query): Builder => $query->whereNull('deleted_at'))
            ->exists();

        if ($doctorExists) {
            $this->redirect(route('doctor.login', ['phone' => $normalized]), navigate: true);

            return;
        }

        if (config('doctor.registration_invite_only')) {
            $this->redirect(URL::temporarySignedRoute('doctor.verify-phone', now()->addHours(2), ['phone' => $normalized]), navigate: true);

            return;
        }

        $this->redirect(route('doctor.verify-phone', ['phone' => $normalized]), navigate: true);
    }
}; ?>

<div class="flex min-h-0 w-full flex-col text-start">
    <flux:heading size="lg" class="patient-auth-heading !text-zinc-900 sm:!text-2xl">{{ __('doctor.auth.phone_heading') }}</flux:heading>

    <form wire:submit="proceed" class="mt-5 space-y-4 sm:mt-8 sm:space-y-6">
        @include('partials.doctor-unified-phone-field')

        @error('countryIso')
            <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
        @enderror

        @error('phone')
            <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
        @enderror

        <flux:button variant="primary" type="submit" class="patient-auth-primary-btn w-full">
            {{ __('doctor.auth.next') }}
        </flux:button>
    </form>

    @if (config('doctor.registration_invite_only') && app()->environment('local'))
        <flux:text class="mt-4 text-center text-xs font-mono text-zinc-500">
            Dev signed URL: {{ URL::temporarySignedRoute('doctor.verify-phone', now()->addHours(2), ['phone' => '966500000000']) }}
        </flux:text>
    @endif
</div>
