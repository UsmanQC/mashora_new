<x-layouts::patient-auth>
    <flux:heading size="xl" class="patient-auth-heading">{{ __('patient_auth.login_title') }}</flux:heading>
    <flux:text class="mt-2">{{ __('patient_auth.login_sub') }}</flux:text>

    <x-auth-session-status class="my-6 text-center" :status="session('status')" />

    <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-6">
        @csrf
        <input type="hidden" name="patient_flow" value="1" />

        <flux:input
            name="email"
            :label="__('patient_auth.login_field')"
            type="text"
            required
            autofocus
            autocomplete="username"
            :value="old('email', request('phone'))"
        />

        <div class="relative">
            <flux:input
                name="password"
                :label="__('patient_auth.password')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />
            @if (Route::has('password.request'))
                <flux:link class="absolute end-0 top-0 text-sm font-medium text-mashora-brand hover:underline" :href="route('patient.auth.forgot-password')" wire:navigate>
                    {{ __('patient_auth.forgot_password') }}
                </flux:link>
            @endif
        </div>

        <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

        <flux:button variant="primary" type="submit" class="patient-auth-primary-btn w-full">{{ __('patient_auth.cta_login') }}</flux:button>

        <flux:text class="block text-center text-sm text-zinc-500">
            <flux:link :href="route('patient.auth.start')" class="cursor-pointer font-medium text-mashora-brand hover:underline" wire:navigate>
                {{ __('patient_auth.cta_register') }}
            </flux:link>
        </flux:text>
    </form>
</x-layouts::patient-auth>
