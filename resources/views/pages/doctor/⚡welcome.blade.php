<?php

use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor-guest')] #[Title('Doctor portal')] class extends Component
{
}; ?>

<div class="flex flex-col items-center gap-8">
    <div class="space-y-2 text-center">
        <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.welcome_title') }}</flux:heading>
        <flux:text class="text-zinc-600">{{ __('doctor.welcome_subtitle') }}</flux:text>
        <flux:text class="text-xs text-zinc-500">{{ __('doctor.welcome_invite_hint') }}</flux:text>
    </div>

    <div class="flex w-full flex-col gap-3 sm:flex-row sm:justify-center">
        <flux:button
            class="w-full !bg-[#132A6E] !text-white hover:!brightness-95 sm:w-auto sm:min-w-[10rem]"
            :href="route('doctor.login')"
            wire:navigate
            variant="primary"
        >
            {{ __('doctor.welcome_sign_in') }}
        </flux:button>
        <flux:button class="w-full sm:w-auto sm:min-w-[10rem]" :href="route('doctor.register')" wire:navigate variant="outline">
            {{ __('doctor.welcome_register') }}
        </flux:button>
    </div>

    @if (config('doctor.registration_invite_only') && app()->environment('local'))
        <flux:text class="text-center text-xs font-mono text-zinc-500">
            Dev signed URL: {{ URL::temporarySignedRoute('doctor.register', now()->addHours(2)) }}
        </flux:text>
    @endif
</div>
