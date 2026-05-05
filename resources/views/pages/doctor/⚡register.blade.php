<?php

use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor-guest')] #[Title('Doctor registration')] class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(): void
    {
        $normalized = (string) (preg_replace('/\D/', '', $this->phone) ?? '');
        $this->phone = $normalized;

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'min:8', 'max:24', Rule::unique('doctors', 'phone')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** @var Doctor $doctor */
        $doctor = Doctor::query()->create([
            'name' => $this->name,
            'email' => $this->email !== '' ? $this->email : null,
            'phone' => $normalized,
            'password' => $this->password,
            'status' => 'pending',
            'profile_completed' => false,
        ]);

        Auth::guard('doctor')->login($doctor);

        session()->regenerate();

        $this->redirect(route('doctor.register.basic.info'), navigate: true);
    }
}; ?>

<div class="space-y-8">
    <div class="space-y-2 text-center sm:text-start">
        <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.auth.register_title') }}</flux:heading>
    </div>

    <form wire:submit="register" class="space-y-4">
        <flux:field>
            <flux:label>{{ __('doctor.auth.name') }}</flux:label>
            <flux:input wire:model="name" autocomplete="name" />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('doctor.auth.email') }}</flux:label>
            <flux:input wire:model="email" type="email" autocomplete="email" />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('doctor.auth.phone') }}</flux:label>
            <flux:input wire:model="phone" type="tel" autocomplete="tel" />
            <flux:error name="phone" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('doctor.auth.password') }}</flux:label>
            <flux:input wire:model="password" type="password" autocomplete="new-password" />
            <flux:error name="password" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('doctor.auth.password_confirm') }}</flux:label>
            <flux:input wire:model="password_confirmation" type="password" autocomplete="new-password" />
            <flux:error name="password_confirmation" />
        </flux:field>

        <flux:button class="w-full !bg-[#132A6E] !text-white hover:!brightness-95" type="submit" variant="primary">
            {{ __('doctor.auth.register_submit') }}
        </flux:button>
    </form>

    <div class="text-center text-sm text-zinc-600">
        <flux:link :href="route('doctor.login')" wire:navigate>{{ __('doctor.auth.has_account') }}</flux:link>
    </div>
</div>
