<?php

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
    public string $phone = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
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

<div class="space-y-8">
    <div class="space-y-2 text-center sm:text-start">
        <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.auth.sign_in') }}</flux:heading>
        <flux:text class="text-zinc-600">{{ __('doctor.welcome_subtitle') }}</flux:text>
    </div>

    <form wire:submit="login" class="space-y-5">
        <flux:field>
            <flux:label>{{ __('doctor.auth.phone') }}</flux:label>
            <flux:input wire:model="phone" type="tel" autocomplete="username" />
            <flux:error name="phone" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('doctor.auth.password') }}</flux:label>
            <flux:input wire:model="password" type="password" autocomplete="current-password" />
            <flux:error name="password" />
        </flux:field>

        <flux:checkbox wire:model.live="remember" :label="__('doctor.auth.remember')" />

        <flux:button class="w-full !bg-[#132A6E] !text-white hover:!brightness-95" type="submit" variant="primary">
            {{ __('doctor.auth.sign_in') }}
        </flux:button>
    </form>

    <div class="text-center text-sm text-zinc-600">
        <flux:link :href="route('doctor.register')" wire:navigate>{{ __('doctor.welcome_register') }}</flux:link>
    </div>
</div>
