<?php

use Flux\Flux;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient')] #[Title('Session filter')] class extends Component
{
    public string $specialistRole = 'psychiatrist';

    public string $genderPreference = 'both';

    public string $durationMinutes = '30';

    public string $languagePreference = 'both';

    /** @var list<string> */
    public array $subspecialties = [];

    public bool $subspecialtiesExpanded = false;

    /**
     * @return list<string>
     */
    #[Computed]
    public function subspecialtyKeys(): array
    {
        return config('session_filter.subspecialty_keys', []);
    }

    public function toggleSubspecialty(string $key): void
    {
        if ($key === '') {
            return;
        }

        if (in_array($key, $this->subspecialties, true)) {
            $this->subspecialties = array_values(array_filter(
                $this->subspecialties,
                static fn (string $k): bool => $k !== $key
            ));

            return;
        }

        $this->subspecialties = array_values(array_unique([...$this->subspecialties, $key]));
    }

    public function subspecialtyIsSelected(string $key): bool
    {
        return in_array($key, $this->subspecialties, true);
    }

    /** @return list<string> */
    #[Computed]
    public function visibleSubspecialtyKeys(): array
    {
        $keys = $this->subspecialtyKeys;
        $limit = (int) config('session_filter.subspecialties_collapsed_count', 7);

        if ($this->subspecialtiesExpanded || count($keys) <= $limit) {
            return $keys;
        }

        return array_slice($keys, 0, $limit);
    }

    public function toggleSubspecialtiesExpanded(): void
    {
        $this->subspecialtiesExpanded = ! $this->subspecialtiesExpanded;
    }

    /**
     * @return array<string, mixed>
     */
    public function preferenceSnapshot(): array
    {
        return [
            'specialist_role' => $this->specialistRole,
            'gender_preference' => $this->genderPreference,
            'duration_minutes' => $this->durationMinutes,
            'language_preference' => $this->languagePreference,
            'subspecialties' => array_values(array_unique($this->subspecialties)),
        ];
    }

    public function proceedNext(): void
    {
        Session::put('session_filter_preferences', $this->preferenceSnapshot());

        Flux::toast(variant: 'success', text: __('session_filter.next_toast'));

        $this->redirect(route('patient.schedule.specialists'));
    }

    public function proceedSkip(): void
    {
        Session::forget('session_filter_preferences');

        Flux::toast(text: __('session_filter.skip_toast'));

        $this->redirect(route('patient.home'));
    }
}; ?>

<div class="schedule-session-accent mx-auto max-w-4xl space-y-8 px-4 py-6 pb-28 [--color-accent:#0b163e] [--color-accent-content:#0b163e] [--color-accent-foreground:#ffffff] sm:pb-12">
    <div>
        <flux:heading level="2" size="xl" class="font-semibold text-zinc-900">
            {{ __('session_filter.title') }}
        </flux:heading>
        <flux:heading level="3" size="lg" class="mt-6 text-zinc-800">
            {{ __('session_filter.filter_heading') }}
        </flux:heading>
    </div>

    <div class="space-y-8">
        <section class="space-y-3" aria-labelledby="sess-spec-kind">
            <flux:heading id="sess-spec-kind" level="4" size="sm" class="text-zinc-600">
                {{ __('session_filter.sections.specialist') }}
            </flux:heading>
            <flux:radio.group variant="pills" wire:model.live="specialistRole" class="gap-2 sm:gap-3">
                <flux:radio value="psychiatrist">{{ __('session_filter.sections.specialist_kind.psychiatrist') }}</flux:radio>
                <flux:radio value="consultant">{{ __('session_filter.sections.specialist_kind.consultant') }}</flux:radio>
                <flux:radio value="psychologist_non_md">{{ __('session_filter.sections.specialist_kind.psychologist_non_md') }}</flux:radio>
            </flux:radio.group>
        </section>

        <flux:separator variant="subtle" />

        <section class="space-y-3" aria-labelledby="sess-gender">
            <flux:heading id="sess-gender" level="4" size="sm" class="text-zinc-600">
                {{ __('session_filter.sections.gender_pref') }}
            </flux:heading>
            <flux:radio.group variant="pills" wire:model.live="genderPreference">
                <flux:radio value="male">{{ __('session_filter.sections.gender.male') }}</flux:radio>
                <flux:radio value="female">{{ __('session_filter.sections.gender.female') }}</flux:radio>
                <flux:radio value="both">{{ __('session_filter.sections.gender.both') }}</flux:radio>
            </flux:radio.group>
        </section>

        <flux:separator variant="subtle" />

        <section class="space-y-3" aria-labelledby="sess-duration">
            <flux:heading id="sess-duration" level="4" size="sm" class="text-zinc-600">
                {{ __('session_filter.sections.duration') }}
            </flux:heading>
            <flux:radio.group variant="pills" wire:model.live="durationMinutes">
                <flux:radio value="15">{{ __('session_filter.sections.minutes.15') }}</flux:radio>
                <flux:radio value="30">{{ __('session_filter.sections.minutes.30') }}</flux:radio>
                <flux:radio value="45">{{ __('session_filter.sections.minutes.45') }}</flux:radio>
                <flux:radio value="60">{{ __('session_filter.sections.minutes.60') }}</flux:radio>
            </flux:radio.group>
        </section>

        <flux:separator variant="subtle" />

        <section class="space-y-3" aria-labelledby="sess-lang">
            <flux:heading id="sess-lang" level="4" size="sm" class="text-zinc-600">
                {{ __('session_filter.sections.language') }}
            </flux:heading>
            <flux:radio.group variant="pills" wire:model.live="languagePreference">
                <flux:radio value="ar">{{ __('session_filter.sections.lang.ar') }}</flux:radio>
                <flux:radio value="en">{{ __('session_filter.sections.lang.en') }}</flux:radio>
                <flux:radio value="both">{{ __('session_filter.sections.lang.both') }}</flux:radio>
            </flux:radio.group>
        </section>

        <flux:separator variant="subtle" />

        <section class="space-y-3" aria-labelledby="sess-subs">
            <flux:heading id="sess-subs" level="4" size="sm" class="text-zinc-600">
                {{ __('session_filter.sections.subspecialties') }}
            </flux:heading>
            <div class="flex flex-wrap gap-2">
                @foreach ($this->visibleSubspecialtyKeys as $key)
                    <button
                        type="button"
                        wire:key="sub-{{ $key }}"
                        wire:click="toggleSubspecialty('{{ $key }}')"
                        aria-pressed="{{ $this->subspecialtyIsSelected($key) ? 'true' : 'false' }}"
                        class="@if ($this->subspecialtyIsSelected($key)) border-mashora-brand bg-mashora-brand/12 text-mashora-brand @else border-zinc-200 bg-white text-zinc-800 hover:border-zinc-300 @endif rounded-full border px-3 py-1.5 text-start text-xs font-medium leading-snug shadow-sm transition sm:text-sm"
                    >
                        {{ __('session_filter.sections.subspecialty_labels.'.$key) }}
                    </button>
                @endforeach
            </div>
            @if (count($this->subspecialtyKeys) > (int) config('session_filter.subspecialties_collapsed_count', 7))
                <div>
                    <flux:button variant="ghost" size="sm" wire:click="toggleSubspecialtiesExpanded" type="button" class="!px-0 text-[#1565c0] hover:!text-[#0B163E]">
                        {{ $this->subspecialtiesExpanded ? __('session_filter.show_less') : __('session_filter.show_more') }}
                    </flux:button>
                </div>
            @endif
        </section>
    </div>

    <div class="flex w-full flex-col gap-3 border-t border-zinc-200/80 pt-6 sm:flex-row sm:justify-end sm:border-t-0 sm:pt-0">
        <flux:button variant="ghost" wire:click="proceedSkip" type="button" class="min-h-11 w-full sm:w-auto sm:min-w-[9rem]" wire:loading.attr="disabled">
            {{ __('session_filter.skip') }}
        </flux:button>
        <flux:button
            variant="primary"
            wire:click="proceedNext"
            type="button"
            class="min-h-11 w-full border-[#0B163E] !bg-[#0B163E] !text-white hover:!brightness-[0.97] focus-visible:!ring-[#0B163E]/40 sm:w-auto sm:min-w-[9rem]"
            wire:loading.attr="disabled"
        >
            {{ __('session_filter.next') }}
        </flux:button>
    </div>
</div>
