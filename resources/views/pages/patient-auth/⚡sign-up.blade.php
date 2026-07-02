<?php

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Support\PatientPhone;
use App\Support\PendingPatientBooking;
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

    public string $email = '';

    public ?string $gender = null;

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
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->whereNull('deleted_at')],
            'gender' => ['required', Rule::in(['male', 'female'])],
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
            'email' => $this->email,
            'gender' => $this->gender,
            'password' => $this->password,
            'profile_completed' => true,
        ]);

        event(new Registered($user));

        Auth::login($user);

        session()->forget('patient_otp_verified_phone');

        $this->redirect(PendingPatientBooking::homeOrBookingUrl(), navigate: true);
    }
}; ?>

<div class="flex min-h-0 w-full flex-col text-start">
    <flux:heading size="lg" class="patient-auth-heading !text-zinc-900 text-balance sm:!text-2xl">{{ __('patient_auth.register_title') }}</flux:heading>
    <flux:text class="mt-1 text-sm leading-snug text-balance text-zinc-600 sm:mt-2 sm:text-base">{{ __('patient_auth.register_sub') }}</flux:text>

    <div class="mt-2 mb-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-600 sm:mt-3 sm:mb-4 sm:text-sm">
        <flux:icon name="device-phone-mobile" variant="mini" class="size-3.5 shrink-0 text-[#10B981] sm:size-4" />
        <span class="font-medium text-zinc-800">{{ __('patient_auth.phone_verified_label') }}</span>
        <span class="font-semibold tabular-nums text-zinc-900" dir="ltr">+{{ $phone }}</span>
    </div>

    <form wire:submit="registerPatient" class="patient-auth-form space-y-2 sm:space-y-3">
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-x-3 sm:gap-y-2">
            <flux:input
                wire:model.blur="name"
                type="text"
                autocomplete="name"
                required
                :label="__('patient_auth.full_name')"
            />

            <flux:input
                wire:model.blur="email"
                type="email"
                autocomplete="email"
                required
                :label="__('patient_auth.email')"
            />
        </div>

        <flux:field>
            <flux:label>{{ __('patient_auth.gender') }}</flux:label>
            <div class="patient-gender-segmented">
                <flux:radio.group variant="segmented" wire:model.live="gender" class="w-full">
                    <flux:radio value="male" :label="__('patient_auth.gender_male')" />
                    <flux:radio value="female" :label="__('patient_auth.gender_female')" />
                </flux:radio.group>
            </div>
            <flux:error name="gender" />
        </flux:field>

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-x-3 sm:gap-y-2">
            <flux:input wire:model="password" viewable required type="password" :label="__('patient_auth.password')" />

            <flux:input
                wire:model="password_confirmation"
                viewable
                required
                type="password"
                :label="__('patient_auth.password_confirm')"
            />
        </div>

        @error('phone')
            <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
        @enderror

        <flux:button variant="primary" type="submit" class="patient-auth-primary-btn w-full !mt-1 sm:!mt-2" wire:loading.attr="disabled">
            {{ __('patient_auth.cta_register') }}
        </flux:button>
    </form>
</div>
