<?php

use App\Models\Page;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Privacy policy')] class extends Component
{
    public function getPolicyProperty(): ?Page
    {
        return Page::query()
            ->where('use_for', 'doctor')
            ->whereIn('slug', ['privacy-policy', 'privacy_policy'])
            ->first()
            ?? Page::query()->where('slug', 'privacy-policy')->first();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
        <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('Privacy policy') }}</flux:heading>
        <flux:button :href="route('doctor.settings')" wire:navigate variant="ghost" size="sm" icon="arrow-left">{{ __('Back') }}</flux:button>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
        @if ($this->policy)
            <flux:heading size="lg" class="mb-2 font-semibold text-zinc-900">{{ $this->policy->title }}</flux:heading>
            <div class="prose max-w-none text-sm text-zinc-700">
                {!! $this->policy->content_local !!}
            </div>
        @else
            <flux:text class="text-zinc-600">{{ __('Privacy policy content is not available yet.') }}</flux:text>
        @endif
    </div>
</div>
