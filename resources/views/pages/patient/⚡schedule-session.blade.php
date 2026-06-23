<?php

use App\Models\Degree;
use App\Models\Speciality;
use Flux\Flux;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient')] #[Title('Schedule a session')] class extends Component
{
    public string $degree_id = '';

    public string $genderPreference = '';

    public string $durationMinutes = '';

    public string $languagePreference = '';

    /** @var list<string> Speciality primary keys as strings (matches `specialities.id`) */
    public array $subspecialties = [];

    public bool $subspecialtiesExpanded = false;

    /**
     * @return list<array{value: string, label: string}>
     */
    #[Computed]
    public function specialistKindOptions(): array
    {
        $degrees = Degree::query()
            ->where('status', true)
            ->orderBy('id')
            ->get(['id', 'title', 'title_ar']);

        if ($degrees->isEmpty()) {
            return [
                ['value' => '1', 'label' => __('session_filter.sections.specialist_kind.psychiatrist')],
                ['value' => '2', 'label' => __('session_filter.sections.specialist_kind.consultant')],
                ['value' => '3', 'label' => __('session_filter.sections.specialist_kind.psychologist_non_md')],
            ];
        }

        $isAr = app()->getLocale() === 'ar';

        return $degrees
            ->values()
            ->map(function (Degree $degree) use ($isAr): array {
                $label = $isAr
                    ? (filled($degree->title_ar) ? (string) $degree->title_ar : (string) $degree->title)
                    : (filled($degree->title) ? (string) $degree->title : (string) $degree->title_ar);

                return [
                    'value' => (string) $degree->id,
                    'label' => $label,
                ];
            })
            ->all();
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    #[Computed]
    public function specialityOptions(): array
    {
        return Speciality::query()
            ->where('status', true)
            ->orderBy('id')
            ->get(['id', 'title', 'title_ar'])
            ->map(function (Speciality $s): array {
                $isAr = app()->getLocale() === 'ar';
                $label = $isAr
                    ? (filled($s->title_ar) ? (string) $s->title_ar : (string) $s->title)
                    : (filled($s->title) ? (string) $s->title : (string) $s->title_ar);

                return [
                    'id' => (string) $s->id,
                    'label' => $label,
                ];
            })
            ->all();
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    #[Computed]
    public function visibleSpecialityOptions(): array
    {
        $all = $this->specialityOptions;
        $limit = (int) config('session_filter.subspecialties_collapsed_count', 7);

        if ($this->subspecialtiesExpanded || count($all) <= $limit) {
            return $all;
        }

        return array_slice($all, 0, $limit);
    }

    public function toggleSubspecialty(string $id): void
    {
        if ($id === '') {
            return;
        }

        if (in_array($id, $this->subspecialties, true)) {
            $this->subspecialties = array_values(array_filter(
                $this->subspecialties,
                static fn (string $k): bool => $k !== $id
            ));

            return;
        }

        $this->subspecialties = array_values(array_unique([...$this->subspecialties, $id]));
    }

    public function subspecialtyIsSelected(string $id): bool
    {
        return in_array($id, $this->subspecialties, true);
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
            'degree_id' => $this->degree_id,
            'gender_preference' => $this->genderPreference,
            'duration_minutes' => $this->durationMinutes,
            'language_preference' => $this->languagePreference,
            'subspecialties' => array_values(array_unique($this->subspecialties)),
        ];
    }

    public function proceedNext(): void
    {
        $availableDegreeIds = array_column($this->specialistKindOptions, 'value');

        $this->validate([
            'degree_id' => ['required', 'string', 'in:'.implode(',', $availableDegreeIds)],
            'genderPreference' => ['required', 'in:male,female,both'],
            'durationMinutes' => ['required', 'in:15,30,45,60'],
            'languagePreference' => ['required', 'in:ar,en,both'],
        ], [
            'degree_id.required' => __('session_filter.validation.specialist_required'),
            'genderPreference.required' => __('session_filter.validation.gender_required'),
            'durationMinutes.required' => __('session_filter.validation.duration_required'),
            'languagePreference.required' => __('session_filter.validation.language_required'),
        ]);

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

    #[Computed]
    public function requiredFiltersCompleted(): int
    {
        return collect([
            $this->degree_id,
            $this->genderPreference,
            $this->durationMinutes,
            $this->languagePreference,
        ])->filter(fn (string $value): bool => filled($value))->count();
    }

    public function requiredFilterProgressPercent(): int
    {
        return (int) round(($this->requiredFiltersCompleted / 4) * 100);
    }
}; ?>

<div class="pb-28 sm:pb-10">
    <div class="mx-auto max-w-5xl space-y-5 px-4 py-6 lg:space-y-6 lg:px-8 lg:py-8">
        {{-- Hero — soft vertical 50/50 (top + bottom) --}}
        <header class="grid min-h-[11rem] grid-rows-2 overflow-hidden rounded-2xl border border-blue-100/90 bg-white shadow-sm sm:min-h-[10.5rem]">
            <div class="flex items-center gap-4 bg-blue-50/70 p-4 sm:p-5">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#1565c0]/12 text-[#1565c0] lg:size-12">
                    <flux:icon name="calendar-days" variant="mini" class="size-5 lg:size-6" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-zinc-900 sm:text-base lg:text-lg">{{ __('session_filter.title') }}</p>
                    <p class="mt-0.5 text-xs text-zinc-600 sm:text-sm">{{ __('session_filter.subtitle') }}</p>
                </div>
            </div>

            <div class="flex flex-col justify-center border-t border-blue-100/90 bg-white p-4 sm:p-5">
                <div class="flex items-center justify-between gap-3 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    <span>{{ __('session_filter.filter_heading') }}</span>
                    <span class="tabular-nums text-[#1565c0]">
                        {{ __('session_filter.progress', ['current' => $this->requiredFiltersCompleted, 'total' => 4]) }}
                    </span>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-blue-100/60">
                    <div
                        class="h-full rounded-full bg-[#42a5f5] transition-all duration-300 ease-out"
                        style="width: {{ $this->requiredFilterProgressPercent() }}%"
                        role="progressbar"
                        aria-valuenow="{{ $this->requiredFiltersCompleted }}"
                        aria-valuemin="0"
                        aria-valuemax="4"
                    ></div>
                </div>
            </div>
        </header>

        <div class="session-filter-shell w-full overflow-hidden rounded-2xl border border-zinc-200/90 bg-white shadow-sm sm:rounded-3xl">
        <section class="session-filter-section" aria-labelledby="sess-spec-kind">
            <div class="flex items-start gap-4">
                <div class="session-filter-section-icon session-filter-section-icon--specialist">
                    <flux:icon name="user-circle" class="size-5" />
                </div>
                <div class="min-w-0 flex-1 space-y-3">
                    <flux:heading id="sess-spec-kind" level="4" size="sm" class="font-semibold text-zinc-900">
                        {{ __('session_filter.sections.specialist') }}
                    </flux:heading>
                    <div class="flex flex-wrap gap-2 sm:gap-2.5" role="radiogroup" aria-labelledby="sess-spec-kind">
                        @foreach ($this->specialistKindOptions as $option)
                            <button
                                type="button"
                                wire:key="degree-{{ $option['value'] }}"
                                wire:click="$set('degree_id', '{{ $option['value'] }}')"
                                aria-pressed="{{ $degree_id === $option['value'] ? 'true' : 'false' }}"
                                @class([
                                    'session-filter-chip',
                                    'session-filter-chip--active' => $degree_id === $option['value'],
                                ])
                            >
                                {{ $option['label'] }}
                            </button>
                        @endforeach
                    </div>
                    <flux:error name="degree_id" />
                </div>
            </div>
        </section>

        <section class="session-filter-section" aria-labelledby="sess-gender">
            <div class="flex items-start gap-4">
                <div class="session-filter-section-icon session-filter-section-icon--gender">
                    <flux:icon name="users" class="size-5" />
                </div>
                <div class="min-w-0 flex-1 space-y-3">
                    <flux:heading id="sess-gender" level="4" size="sm" class="font-semibold text-zinc-900">
                        {{ __('session_filter.sections.gender_pref') }}
                    </flux:heading>
                    <div class="flex flex-wrap gap-2 sm:gap-2.5" role="radiogroup" aria-labelledby="sess-gender">
                        @foreach (['male' => __('session_filter.sections.gender.male'), 'female' => __('session_filter.sections.gender.female'), 'both' => __('session_filter.sections.gender.both')] as $value => $label)
                            <button
                                type="button"
                                wire:key="gender-{{ $value }}"
                                wire:click="$set('genderPreference', '{{ $value }}')"
                                aria-pressed="{{ $genderPreference === $value ? 'true' : 'false' }}"
                                @class([
                                    'session-filter-chip',
                                    'session-filter-chip--active' => $genderPreference === $value,
                                ])
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    <flux:error name="genderPreference" />
                </div>
            </div>
        </section>

        <section class="session-filter-section" aria-labelledby="sess-duration">
            <div class="flex items-start gap-4">
                <div class="session-filter-section-icon session-filter-section-icon--duration">
                    <flux:icon name="clock" class="size-5" />
                </div>
                <div class="min-w-0 flex-1 space-y-3">
                    <flux:heading id="sess-duration" level="4" size="sm" class="font-semibold text-zinc-900">
                        {{ __('session_filter.sections.duration') }}
                    </flux:heading>
                    <div class="flex flex-wrap gap-2 sm:gap-2.5" role="radiogroup" aria-labelledby="sess-duration">
                        @foreach (['15', '30', '45', '60'] as $minutes)
                            <button
                                type="button"
                                wire:key="duration-{{ $minutes }}"
                                wire:click="$set('durationMinutes', '{{ $minutes }}')"
                                aria-pressed="{{ $durationMinutes === $minutes ? 'true' : 'false' }}"
                                @class([
                                    'session-filter-chip',
                                    'session-filter-chip--active' => $durationMinutes === $minutes,
                                ])
                            >
                                {{ __('session_filter.sections.minutes.'.$minutes) }}
                            </button>
                        @endforeach
                    </div>
                    <flux:error name="durationMinutes" />
                </div>
            </div>
        </section>

        <section class="session-filter-section" aria-labelledby="sess-lang">
            <div class="flex items-start gap-4">
                <div class="session-filter-section-icon session-filter-section-icon--language">
                    <flux:icon name="language" class="size-5" />
                </div>
                <div class="min-w-0 flex-1 space-y-3">
                    <flux:heading id="sess-lang" level="4" size="sm" class="font-semibold text-zinc-900">
                        {{ __('session_filter.sections.language') }}
                    </flux:heading>
                    <div class="flex flex-wrap gap-2 sm:gap-2.5" role="radiogroup" aria-labelledby="sess-lang">
                        @foreach (['ar' => __('session_filter.sections.lang.ar'), 'en' => __('session_filter.sections.lang.en'), 'both' => __('session_filter.sections.lang.both')] as $value => $label)
                            <button
                                type="button"
                                wire:key="language-{{ $value }}"
                                wire:click="$set('languagePreference', '{{ $value }}')"
                                aria-pressed="{{ $languagePreference === $value ? 'true' : 'false' }}"
                                @class([
                                    'session-filter-chip',
                                    'session-filter-chip--active' => $languagePreference === $value,
                                ])
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    <flux:error name="languagePreference" />
                </div>
            </div>
        </section>

        <section class="session-filter-section session-filter-section--last" aria-labelledby="sess-subs">
            <div class="flex items-start gap-4">
                <div class="session-filter-section-icon session-filter-section-icon--subspecialty">
                    <flux:icon name="tag" class="size-5" />
                </div>
                <div class="min-w-0 flex-1 space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading id="sess-subs" level="4" size="sm" class="font-semibold text-zinc-900">
                            {{ __('session_filter.sections.subspecialties') }}
                        </flux:heading>
                        <flux:badge size="sm" color="zinc">{{ __('session_filter.optional') }}</flux:badge>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->visibleSpecialityOptions as $opt)
                            <button
                                type="button"
                                wire:key="sub-{{ $opt['id'] }}"
                                wire:click="toggleSubspecialty('{{ $opt['id'] }}')"
                                aria-pressed="{{ $this->subspecialtyIsSelected($opt['id']) ? 'true' : 'false' }}"
                                @class([
                                    'session-filter-chip',
                                    'session-filter-chip--selected' => $this->subspecialtyIsSelected($opt['id']),
                                ])
                            >
                                {{ $opt['label'] }}
                            </button>
                        @endforeach
                    </div>
                    @if (count($this->specialityOptions) > (int) config('session_filter.subspecialties_collapsed_count', 7))
                        <div>
                            <flux:button variant="ghost" size="sm" wire:click="toggleSubspecialtiesExpanded" type="button" class="!px-0 !text-[#1565c0] hover:!text-[#1358a8]">
                                {{ $this->subspecialtiesExpanded ? __('session_filter.show_less') : __('session_filter.show_more') }}
                            </flux:button>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>

    <div class="session-filter-actions flex w-full flex-col gap-3 sm:flex-row sm:justify-end">
        <flux:button variant="ghost" wire:click="proceedSkip" type="button" class="min-h-11 w-full sm:w-auto sm:min-w-[9rem]" wire:loading.attr="disabled">
            {{ __('session_filter.skip') }}
        </flux:button>
        <flux:button
            variant="primary"
            wire:click="proceedNext"
            type="button"
            class="min-h-11 w-full !border-[#1565c0] !bg-[#1565c0] !text-white shadow-md shadow-[#1565c0]/20 hover:!brightness-[0.97] focus-visible:!ring-[#1565c0]/40 sm:w-auto sm:min-w-[9rem]"
            wire:loading.attr="disabled"
        >
            {{ __('session_filter.next') }}
        </flux:button>
    </div>
    </div>
</div>
