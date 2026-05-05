<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Ratings')] class extends Component
{
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('doctor.ratings.title') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-600">{{ __('doctor.ratings.subtitle') }}</flux:text>
    </div>

    <div class="rounded-2xl border border-dashed border-zinc-200 bg-white p-8 text-center shadow-sm">
        <flux:text class="text-zinc-500">{{ __('doctor.ratings.empty') }}</flux:text>
    </div>
</div>
