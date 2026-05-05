<?php

use App\Support\SpecialistCatalog;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient')] #[Title('Specialists')] class extends Component
{
    /** @var array<string, int> */
    public array $likeCounts = [];

    /** @var list<array<string, mixed>>|null */
    protected ?array $filteredSpecialistsCache = null;

    /**
     * @return list<array<string, mixed>>
     */
    protected function filteredSpecialists(): array
    {
        return $this->filteredSpecialistsCache ??= SpecialistCatalog::filtered(
            Session::get('session_filter_preferences')
        );
    }

    public function mount(): void
    {
        foreach ($this->filteredSpecialists() as $specialist) {
            $this->likeCounts[$specialist['id']] = (int) ($specialist['likes'] ?? 0);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function specialists(): array
    {
        return $this->filteredSpecialists();
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
        $specialist = collect($this->specialists)->firstWhere('id', $specialistId);
        if ($specialist === null) {
            return;
        }

        $doctorId = $specialist['doctor_database_id'] ?? null;
        if (is_int($doctorId) && $doctorId > 0) {
            $date = now()->timezone(config('app.timezone'))->format('Y-m-d');

            $this->redirect(
                route('patient.book-appointments', ['doctor' => $doctorId], false)
                    . '?'.http_build_query([
                        'date' => $date,
                        'duration' => (int) ($specialist['session_minutes'] ?? 15),
                        'time' => $slot,
                    ])
            );

            return;
        }

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

    @if (count($this->specialists) === 0)
        <div class="rounded-2xl border border-zinc-200/90 bg-white p-8 text-center shadow-md shadow-black/10">
            <flux:heading size="lg" class="text-zinc-900">{{ __('specialist_results.no_results_title') }}</flux:heading>
            <flux:text class="mx-auto mt-2 max-w-md text-zinc-600">{{ __('specialist_results.no_results_hint') }}</flux:text>
            <flux:button
                :href="route('patient.schedule.filter')"
                variant="primary"
                class="mt-6 border-[#0B163E] !bg-[#0B163E] !text-white hover:!brightness-[0.97]"
                wire:navigate
            >
                {{ __('specialist_results.adjust_filters') }}
            </flux:button>
        </div>
    @else
        <div class="grid gap-6 md:grid-cols-2">
            @foreach ($this->specialists as $specialist)
                @include('partials.patient-specialist-result-card', [
                    'specialist' => $specialist,
                    'likes' => $this->likeCounts[$specialist['id']] ?? (int) $specialist['likes'],
                ])
            @endforeach
        </div>
    @endif
</div>
