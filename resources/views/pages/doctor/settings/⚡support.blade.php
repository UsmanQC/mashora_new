<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Support')] class extends Component
{
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
        <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('Support') }}</flux:heading>
        <flux:button :href="route('doctor.settings')" wire:navigate variant="ghost" size="sm" icon="arrow-left">{{ __('Back') }}</flux:button>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
        <flux:text class="text-zinc-700">{{ __('Need help? Contact') }} <a href="mailto:contact@mashora.co" class="font-semibold text-[#3C5CF7] underline">contact@mashora.co</a></flux:text>
    </div>
</div>
