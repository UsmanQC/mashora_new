<x-layouts::patient-auth>
    <flux:heading size="xl" class="patient-auth-heading">{{ __('patient_auth.forgot_title') }}</flux:heading>
    <flux:text class="mt-2">{{ __('patient_auth.forgot_sub') }}</flux:text>

    <x-auth-session-status class="my-6 text-center" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-6">
        @csrf
        <input type="hidden" name="patient_flow" value="1" />

        <flux:input
            name="email"
            type="email"
            required
            autocomplete="email"
            :label="__('Email address')"
            :value="old('email')"
            placeholder="email@example.com"
        />

        @error('email')
            <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
        @enderror

        <flux:text class="text-sm text-zinc-500">{{ __('patient_auth.forgot_requires_email_note') }}</flux:text>

        <flux:button variant="primary" type="submit" class="patient-auth-primary-btn w-full">{{ __('Email password reset link') }}</flux:button>

        <flux:text class="text-center text-sm text-zinc-500">
            <flux:link :href="route('patient.auth.sign-in')" class="cursor-pointer font-medium text-mashora-brand hover:underline" wire:navigate>
                {{ __('patient_auth.cta_login') }}
            </flux:link>
        </flux:text>
    </form>
</x-layouts::patient-auth>
