<?php

use App\Models\User;
use App\Support\CountryPhoneTerritories;
use App\Support\PatientPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient-auth')] #[Title('Phone')] class extends Component
{
    public string $countryIso = 'SA';

    public string $phone = '';

    public ?string $loginPhoneE164 = null;

    public function mount(Request $request): void
    {
        $raw = $request->query('phone');

        if (! is_string($raw) || $raw === '') {
            $raw = old('email');
        }

        if (! is_string($raw) || $raw === '') {
            return;
        }

        $this->loginPhoneE164 = $this->resolveExistingLoginPhone($raw);
    }

    private function resolveExistingLoginPhone(string $raw): ?string
    {
        $digits = PatientPhone::normalize($raw);

        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return null;
        }

        $user = User::query()->wherePhoneLogin($digits)->first();

        return $user?->phone;
    }

    public function backToPhoneEntry(): void
    {
        $this->loginPhoneE164 = null;
        $this->resetValidation();
    }

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

        if (User::query()->wherePhoneLogin($normalized)->exists()) {
            $this->redirect(route('patient.phone', ['phone' => $normalized]), navigate: true);

            return;
        }

        $this->redirect(URL::temporarySignedRoute(
            'patient.auth.verify-phone',
            now()->addHour(),
            ['phone' => $normalized],
        ), navigate: false);
    }
}; ?>

<div class="flex min-h-0 w-full flex-col text-start">
    @if ($loginPhoneE164)
        <flux:heading size="lg" class="patient-auth-heading text-start !text-zinc-900 sm:!text-2xl">{{ __('patient_auth.login_title') }}</flux:heading>
        <flux:text class="mt-1 text-sm sm:mt-2 sm:text-base">{{ __('patient_auth.login_password_lead') }}</flux:text>

        <flux:text class="mt-2 text-sm font-medium tabular-nums text-zinc-700 sm:mt-3 sm:text-base" dir="ltr">
            +{{ $loginPhoneE164 }}
        </flux:text>

        <x-auth-session-status class="my-4 text-center sm:my-6" :status="session('status')" />

        @error('email')
            <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
        @enderror

        <form method="POST" action="{{ route('login.store') }}" class="space-y-4 sm:space-y-6">
            @csrf
            <input type="hidden" name="patient_flow" value="1" />
            <input type="hidden" name="email" value="{{ $loginPhoneE164 }}" />

            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('patient_auth.password')"
                    type="password"
                    required
                    autofocus
                    autocomplete="current-password"
                    viewable
                />
                @if (Route::has('password.request'))
                    <flux:link
                        class="absolute end-0 top-0 text-sm font-medium text-mashora-brand hover:underline"
                        :href="route('patient.auth.forgot-password')"
                        wire:navigate
                    >
                        {{ __('patient_auth.forgot_password') }}
                    </flux:link>
                @endif
            </div>

            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <flux:button variant="primary" type="submit" class="patient-auth-primary-btn w-full">
                {{ __('patient_auth.cta_login') }}
            </flux:button>

            <div class="text-center text-sm text-zinc-500">
                <flux:button type="button" variant="ghost" size="sm" wire:click="backToPhoneEntry" class="mx-auto">
                    {{ __('patient_auth.change_phone') }}
                </flux:button>
            </div>
        </form>
    @else
        <flux:heading size="lg" class="patient-auth-heading text-start !text-zinc-900 sm:!text-2xl">{{ __('patient_auth.phone_heading') }}</flux:heading>

        <form wire:submit="continueGuest" class="mt-2 w-full space-y-3 sm:mt-3 sm:space-y-4">
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
    @endif
</div>
