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

    public function mount(): void
    {
        $this->phone = (string) request()->string('phone');

        if ($this->phone === '') {
            $this->redirect(route('doctor.welcome'), navigate: true);
        }
    }

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

<div class="flex min-h-full items-center">
    <div class="w-full py-2">
        <div class="mb-6 text-center">
            <div class="mx-auto mb-3 inline-flex h-11 w-11 items-center justify-center rounded-full bg-[#132A6E]/10 text-[#132A6E]">
                <flux:icon name="shield-check" class="size-5" />
            </div>
            <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.auth.sign_in') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-600">{{ __('doctor.welcome_subtitle') }}</flux:text>
        </div>

        <form wire:submit="login" class="space-y-5">
            <flux:text class="text-center text-sm text-zinc-600">
                {{ __('Enter your password for') }} <span class="font-semibold text-zinc-900">{{ $phone }}</span>
            </flux:text>
            <div class="text-center">
                <flux:link :href="route('doctor.welcome')" wire:navigate>{{ __('Not my number') }}</flux:link>
            </div>

            <flux:field>
                <flux:input wire:model="phone" type="hidden" autocomplete="username" />
                <flux:error name="phone" />
                <flux:label>{{ __('doctor.auth.password') }}</flux:label>
                <flux:input wire:model="password" type="password" autocomplete="current-password" viewable />
                <flux:error name="password" />
            </flux:field>

            <flux:checkbox wire:model.live="remember" :label="__('doctor.auth.remember')" />

            <flux:button class="w-full bg-[#132A6E]! text-white! hover:brightness-95!" type="submit" variant="primary">
                {{ __('doctor.auth.sign_in') }}
            </flux:button>
        </form>

    </div>
</div>
