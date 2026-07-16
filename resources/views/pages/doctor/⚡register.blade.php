<?php

use App\Livewire\Concerns\RedirectsAuthenticatedDoctorsFromGuestPages;
use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor-guest')] #[Title('Doctor registration')] class extends Component
{
    use RedirectsAuthenticatedDoctorsFromGuestPages;

    public string $phone = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        if ($this->redirectAuthenticatedDoctorAwayFromGuestPages()) {
            return;
        }

        $raw = (string) request()->string('phone');
        $normalized = (string) (preg_replace('/\D/', '', $raw) ?? '');

        if ($normalized === '') {
            $this->redirect(route('doctor.welcome'), navigate: true);

            return;
        }

        if ((string) session('doctor_otp_verified_phone') !== $normalized) {
            $this->redirect(route('doctor.welcome'), navigate: true);

            return;
        }

        $this->phone = $normalized;
    }

    public function register(): void
    {
        if ($this->redirectAuthenticatedDoctorAwayFromGuestPages()) {
            return;
        }
        $normalized = (string) (preg_replace('/\D/', '', $this->phone) ?? '');
        $this->phone = $normalized;

        $this->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('doctors', 'email')->whereNull('deleted_at')],
            'phone' => ['required', 'string', 'min:8', 'max:24', Rule::unique('doctors', 'phone')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** @var Doctor $doctor */
        $doctor = Doctor::query()->create([
            'name' => null,
            'email' => $this->email,
            'phone' => $normalized,
            'password' => $this->password,
            'status' => 'pending',
            'profile_completed' => false,
        ]);

        Auth::guard('doctor')->login($doctor);

        session()->regenerate();

        session()->forget('doctor_otp_verified_phone');

        $this->redirect(route('doctor.register.basic.info'), navigate: true);
    }
}; ?>

<div class="flex min-h-0 w-full flex-col text-start">
    <div class="mb-2 sm:mb-3">
        <flux:button
            :href="route('doctor.welcome')"
            wire:navigate
            variant="ghost"
            size="sm"
            icon="arrow-left"
            aria-label="{{ __('pagination.previous') }}"
            title="{{ __('pagination.previous') }}"
            class="px-0 text-zinc-600 hover:text-zinc-900"
        />
    </div>

    <flux:heading size="lg" class="patient-auth-heading !text-zinc-900 text-balance sm:!text-2xl">{{ __('doctor.auth.register_title') }}</flux:heading>
    <flux:text class="mt-1 text-sm leading-snug text-balance text-zinc-600 sm:mt-2 sm:text-base">{{ __('doctor.auth.register_sub') }}</flux:text>

    <div class="mt-2 mb-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-600 sm:mt-3 sm:mb-4 sm:text-sm">
        <flux:icon name="device-phone-mobile" variant="mini" class="size-3.5 shrink-0 text-[#10B981] sm:size-4" />
        <span class="font-medium text-zinc-800">{{ __('doctor.auth.phone_verified_label') }}</span>
        <span class="font-semibold tabular-nums text-zinc-900" dir="ltr">+{{ $phone }}</span>
    </div>

    <form wire:submit="register" class="patient-auth-form space-y-2 sm:space-y-3">
        <flux:input wire:model="phone" type="hidden" autocomplete="tel" />

        <flux:input
            wire:model.blur="email"
            type="email"
            autocomplete="email"
            required
            :label="__('doctor.auth.email')"
        />

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-x-3 sm:gap-y-2">
            <flux:input
                wire:model="password"
                type="password"
                autocomplete="new-password"
                viewable
                required
                :label="__('doctor.auth.password')"
            />

            <flux:input
                wire:model="password_confirmation"
                type="password"
                autocomplete="new-password"
                viewable
                required
                :label="__('doctor.auth.password_confirm')"
            />
        </div>

        @error('phone')
            <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
        @enderror

        <flux:button variant="primary" type="submit" class="patient-auth-primary-btn w-full !mt-1 sm:!mt-2">
            {{ __('doctor.auth.register_submit') }}
        </flux:button>
    </form>
</div>
