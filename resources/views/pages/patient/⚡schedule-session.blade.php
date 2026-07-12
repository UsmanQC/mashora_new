<?php

use App\Models\Degree;
use App\Models\Speciality;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
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

    #[Url(as: 'instant', except: false)]
    public bool $instantBooking = false;

    public int $mobileStep = 1;

    public function mount(): void
    {
        if (request()->boolean('instant')) {
            $this->instantBooking = true;
        }

        $this->syncInstantBookingSession();
    }

    public function updatedInstantBooking(): void
    {
        $this->syncInstantBookingSession();
    }

    protected function syncInstantBookingSession(): void
    {
        if ($this->instantBooking) {
            Session::put('instant_booking', true);

            return;
        }

        Session::forget('instant_booking');

        $preferences = Session::get('session_filter_preferences');
        if (is_array($preferences)) {
            $preferences['instant_booking'] = false;
            Session::put('session_filter_preferences', $preferences);
        }
    }

    public function mobileStepsTotal(): int
    {
        // return 5; // includes session length step (mobile step 3)

        return 4;
    }

    public function mobileStepProgressPercent(): int
    {
        return (int) round(($this->mobileStep / $this->mobileStepsTotal()) * 100);
    }

    public function mobileStepTitle(): string
    {
        $key = match ($this->mobileStep) {
            1 => 'degree',
            2 => 'gender',
            // 3 => 'duration', // Session length step (disabled on mobile)
            3 => 'language',
            default => 'subspecialties',
        };

        return (string) __('session_filter.mobile_steps.'.$key);
    }

    public function mobileHeaderTitle(): string
    {
        if ($this->instantBooking) {
            return (string) __('session_filter.instant_title');
        }

        return (string) __('session_filter.scheduled_title');
    }

    public function mobileHeaderSubtitle(): string
    {
        if ($this->instantBooking) {
            return (string) __('session_filter.instant_step_of', [
                'step' => $this->mobileStepTitle(),
                'current' => $this->mobileStep,
                'total' => $this->mobileStepsTotal(),
            ]);
        }

        return (string) __('session_filter.scheduled_step_of', [
            'step' => $this->mobileStepTitle(),
            'current' => $this->mobileStep,
            'total' => $this->mobileStepsTotal(),
        ]);
    }

    public function goToNextMobileStep(): void
    {
        $this->validateCurrentMobileStep();

        if ($this->mobileStep === 2) {
            $this->ensureMobileDurationDefault();
        }

        if ($this->mobileStep >= $this->mobileStepsTotal()) {
            $this->proceedNext();

            return;
        }

        $this->mobileStep++;
    }

    public function goToPreviousMobileStep(): void
    {
        if ($this->mobileStep > 1) {
            $this->mobileStep--;
        }
    }

    public function goBackMobile(): void
    {
        if ($this->mobileStep > 1) {
            $this->goToPreviousMobileStep();

            return;
        }

        $url = auth()->check() ? route('patient.home') : route('home');

        $this->redirect(
            $url,
            navigate: \App\Support\PatientPageNavigation::usesLivewireNavigate($url),
        );
    }

    public function skipSubspecialtiesStep(): void
    {
        $this->subspecialties = [];
        $this->proceedNext();
    }

    public function skipMobileGenderStep(): void
    {
        $this->genderPreference = 'both';
        $this->ensureMobileDurationDefault();
        $this->goToNextMobileStep();
    }

    public function skipMobileLanguageStep(): void
    {
        $this->languagePreference = 'both';
        $this->goToNextMobileStep();
    }

    protected function ensureMobileDurationDefault(): void
    {
        if ($this->durationMinutes === '') {
            $this->durationMinutes = '15';
        }
    }

    public function selectMobileDegree(string $value): void
    {
        $this->degree_id = $value;
        $this->goToNextMobileStep();
    }

    public function selectMobileGender(string $value): void
    {
        $this->genderPreference = $value;
        $this->ensureMobileDurationDefault();
        $this->goToNextMobileStep();
    }

    /*
    public function selectMobileDuration(string $minutes): void
    {
        $this->durationMinutes = $minutes;
        $this->goToNextMobileStep();
    }
    */

    public function selectMobileLanguage(string $value): void
    {
        $this->languagePreference = $value;
        $this->goToNextMobileStep();
    }

    /**
     * @return list<string>
     */
    protected function allowedDegreeIds(): array
    {
        return [...array_column($this->specialistKindOptions, 'value'), 'all'];
    }

    protected function validateCurrentMobileStep(): void
    {
        match ($this->mobileStep) {
            1 => $this->validate([
                'degree_id' => ['required', 'string', 'in:'.implode(',', $this->allowedDegreeIds())],
            ], [
                'degree_id.required' => __('session_filter.validation.specialist_required'),
            ]),
            2 => $this->validate([
                'genderPreference' => ['required', 'in:male,female,both'],
            ], [
                'genderPreference.required' => __('session_filter.validation.gender_required'),
            ]),
            /*
            3 => $this->validate([
                'durationMinutes' => ['required', 'in:15,30,45,60'],
            ], [
                'durationMinutes.required' => __('session_filter.validation.duration_required'),
            ]),
            */
            3 => $this->validate([
                'languagePreference' => ['required', 'in:ar,en,both'],
            ], [
                'languagePreference.required' => __('session_filter.validation.language_required'),
            ]),
            /*
            4 => $this->validate([
                'languagePreference' => ['required', 'in:ar,en,both'],
            ], [
                'languagePreference.required' => __('session_filter.validation.language_required'),
            ]),
            */
            default => null,
        };
    }

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

    public function selectAllSubspecialties(): void
    {
        $this->subspecialties = array_column($this->specialityOptions, 'id');
        $this->subspecialtiesExpanded = true;
    }

    public function allSubspecialtiesSelected(): bool
    {
        $allIds = array_column($this->specialityOptions, 'id');

        if ($allIds === []) {
            return false;
        }

        return count(array_diff($allIds, $this->subspecialties)) === 0;
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
            'instant_booking' => $this->instantBooking,
        ];
    }

    public function proceedNext(): void
    {
        $this->validate([
            'degree_id' => ['required', 'string', 'in:'.implode(',', $this->allowedDegreeIds())],
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

        if ($this->instantBooking) {
            Session::put('instant_booking', true);
        } else {
            Session::forget('instant_booking');
        }

        Flux::toast(variant: 'success', text: __('session_filter.next_toast'));

        $this->redirect(
            $this->instantBooking
                ? route('patient.schedule.instant')
                : route('patient.schedule.specialists')
        );
    }

    public function proceedSkip(): void
    {
        Session::forget('session_filter_preferences');
        Session::forget('instant_booking');

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

    public function profilePhotoUrl(): ?string
    {
        $user = Auth::user();

        if ($user === null || ! filled($user->profile_photo_path)) {
            return null;
        }

        return Storage::disk('public')->url((string) $user->profile_photo_path);
    }
}; ?>

<div class="pb-6 sm:pb-10">
    {{-- Mobile: step-by-step wizard --}}
    <div
        class="session-filter-mobile bg-slate-50 pb-[calc(7.5rem+env(safe-area-inset-bottom))] sm:hidden"
        id="patient-portal-swipe-surface"
        data-swipe-livewire-method="goBackMobile"
        data-swipe-hint-id="patient-portal-swipe-hint"
    >
        @include('partials.patient-luxury-page-header', [
            'title' => $this->mobileHeaderTitle(),
            'subtitle' => $this->mobileHeaderSubtitle(),
            'profilePhotoUrl' => $this->profilePhotoUrl(),
            'userName' => auth()->user()?->name,
            'testId' => 'patient-schedule-filter-header',
            'progressStep' => $mobileStep,
            'progressTotal' => $this->mobileStepsTotal(),
        ])

        <div class="session-filter-mobile-body mt-4 space-y-3 px-6">
            @if ($instantBooking)
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/80 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ __('session_filter.instant_window_hint', ['minutes' => config('appointments.instant_window_minutes', 60)]) }}
                </div>
            @endif
            @if ($mobileStep === 1)
                <div class="space-y-2.5" role="radiogroup" aria-label="{{ __('session_filter.sections.specialist') }}">
                    <button
                        type="button"
                        wire:key="mobile-degree-all"
                        wire:click="selectMobileDegree('all')"
                        wire:loading.attr="disabled"
                        wire:target="selectMobileDegree"
                        aria-pressed="{{ $degree_id === 'all' ? 'true' : 'false' }}"
                        data-test="session-filter-select-all-specialists"
                        @class([
                            'session-filter-mobile-option',
                            'session-filter-mobile-option--active' => $degree_id === 'all',
                        ])
                    >
                        <span class="session-filter-mobile-option__icon session-filter-mobile-option__icon--specialist">
                            <flux:icon name="users" class="size-5" />
                        </span>
                        <span class="session-filter-mobile-option__label">{{ __('session_filter.select_all') }}</span>
                        <span class="session-filter-mobile-option__indicator" aria-hidden="true"></span>
                    </button>
                    @foreach ($this->specialistKindOptions as $option)
                        <button
                            type="button"
                            wire:key="mobile-degree-{{ $option['value'] }}"
                            wire:click="selectMobileDegree('{{ $option['value'] }}')"
                            wire:loading.attr="disabled"
                            wire:target="selectMobileDegree"
                            aria-pressed="{{ $degree_id === $option['value'] ? 'true' : 'false' }}"
                            @class([
                                'session-filter-mobile-option',
                                'session-filter-mobile-option--active' => $degree_id === $option['value'],
                            ])
                        >
                            <span class="session-filter-mobile-option__icon session-filter-mobile-option__icon--specialist">
                                <flux:icon name="user-circle" class="size-5" />
                            </span>
                            <span class="session-filter-mobile-option__label">{{ $option['label'] }}</span>
                            <span class="session-filter-mobile-option__indicator" aria-hidden="true"></span>
                        </button>
                    @endforeach
                </div>
                <flux:error name="degree_id" />
            @elseif ($mobileStep === 2)
                <div class="grid grid-cols-3 gap-2.5" role="radiogroup" aria-label="{{ __('session_filter.sections.gender_pref') }}">
                    @foreach (['male' => ['icon' => 'user', 'tone' => 'sky'], 'female' => ['icon' => 'user', 'tone' => 'violet'], 'both' => ['icon' => 'users', 'tone' => 'emerald']] as $value => $meta)
                        <button
                            type="button"
                            wire:key="mobile-gender-{{ $value }}"
                            wire:click="selectMobileGender('{{ $value }}')"
                            wire:loading.attr="disabled"
                            wire:target="selectMobileGender"
                            aria-pressed="{{ $genderPreference === $value ? 'true' : 'false' }}"
                            @class([
                                'session-filter-mobile-tile',
                                'session-filter-mobile-tile--active' => $genderPreference === $value,
                            ])
                        >
                            <span @class([
                                'session-filter-mobile-tile__icon',
                                'session-filter-mobile-tile__icon--'.$meta['tone'] => true,
                            ])>
                                <flux:icon :name="$meta['icon']" class="size-5" />
                            </span>
                            <span class="session-filter-mobile-tile__label">{{ __('session_filter.sections.gender.'.$value) }}</span>
                        </button>
                    @endforeach
                </div>
                <flux:error name="genderPreference" />
            {{-- Session length step (mobile step 3) — disabled, kept for reference --}}
            {{--
            @elseif ($mobileStep === 3)
                <div class="grid grid-cols-2 gap-2.5" role="radiogroup" aria-label="{{ __('session_filter.sections.duration') }}">
                    @foreach (['15', '30', '45', '60'] as $minutes)
                        <button
                            type="button"
                            wire:key="mobile-duration-{{ $minutes }}"
                            wire:click="selectMobileDuration('{{ $minutes }}')"
                            wire:loading.attr="disabled"
                            wire:target="selectMobileDuration"
                            aria-pressed="{{ $durationMinutes === $minutes ? 'true' : 'false' }}"
                            @class([
                                'session-filter-mobile-duration',
                                'session-filter-mobile-duration--active' => $durationMinutes === $minutes,
                            ])
                        >
                            <span class="session-filter-mobile-duration__value">{{ $minutes }}</span>
                            <span class="session-filter-mobile-duration__unit">{{ app()->getLocale() === 'ar' ? 'دقيقة' : 'min' }}</span>
                        </button>
                    @endforeach
                </div>
                <flux:error name="durationMinutes" />
            --}}
            @elseif ($mobileStep === 3)
                <div class="space-y-2.5" role="radiogroup" aria-label="{{ __('session_filter.sections.language') }}">
                    @foreach (['ar' => __('session_filter.sections.lang.ar'), 'en' => __('session_filter.sections.lang.en'), 'both' => __('session_filter.sections.lang.both')] as $value => $label)
                        <button
                            type="button"
                            wire:key="mobile-language-{{ $value }}"
                            wire:click="selectMobileLanguage('{{ $value }}')"
                            wire:loading.attr="disabled"
                            wire:target="selectMobileLanguage"
                            aria-pressed="{{ $languagePreference === $value ? 'true' : 'false' }}"
                            @class([
                                'session-filter-mobile-option',
                                'session-filter-mobile-option--active' => $languagePreference === $value,
                            ])
                        >
                            <span class="session-filter-mobile-option__icon session-filter-mobile-option__icon--language">
                                <flux:icon name="language" class="size-5" />
                            </span>
                            <span class="session-filter-mobile-option__label">{{ $label }}</span>
                            <span class="session-filter-mobile-option__indicator" aria-hidden="true"></span>
                        </button>
                    @endforeach
                </div>
                <flux:error name="languagePreference" />
            @else
                <div class="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-zinc-900">{{ __('session_filter.sections.subspecialties') }}</p>
                        <span class="rounded-full bg-[#10B981]/10 px-2.5 py-0.5 text-[0.6875rem] font-bold uppercase tracking-wide text-[#10B981]">
                            {{ __('session_filter.optional') }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            wire:key="mobile-sub-all"
                            wire:click="selectAllSubspecialties"
                            aria-pressed="{{ $this->allSubspecialtiesSelected() ? 'true' : 'false' }}"
                            data-test="session-filter-select-all-subspecialties"
                            @class([
                                'session-filter-chip',
                                'session-filter-chip--selected' => $this->allSubspecialtiesSelected(),
                            ])
                        >
                            {{ __('session_filter.select_all') }}
                        </button>
                        @foreach ($this->visibleSpecialityOptions as $opt)
                            <button
                                type="button"
                                wire:key="mobile-sub-{{ $opt['id'] }}"
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
                        <div class="mt-3">
                            <flux:button variant="ghost" size="sm" wire:click="toggleSubspecialtiesExpanded" type="button" class="!px-0 !text-[#10B981]">
                                {{ $this->subspecialtiesExpanded ? __('session_filter.show_less') : __('session_filter.show_more') }}
                            </flux:button>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div class="session-filter-mobile-footer pointer-events-none fixed inset-x-0 bottom-[calc(6.25rem+env(safe-area-inset-bottom))] z-40 px-6">
            @if ($mobileStep === 1)
                <button
                    type="button"
                    wire:click="proceedSkip"
                    wire:loading.attr="disabled"
                    class="session-filter-skip-all pointer-events-auto mx-auto flex w-full max-w-md items-center justify-center rounded-full bg-[#10B981] px-5 py-3 text-sm font-bold text-black shadow-sm shadow-[#10B981]/25 transition hover:bg-[#0ea271] active:scale-[0.98] disabled:opacity-70"
                >
                    {{ __('session_filter.skip_all') }}
                </button>
            @elseif ($mobileStep === $this->mobileStepsTotal())
                <div class="pointer-events-auto mx-auto w-full max-w-md space-y-2.5">
                    <flux:button
                        variant="primary"
                        size="sm"
                        wire:click="goToNextMobileStep"
                        type="button"
                        class="!min-h-11 w-full !rounded-full !border-[#10B981] !bg-[#10B981] !px-4 !text-sm !font-bold !text-black shadow-sm shadow-[#10B981]/20 hover:!brightness-[0.97]"
                        wire:loading.attr="disabled"
                        data-test="session-filter-mobile-finish"
                    >
                        {{ __('session_filter.finish') }}
                    </flux:button>

                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            wire:click="skipSubspecialtiesStep"
                            wire:loading.attr="disabled"
                            class="inline-flex min-h-11 w-full items-center justify-center rounded-full border border-zinc-300 bg-white px-4 text-sm font-semibold text-zinc-700 shadow-sm transition hover:border-zinc-400 hover:bg-zinc-50 active:scale-[0.98] disabled:opacity-70"
                            data-test="session-filter-mobile-skip"
                        >
                            {{ __('session_filter.skip') }}
                        </button>

                        <flux:button
                            variant="ghost"
                            size="sm"
                            wire:click="goToPreviousMobileStep"
                            type="button"
                            class="!min-h-11 w-full !rounded-full !border !border-zinc-200 !bg-white !px-3 !text-sm !font-semibold !text-zinc-700 hover:!bg-zinc-50"
                            wire:loading.attr="disabled"
                            data-test="session-filter-mobile-back"
                        >
                            {{ __('session_filter.back') }}
                        </flux:button>
                    </div>
                </div>
            @elseif (in_array($mobileStep, [2, 3], true))
                <div class="pointer-events-auto mx-auto w-full max-w-md">
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            wire:click="{{ $mobileStep === 2 ? 'skipMobileGenderStep' : 'skipMobileLanguageStep' }}"
                            wire:loading.attr="disabled"
                            class="inline-flex min-h-11 w-full items-center justify-center rounded-full border border-zinc-300 bg-white px-4 text-sm font-semibold text-zinc-700 shadow-sm transition hover:border-zinc-400 hover:bg-zinc-50 active:scale-[0.98] disabled:opacity-70"
                            data-test="session-filter-mobile-skip-{{ $mobileStep === 2 ? 'gender' : 'language' }}"
                        >
                            {{ __('session_filter.skip') }}
                        </button>

                        <flux:button
                            variant="ghost"
                            size="sm"
                            wire:click="goToPreviousMobileStep"
                            type="button"
                            class="!min-h-11 w-full !rounded-full !border !border-zinc-200 !bg-white !px-3 !text-sm !font-semibold !text-zinc-700 hover:!bg-zinc-50"
                            wire:loading.attr="disabled"
                            data-test="session-filter-mobile-back"
                        >
                            {{ __('session_filter.back') }}
                        </flux:button>
                    </div>
                </div>
            {{-- Session length step footer (back only) — disabled with step above --}}
            {{--
            @else
                <div class="pointer-events-auto mx-auto flex w-full max-w-md">
                    <flux:button variant="ghost" size="sm" wire:click="goToPreviousMobileStep" type="button" class="!px-2" wire:loading.attr="disabled">
                        {{ __('session_filter.back') }}
                    </flux:button>
                </div>
            --}}
            @endif
        </div>
    </div>

    {{-- Desktop / tablet: full filter form --}}
    <div class="hidden w-full space-y-5 sm:block lg:space-y-6">
        {{-- Hero — soft vertical 50/50 (top + bottom) --}}
        <header class="grid min-h-[11rem] grid-rows-2 overflow-hidden rounded-2xl border border-emerald-100/90 bg-white shadow-sm sm:min-h-[10.5rem]">
            <div class="flex items-center gap-4 bg-emerald-50/70 p-4 sm:p-5">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#10B981]/12 text-[#10B981] lg:size-12">
                    <flux:icon name="calendar-days" variant="mini" class="size-5 lg:size-6" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-zinc-900 sm:text-base lg:text-lg">
                        {{ $instantBooking ? __('session_filter.instant_title') : __('session_filter.scheduled_title') }}
                    </p>
                    <p class="mt-0.5 text-xs text-zinc-600 sm:text-sm">
                        {{ $instantBooking ? __('session_filter.instant_subtitle', ['minutes' => config('appointments.instant_window_minutes', 60)]) : __('session_filter.scheduled_subtitle') }}
                    </p>
                </div>
            </div>

            <div class="flex flex-col justify-center border-t border-emerald-100/90 bg-white p-4 sm:p-5">
                <div class="flex items-center justify-between gap-3 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    <span>{{ __('session_filter.filter_heading') }}</span>
                    <span class="tabular-nums text-[#10B981]">
                        {{ __('session_filter.progress', ['current' => $this->requiredFiltersCompleted, 'total' => 4]) }}
                    </span>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-[#10B981]/15">
                    <div
                        class="h-full rounded-full bg-[#34d399] transition-all duration-300 ease-out"
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
                        <button
                            type="button"
                            wire:key="degree-all"
                            wire:click="$set('degree_id', 'all')"
                            aria-pressed="{{ $degree_id === 'all' ? 'true' : 'false' }}"
                            data-test="session-filter-select-all-specialists"
                            @class([
                                'session-filter-chip',
                                'session-filter-chip--active' => $degree_id === 'all',
                            ])
                        >
                            {{ __('session_filter.select_all') }}
                        </button>
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
                        <span class="inline-flex shrink-0 items-center rounded-full bg-[#10B981]/10 px-2.5 py-0.5 text-[0.6875rem] font-semibold uppercase tracking-wide text-[#10B981] ring-1 ring-[#10B981]/20">
                            {{ __('session_filter.optional') }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            wire:key="sub-all"
                            wire:click="selectAllSubspecialties"
                            aria-pressed="{{ $this->allSubspecialtiesSelected() ? 'true' : 'false' }}"
                            data-test="session-filter-select-all-subspecialties"
                            @class([
                                'session-filter-chip',
                                'session-filter-chip--selected' => $this->allSubspecialtiesSelected(),
                            ])
                        >
                            {{ __('session_filter.select_all') }}
                        </button>
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
                            <flux:button variant="ghost" size="sm" wire:click="toggleSubspecialtiesExpanded" type="button" class="!px-0 !text-[#10B981] hover:!text-[#059669]">
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
            class="min-h-11 w-full !border-[#10B981] !bg-[#10B981] !text-white shadow-md shadow-[#10B981]/20 hover:!brightness-[0.97] focus-visible:!ring-[#10B981]/40 sm:w-auto sm:min-w-[9rem]"
            wire:loading.attr="disabled"
        >
            {{ __('session_filter.next') }}
        </flux:button>
    </div>
    </div>
</div>
