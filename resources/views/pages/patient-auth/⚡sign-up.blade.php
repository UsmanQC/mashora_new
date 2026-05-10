<?php

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Support\PatientPhone;
use App\Support\PatientPlaceholderEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

new #[Layout('layouts::patient-auth')] #[Title('Your details')] class extends Component
{
    use PasswordValidationRules;

    #[Locked]
    public string $phone = '';

    public string $name = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $digits = PatientPhone::normalize((string) request()->query('phone', ''));

        if ($digits === '') {
            throw new HttpException(403);
        }

        if ((string) session('patient_otp_verified_phone') !== $digits) {
            throw new HttpException(403);
        }

        $this->phone = $digits;
    }

    public function registerPatient(): void
    {
        $normalized = PatientPhone::normalize($this->phone);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => $this->passwordRules(),
            'phone' => [
                'required',
                'string',
                Rule::unique(User::class, 'phone')->whereNull('deleted_at'),
            ],
        ]);

        $user = User::create([
            'name' => $this->name,
            'phone' => $normalized,
            'email' => PatientPlaceholderEmail::make($normalized),
            'password' => $this->password,
            'profile_completed' => false,
        ]);

        event(new Registered($user));

        Auth::login($user);

        session()->forget('patient_otp_verified_phone');

        $this->redirect(route('patient.profile.basic'));
    }
}; ?>

<div class="w-full text-start">
    <div class="mb-5 sm:mb-6">
        <flux:button
            :href="route('patient.phone', ['phone' => $phone])"
            wire:navigate
            variant="ghost"
            size="sm"
            icon="arrow-left"
            aria-label="{{ __('pagination.previous') }}"
            title="{{ __('pagination.previous') }}"
            class="px-0 text-zinc-600 hover:text-zinc-900"
        />
    </div>

    <header class="border-b border-zinc-200/80 pb-5 sm:pb-6">
        <flux:heading size="xl" class="patient-auth-heading text-balance text-[#193ADB]">{{ __('patient_auth.register_title') }}</flux:heading>
        <flux:text class="mt-2 max-w-lg text-balance text-zinc-600">{{ __('patient_auth.register_sub') }}</flux:text>
        <div class="mt-4 flex flex-wrap items-center gap-2 text-sm text-zinc-600">
            <flux:icon name="device-phone-mobile" variant="mini" class="size-4 shrink-0 text-[#3C5CF7]" />
            <span class="font-medium text-zinc-800">{{ __('patient_auth.phone_verified_label') }}</span>
            <span class="font-semibold tabular-nums text-zinc-900" dir="ltr">+{{ $phone }}</span>
        </div>
    </header>

    <form wire:submit="registerPatient" class="mt-6 space-y-5 sm:mt-8">
        <flux:input
            wire:model.blur="name"
            type="text"
            autocomplete="name"
            required
            :label="__('patient_auth.full_name')"
        />

        <flux:input wire:model="password" viewable required type="password" :label="__('patient_auth.password')" />

        <flux:input
            wire:model="password_confirmation"
            viewable
            required
            type="password"
            :label="__('patient_auth.password_confirm')"
        />

        @error('phone')
            <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
        @enderror

        <flux:button variant="primary" type="submit" class="patient-auth-primary-btn w-full" wire:loading.attr="disabled">
            {{ __('patient_auth.cta_register') }}
        </flux:button>
    </form>
</div>
