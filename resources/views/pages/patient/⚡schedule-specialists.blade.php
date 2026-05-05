<?php

use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient')] #[Title('Specialists')] class extends Component
{
    /** @var array<string, int> */
    public array $likeCounts = [];

    public function mount(): void
    {
        $cards = Lang::get('specialist_results.demo_cards');

        foreach (is_array($cards) ? $cards : [] as $specialist) {
            $this->likeCounts[$specialist['id']] = (int) $specialist['likes'];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function specialists(): array
    {
        $cards = Lang::get('specialist_results.demo_cards');

        return is_array($cards) ? $cards : [];
    }

    public function incrementLike(string $id): void
    {
        if (! array_key_exists($id, $this->likeCounts)) {
            return;
        }

        $this->likeCounts[$id]++;
    }

    public function pickSlot(string $specialistId, string $slot): void
    {
        Session::flash('patient_demo_slot_pick', [
            'specialist_id' => $specialistId,
            'slot' => $slot,
        ]);

        $formatted = Carbon::createFromFormat('H:i', $slot)
            ->timezone(config('app.timezone'))
            ->locale(app()->getLocale())
            ->translatedFormat('g:i a');

        Flux::toast(text: __('specialist_results.slot_selected_toast', ['time' => $formatted]));
    }

    #[Computed]
    public function hasSavedFilters(): bool
    {
        return Session::has('session_filter_preferences');
    }
}; ?>

<div class="relative mx-auto max-w-6xl space-y-6 px-4 py-6 pb-28 sm:pb-14">
    <header class="space-y-2">
        <flux:heading size="xl" class="font-semibold text-zinc-900">
            {{ __('specialist_results.page_heading') }}
        </flux:heading>
        <flux:text class="text-zinc-600">
            {{ $this->hasSavedFilters ? __('specialist_results.page_sub_with_filters') : __('specialist_results.page_sub_default') }}
        </flux:text>
    </header>

    <div class="grid gap-6 md:grid-cols-2">
        @foreach ($this->specialists as $specialist)
            @include('partials.patient-specialist-result-card', [
                'specialist' => $specialist,
                'likes' => $this->likeCounts[$specialist['id']] ?? (int) $specialist['likes'],
            ])
        @endforeach
    </div>
</div>
