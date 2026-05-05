<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Menu')] class extends Component
{
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.settings.title') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-600">{{ __('doctor.settings.subtitle') }}</flux:text>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-6 shadow-sm">
        <flux:text class="text-sm text-zinc-600">{{ __('doctor.settings.placeholder') }}</flux:text>
    </div>
</div>
