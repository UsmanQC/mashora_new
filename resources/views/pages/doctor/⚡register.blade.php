<?php

use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor-guest')] #[Title('Doctor registration')] class extends Component
{
    public string $phone = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
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

<div class="space-y-8">
    <div class="space-y-2 text-center sm:text-start">
        <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.auth.register_title') }}</flux:heading>
    </div>

    <form wire:submit="register" class="space-y-4">
        <flux:input wire:model="phone" type="hidden" autocomplete="tel" />
        <flux:error name="phone" />
        <flux:text class="text-sm text-zinc-600">
            {{ __('Creating account for') }} <span class="font-semibold text-zinc-900">{{ $phone }}</span>
        </flux:text>

        <flux:field>
            <flux:label>{{ __('doctor.auth.email') }}</flux:label>
            <flux:input wire:model="email" type="email" autocomplete="email" />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('doctor.auth.password') }}</flux:label>
            <flux:input wire:model="password" type="password" autocomplete="new-password" viewable />
            <flux:error name="password" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('doctor.auth.password_confirm') }}</flux:label>
            <flux:input wire:model="password_confirmation" type="password" autocomplete="new-password" viewable />
            <flux:error name="password_confirmation" />
        </flux:field>

        <flux:button class="w-full bg-[#132A6E]! text-white! hover:brightness-95!" type="submit" variant="primary">
            {{ __('doctor.auth.register_submit') }}
        </flux:button>
    </form>

    
</div>
