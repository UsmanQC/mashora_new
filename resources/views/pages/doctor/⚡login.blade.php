<?php

use App\Livewire\Concerns\RedirectsAuthenticatedDoctorsFromGuestPages;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor-guest')] #[Title('Doctor sign in')] class extends Component
{
    use RedirectsAuthenticatedDoctorsFromGuestPages;

    public string $phone = '';

    public string $password = '';

    public bool $remember = false;

    public function mount(): void
    {
        if ($this->redirectAuthenticatedDoctorAwayFromGuestPages()) {
            return;
        }

        $this->phone = (string) request()->string('phone');

        if ($this->phone === '') {
            $this->redirect(route('doctor.welcome'), navigate: true);
        }
    }

    public function login(): void
    {
        if ($this->redirectAuthenticatedDoctorAwayFromGuestPages()) {
            return;
        }

        $this->phone = (string) (preg_replace('/\D/', '', $this->phone) ?? '');

        $this->validate([
            'phone' => ['required', 'string', 'min:8', 'max:24'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($this->phone);

        if (! Auth::guard('doctor')->attempt(
            ['phone' => $this->phone, 'password' => $this->password],
            $this->remember
        )) {
            RateLimiter::hit($this->throttleKey($this->phone));

            throw ValidationException::withMessages([
                'phone' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($this->phone));

        session()->regenerate();

        $this->redirect(route('doctor.dashboard'), navigate: true);
    }

    private function ensureIsNotRateLimited(string $phone): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($phone), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey($phone));

        throw ValidationException::withMessages([
            'phone' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    private function throttleKey(string $phone): string
    {
        return Str::transliterate(Str::lower($phone).'|'.request()->ip());
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

    <flux:heading size="lg" class="patient-auth-heading !text-zinc-900 sm:!text-2xl">{{ __('doctor.auth.sign_in') }}</flux:heading>
    <flux:text class="mt-1 text-sm sm:mt-2 sm:text-base">{{ __('doctor.auth.login_password_lead') }}</flux:text>

    <flux:text class="mt-2 text-sm font-medium tabular-nums text-zinc-800 sm:mt-3 sm:text-base" dir="ltr">
        +{{ $phone }}
    </flux:text>

    <form wire:submit="login" class="mt-5 space-y-4 sm:mt-8 sm:space-y-5">
        <flux:input wire:model="phone" type="hidden" autocomplete="username" />

        <flux:input
            wire:model="password"
            type="password"
            autocomplete="current-password"
            viewable
            required
            :label="__('doctor.auth.password')"
        />

        @error('phone')
            <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
        @enderror

        @error('password')
            <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
        @enderror

        <flux:checkbox wire:model.live="remember" :label="__('doctor.auth.remember')" />

        <flux:button variant="primary" type="submit" class="patient-auth-primary-btn w-full">
            {{ __('doctor.auth.sign_in') }}
        </flux:button>

        <div class="text-center text-sm text-zinc-500">
            <flux:button
                type="button"
                variant="ghost"
                size="sm"
                :href="route('doctor.welcome')"
                wire:navigate
                class="mx-auto"
            >
                {{ __('doctor.auth.not_my_number') }}
            </flux:button>
        </div>
    </form>
</div>
