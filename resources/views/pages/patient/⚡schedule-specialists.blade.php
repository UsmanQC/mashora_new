<?php

use App\Support\PendingPatientBooking;
use App\Support\SpecialistCatalog;
use App\Models\Duration;
use App\Models\Degree;
use App\Models\Doctor;
use App\Models\Speciality;
use App\Services\DoctorAvailabilityService;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient')] #[Title('Specialists')] class extends Component
{
    /** @var array<string, int> */
    public array $likeCounts = [];

    public string $searchDoctor = '';

    public string $selectedDate = '';

    public string $selectedDuration = '';

    public bool $showFilterPanel = false;

    public string $filterGender = 'both';

    public string $filterLanguage = 'both';

    public string $filterDegree = '';

    /** @var list<string> */
    public array $filterSubspecialties = [];

    public bool $instantBooking = false;

    /** @var list<array<string, mixed>>|null */
    protected ?array $filteredSpecialistsCache = null;

    /** @var array<string, list<string>> */
    protected array $doctorSlotsCache = [];

    protected function patientTimezone(): string
    {
        return \App\Support\AppTimezone::name();
    }

    protected function isInstantEntryRequest(): bool
    {
        return request()->routeIs('patient.schedule.instant')
            || request()->boolean('instant');
    }

    public function boot(): void
    {
        if (Session::get('instant_booking')) {
            $this->instantBooking = true;
        }
    }

    public function mount(): void
    {
        if ($this->isInstantEntryRequest()) {
            $this->instantBooking = true;
            $this->syncInstantBookingState();

            if (! $this->hasCompletedFilterPreferences()) {
                $this->redirect(route('patient.schedule.filter', ['instant' => 1]));

                return;
            }

            $this->applyPreferencesFromSession(forInstant: true);
        } elseif (request()->routeIs('patient.schedule.specialists') && ! $this->isLivewireUpdateRequest()) {
            $this->instantBooking = false;
            Session::forget('instant_booking');

            if (! $this->hasCompletedFilterPreferences()) {
                $this->redirect(route('patient.schedule.filter'));

                return;
            }

            $this->applyPreferencesFromSession(forInstant: false);
        } elseif (Session::get('instant_booking')) {
            $this->instantBooking = true;
        }

        foreach ($this->filteredSpecialists() as $specialist) {
            $this->likeCounts[$specialist['id']] = (int) ($specialist['likes'] ?? 0);
        }

        $this->selectedDate = now()->timezone($this->patientTimezone())->toDateString();

        if ($this->instantBooking) {
            Session::put('instant_booking', true);
        }

        $sessionDuration = (string) (Session::get('session_filter_preferences.duration_minutes') ?? '');
        if ($sessionDuration !== '' && $this->selectedDuration === '') {
            $this->selectedDuration = $sessionDuration;
        }

        if ($this->filterGender === 'both') {
            $this->filterGender = (string) (Session::get('session_filter_preferences.gender_preference') ?? 'both');
        }

        if ($this->filterLanguage === 'both') {
            $this->filterLanguage = (string) (Session::get('session_filter_preferences.language_preference') ?? 'both');
        }

        if ($this->filterDegree === '') {
            $this->filterDegree = (string) (Session::get('session_filter_preferences.degree_id') ?? '');
        }

        if ($this->filterSubspecialties === []) {
            $this->filterSubspecialties = collect(Session::get('session_filter_preferences.subspecialties', []))
                ->map(static fn (mixed $id): string => (string) $id)
                ->filter()
                ->values()
                ->all();
        }
    }

    protected function isLivewireUpdateRequest(): bool
    {
        return request()->hasHeader('X-Livewire');
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function filteredSpecialists(): array
    {
        return $this->filteredSpecialistsCache ??= SpecialistCatalog::filtered(
            Session::get('session_filter_preferences')
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function specialists(): array
    {
        return $this->filteredSpecialists();
    }

    /**
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function visibleSpecialists(): array
    {
        $items = $this->specialists;

        $search = mb_strtolower(trim($this->searchDoctor));
        if ($search !== '') {
            $items = array_values(array_filter(
                $items,
                static fn (array $specialist): bool => str_contains(
                    mb_strtolower((string) ($specialist['name'] ?? '')),
                    $search
                )
            ));
        }

        if ($this->selectedDuration !== '') {
            $items = array_values(array_filter(
                $items,
                fn (array $specialist): bool => SpecialistCatalog::offersDuration($specialist, $this->selectedDuration)
            ));
        }

        if ($this->filterGender !== 'both') {
            $items = array_values(array_filter(
                $items,
                fn (array $specialist): bool => (string) ($specialist['gender'] ?? '') === $this->filterGender
            ));
        }

        if ($this->filterLanguage !== 'both') {
            $items = array_values(array_filter(
                $items,
                fn (array $specialist): bool => in_array($this->filterLanguage, (array) ($specialist['languages'] ?? []), true)
            ));
        }

        if ($this->filterDegree !== '') {
            $items = array_values(array_filter(
                $items,
                fn (array $specialist): bool => (string) ($specialist['degree_id'] ?? '') === $this->filterDegree
            ));
        }

        if ($this->filterSubspecialties !== []) {
            $items = array_values(array_filter(
                $items,
                function (array $specialist): bool {
                    $doctorSpecialities = (array) ($specialist['speciality_ids'] ?? []);

                    return count(array_intersect($this->filterSubspecialties, $doctorSpecialities)) > 0;
                }
            ));
        }

        if ($this->instantBooking) {
            $items = array_values(array_filter(
                $items,
                fn (array $specialist): bool => ($specialist['accept_instant_appointment'] ?? true) !== false
                    && $this->availableSlots($specialist) !== []
            ));
        }

        return $items;
    }

    protected function syncInstantBookingState(): void
    {
        $fromSession = (bool) (
            Session::get('instant_booking')
            ?? data_get(Session::get('session_filter_preferences'), 'instant_booking')
        );

        if ($this->instantBooking || $fromSession) {
            $this->instantBooking = true;
            Session::put('instant_booking', true);

            $preferences = Session::get('session_filter_preferences', []);
            if (is_array($preferences)) {
                $preferences['instant_booking'] = true;
                Session::put('session_filter_preferences', $preferences);
            }

            return;
        }

        Session::forget('instant_booking');
    }

    protected function hasCompletedFilterPreferences(): bool
    {
        $preferences = Session::get('session_filter_preferences');

        if (! is_array($preferences)) {
            return false;
        }

        return filled($preferences['degree_id'] ?? null)
            && filled($preferences['gender_preference'] ?? null)
            && filled($preferences['duration_minutes'] ?? null)
            && filled($preferences['language_preference'] ?? null);
    }

    protected function applyPreferencesFromSession(bool $forInstant = true): void
    {
        if ($forInstant) {
            Session::put('instant_booking', true);
            $this->instantBooking = true;
        } else {
            Session::forget('instant_booking');
            $this->instantBooking = false;
        }

        $preferences = Session::get('session_filter_preferences');

        if (! is_array($preferences)) {
            return;
        }

        $preferences['instant_booking'] = $forInstant;
        Session::put('session_filter_preferences', $preferences);

        $this->filterGender = (string) ($preferences['gender_preference'] ?? 'both');
        $this->filterLanguage = (string) ($preferences['language_preference'] ?? 'both');
        $this->filterDegree = (string) ($preferences['degree_id'] ?? '');
        $this->filterSubspecialties = collect($preferences['subspecialties'] ?? [])
            ->map(static fn (mixed $id): string => (string) $id)
            ->filter()
            ->values()
            ->all();

        $sessionDuration = (string) ($preferences['duration_minutes'] ?? '');
        if ($sessionDuration !== '') {
            $this->selectedDuration = $sessionDuration;
        }

        $this->selectedDate = now()->timezone($this->patientTimezone())->toDateString();
        $this->filteredSpecialistsCache = null;
        $this->doctorSlotsCache = [];
    }

    public function updatedInstantBooking(): void
    {
        $this->syncInstantBookingState();
    }

    /**
     * @return list<array{date: string, day: string, weekday: string, is_today: bool}>
     */
    #[Computed]
    public function dayOptions(): array
    {
        $start = now()->timezone($this->patientTimezone())->startOfDay();

        $days = [];
        for ($i = 0; $i < 8; $i++) {
            $date = $start->copy()->addDays($i)->locale(app()->getLocale());

            $days[] = [
                'date' => $date->toDateString(),
                'day' => $date->translatedFormat('d'),
                'weekday' => $i === 0 ? __('specialist_results.today_short') : $date->translatedFormat('D'),
                'is_today' => $i === 0,
            ];
        }

        return $days;
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function durationOptions(): array
    {
        return Duration::query()
            ->orderBy('duration')
            ->pluck('duration')
            ->map(static fn (mixed $minutes): string => (string) $minutes)
            ->filter(static fn (string $minutes): bool => $minutes !== '' && $minutes !== '0')
            ->values()
            ->all();
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    public function selectDuration(string $minutes): void
    {
        $this->selectedDuration = $minutes;
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    #[Computed]
    public function degreeOptions(): array
    {
        $isAr = app()->getLocale() === 'ar';

        return Degree::query()
            ->where('status', true)
            ->orderBy('id')
            ->get(['id', 'title', 'title_ar'])
            ->map(function (Degree $degree) use ($isAr): array {
                $label = $isAr
                    ? (filled($degree->title_ar) ? (string) $degree->title_ar : (string) $degree->title)
                    : (filled($degree->title) ? (string) $degree->title : (string) $degree->title_ar);

                return [
                    'id' => (string) $degree->id,
                    'label' => $label,
                ];
            })
            ->all();
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    #[Computed]
    public function subspecialityOptions(): array
    {
        $isAr = app()->getLocale() === 'ar';

        return Speciality::query()
            ->where('status', true)
            ->orderBy('id')
            ->get(['id', 'title', 'title_ar'])
            ->map(function (Speciality $speciality) use ($isAr): array {
                $label = $isAr
                    ? (filled($speciality->title_ar) ? (string) $speciality->title_ar : (string) $speciality->title)
                    : (filled($speciality->title) ? (string) $speciality->title : (string) $speciality->title_ar);

                return [
                    'id' => (string) $speciality->id,
                    'label' => $label,
                ];
            })
            ->all();
    }

    public function toggleFilterPanel(): void
    {
        $this->showFilterPanel = ! $this->showFilterPanel;
    }

    public function clearFilterPanel(): void
    {
        $this->filterGender = 'both';
        $this->filterLanguage = 'both';
        $this->filterDegree = '';
        $this->filterSubspecialties = [];
    }

    public function applyFilterPanel(): void
    {
        Session::put('session_filter_preferences', [
            'degree_id' => $this->filterDegree,
            'gender_preference' => $this->filterGender,
            'duration_minutes' => $this->selectedDuration,
            'language_preference' => $this->filterLanguage,
            'subspecialties' => $this->filterSubspecialties,
            'instant_booking' => $this->instantBooking,
        ]);

        $this->showFilterPanel = false;
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
            $date = $this->selectedDate;
            $duration = (int) ($specialist['session_minutes'] ?? 15);

            PendingPatientBooking::store($doctorId, $date, $slot, $duration);

            $this->redirect(
                route('patient.book-appointments', ['doctor' => $doctorId], false)
                    . '?'.http_build_query([
                        'date' => $date,
                        'duration' => $duration,
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
            ->timezone($this->patientTimezone())
            ->locale(app()->getLocale())
            ->translatedFormat('g:i a');

        Flux::toast(text: __('specialist_results.slot_selected_toast', ['time' => $formatted]));
    }

    /**
     * @param  array<string, mixed>  $specialist
     * @return list<string>
     */
    public function availableSlots(array $specialist): array
    {
        $doctorId = $specialist['doctor_database_id'] ?? null;
        $duration = (int) ($this->selectedDuration !== ''
            ? $this->selectedDuration
            : ($specialist['session_minutes'] ?? 15));

        if (is_int($doctorId) && $doctorId > 0) {
            $doctor = Doctor::query()->find($doctorId);

            if (! $doctor instanceof Doctor) {
                return [];
            }

            /** @var DoctorAvailabilityService $availability */
            $availability = app(DoctorAvailabilityService::class);

            if ($this->instantBooking) {
                return $availability->availableSlotsWithinInstantWindow($doctor, $duration);
            }

            return $availability->availableSlots($doctor, $this->selectedDate, $duration);
        }

        $slots = $this->slotsForSelectedDate($specialist);
        $timezone = $this->patientTimezone();

        $selectedDate = Carbon::parse($this->selectedDate, $timezone)->startOfDay();
        $today = now()->timezone($timezone)->startOfDay();
        $now = now()->timezone($timezone);

        return collect($slots)
            ->filter(function (string $slot) use ($selectedDate, $today, $now, $timezone): bool {
                try {
                    $slotAt = Carbon::createFromFormat(
                        'Y-m-d H:i',
                        $selectedDate->format('Y-m-d').' '.$slot,
                        $timezone
                    );
                } catch (\Throwable) {
                    return false;
                }

                if ($this->instantBooking) {
                    if (! $selectedDate->equalTo($today)) {
                        return false;
                    }

                    $windowEnd = $now->copy()->addMinutes(app(DoctorAvailabilityService::class)->instantWindowMinutes());

                    return $slotAt->greaterThan($now) && $slotAt->lessThanOrEqualTo($windowEnd);
                }

                if ($selectedDate->equalTo($today)) {
                    return $slotAt->greaterThan($now);
                }

                return true;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $specialist
     * @return list<string>
     */
    protected function slotsForSelectedDate(array $specialist): array
    {
        $doctorId = $specialist['doctor_database_id'] ?? null;

        if (! is_int($doctorId) || $doctorId <= 0) {
            /** @var list<string> */
            return collect($specialist['slots'] ?? [])
                ->map(static fn (mixed $slot): string => (string) $slot)
                ->filter()
                ->values()
                ->all();
        }

        $cacheKey = $doctorId.'|'.$this->selectedDate;
        if (array_key_exists($cacheKey, $this->doctorSlotsCache)) {
            return $this->doctorSlotsCache[$cacheKey];
        }

        $timezone = $this->patientTimezone();
        $selectedDate = Carbon::parse($this->selectedDate, $timezone);
        $weekday = strtolower($selectedDate->englishDayOfWeek);

        $doctor = Doctor::query()
            ->with(['workingDays' => function ($query) use ($selectedDate, $weekday): void {
                $query->where('is_working', true)
                    ->where(function ($q) use ($selectedDate, $weekday): void {
                        $q->whereDate('override_date', $selectedDate->toDateString())
                            ->orWhere(function ($qq) use ($weekday): void {
                                $qq->whereNull('override_date')
                                    ->where('day_of_week', $weekday);
                            });
                    })
                    ->with('workingHours');
            }])
            ->find($doctorId);
            if (! $doctor instanceof Doctor || $doctor->workingDays->isEmpty()) {
            $this->doctorSlotsCache[$cacheKey] = [];

            return [];
        }

        /** @var list<string> $slots */
        $slots = $doctor->workingDays
            ->flatMap(static function ($workingDay) use ($timezone) {
                return $workingDay->workingHours->flatMap(static function ($hour) use ($timezone) {
                    if (! filled($hour->start_time) || ! filled($hour->end_time)) {
                        return [];
                    }

                    try {
                        $start = Carbon::createFromFormat('H:i:s', (string) $hour->start_time, $timezone);
                        $end = Carbon::createFromFormat('H:i:s', (string) $hour->end_time, $timezone);
                    } catch (\Throwable) {
                        return [];
                    }

                    $times = [];
                    while ($start < $end) {
                        $times[] = $start->format('H:i');
                        $start = $start->addMinutes(15);
                    }

                    return $times;
                });
            })
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->doctorSlotsCache[$cacheKey] = $slots;

        return $slots;
    }

    #[Computed]
    public function hasSavedFilters(): bool
    {
        return Session::has('session_filter_preferences');
    }

    public function pageHeading(): string
    {
        return $this->instantBooking
            ? (string) __('specialist_results.page_heading_instant')
            : (string) __('specialist_results.page_heading');
    }

    public function pageSubtitle(): string
    {
        if ($this->instantBooking) {
            return (string) __('specialist_results.page_sub_instant', [
                'minutes' => app(DoctorAvailabilityService::class)->instantWindowMinutes(),
            ]);
        }

        return $this->hasSavedFilters
            ? (string) __('specialist_results.page_sub_with_filters')
            : (string) __('specialist_results.page_sub_default');
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

<div class="patient-luxury-specialists bg-slate-50 pb-[calc(4.75rem+env(safe-area-inset-bottom))] sm:bg-transparent sm:pb-14" data-test="patient-luxury-specialists">
    @include('partials.patient-luxury-page-header', [
        'title' => $this->pageHeading(),
        'subtitle' => $this->pageSubtitle(),
        'profilePhotoUrl' => $this->profilePhotoUrl(),
        'userName' => auth()->user()?->name,
        'testId' => 'patient-specialists-header',
    ])

    <div class="relative mx-auto w-full max-w-7xl space-y-6 px-6 py-4 sm:px-5 sm:py-6">
    <section class="space-y-4 rounded-3xl border border-zinc-200/80 bg-white/95 p-4 shadow-[0_12px_28px_-20px_rgba(2,6,23,0.35)] backdrop-blur sm:p-5">
        <div class="flex items-center gap-2.5 sm:gap-3">
            <div class="relative flex-1">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="searchDoctor"
                    placeholder="{{ __('specialist_results.search_placeholder') }}"
                    class="h-12 w-full rounded-2xl border border-zinc-200/90 bg-zinc-50/70 ps-4 pe-11 text-sm font-medium text-zinc-800 placeholder:font-normal placeholder:text-zinc-400 transition focus:border-[#10B981] focus:bg-white focus:outline-none focus:ring-4 focus:ring-[#10B981]/15"
                />
                <flux:icon name="magnifying-glass" class="pointer-events-none absolute end-3 top-1/2 size-5 -translate-y-1/2 text-zinc-400" />
            </div>
            <button
                type="button"
                wire:click="toggleFilterPanel"
                class="inline-flex size-12 shrink-0 items-center justify-center rounded-2xl border border-zinc-200/90 bg-zinc-50/70 text-zinc-500 transition hover:border-[#10B981]/35 hover:bg-[#10B981]/5 hover:text-[#10B981]"
                aria-label="{{ __('specialist_results.filters') }}"
            >
                <flux:icon name="adjustments-horizontal" class="size-5" />
            </button>
        </div>

        <div @class(['grid grid-cols-4 gap-2 sm:grid-cols-8 sm:gap-2.5', 'hidden' => $this->instantBooking])>
            @foreach ($this->dayOptions as $day)
                <button
                    type="button"
                    wire:key="day-{{ $day['date'] }}"
                    wire:click="selectDate('{{ $day['date'] }}')"
                    @class([
                        'w-full rounded-2xl border px-2.5 py-2.5 text-center text-sm font-semibold transition',
                        $this->selectedDate === $day['date']
                            ? 'border-[#10B981] bg-[#10B981] text-white shadow-[0_10px_20px_-12px_rgb(16_185_129/0.45)]'
                            : 'border-zinc-200/80 bg-zinc-50/70 text-[#10B981] hover:border-[#10B981]/35 hover:bg-[#10B981]/5',
                    ])
                >
                    <div>{{ $day['day'] }}</div>
                    <div class="text-xs font-medium opacity-90">{{ $day['weekday'] }}</div>
                </button>
            @endforeach
        </div>

        @if ($this->instantBooking)
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/80 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ __('specialist_results.instant_window_hint', ['minutes' => app(\App\Services\DoctorAvailabilityService::class)->instantWindowMinutes()]) }}
            </div>
        @endif

        <div class="flex flex-wrap justify-center gap-2 pt-0.5">
            @foreach ($this->durationOptions as $minutes)
                <button
                    type="button"
                    wire:key="duration-{{ $minutes }}"
                    wire:click="selectDuration('{{ $minutes }}')"
                    @class([
                        'rounded-xl border px-3.5 py-2 text-sm font-semibold transition',
                        $this->selectedDuration === $minutes
                            ? 'border-[#10B981] bg-[#10B981] text-white shadow-[0_10px_20px_-12px_rgb(16_185_129/0.45)]'
                            : 'border-zinc-200/80 bg-zinc-50/70 text-[#10B981] hover:border-[#10B981]/35 hover:bg-[#10B981]/5',
                    ])
                >
                    {{ __('specialist_results.duration_minutes', ['minutes' => $minutes]) }}
                </button>
            @endforeach
        </div>
    </section>

    @if ($showFilterPanel)
        <div class="fixed inset-0 z-50">
            <button type="button" wire:click="toggleFilterPanel" class="absolute inset-0 bg-black/35" aria-label="Close"></button>

            <div class="absolute inset-y-0 end-0 w-full max-w-md overflow-y-auto border-s border-zinc-200 bg-white p-5 shadow-2xl">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">{{ __('specialist_results.filter_title') }}</flux:heading>
                    <button type="button" wire:click="toggleFilterPanel" class="rounded-lg p-2 text-zinc-500 hover:bg-zinc-100">
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>

                <div class="mt-5 space-y-5">
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-zinc-700">{{ __('session_filter.sections.gender_pref') }}</label>
                        <select wire:model="filterGender" class="h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm focus:border-[#10B981] focus:outline-none focus:ring-2 focus:ring-[#10B981]/20">
                            <option value="both">{{ __('session_filter.sections.gender.both') }}</option>
                            <option value="male">{{ __('session_filter.sections.gender.male') }}</option>
                            <option value="female">{{ __('session_filter.sections.gender.female') }}</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-zinc-700">{{ __('session_filter.sections.language') }}</label>
                        <select wire:model="filterLanguage" class="h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm focus:border-[#10B981] focus:outline-none focus:ring-2 focus:ring-[#10B981]/20">
                            <option value="both">{{ __('session_filter.sections.lang.both') }}</option>
                            <option value="ar">{{ __('session_filter.sections.lang.ar') }}</option>
                            <option value="en">{{ __('session_filter.sections.lang.en') }}</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-zinc-700">{{ __('session_filter.sections.specialist') }}</label>
                        <select wire:model="filterDegree" class="h-11 w-full rounded-xl border border-zinc-200 bg-white px-3 text-sm focus:border-[#10B981] focus:outline-none focus:ring-2 focus:ring-[#10B981]/20">
                            <option value="">{{ __('specialist_results.all_option') }}</option>
                            @foreach ($this->degreeOptions as $degree)
                                <option value="{{ $degree['id'] }}">{{ $degree['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-zinc-700">{{ __('session_filter.sections.subspecialties') }}</label>
                        <div class="flex max-h-56 flex-wrap gap-2 overflow-y-auto rounded-xl border border-zinc-200 bg-zinc-50/60 p-3">
                            @foreach ($this->subspecialityOptions as $speciality)
                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700">
                                    <input
                                        type="checkbox"
                                        value="{{ $speciality['id'] }}"
                                        wire:model.live="filterSubspecialties"
                                        class="size-3.5 rounded border-zinc-300 text-[#10B981] focus:ring-[#10B981]/30"
                                    />
                                    <span>{{ $speciality['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2 border-t border-zinc-200 pt-4">
                    <button type="button" wire:click="clearFilterPanel" class="rounded-xl border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-600 hover:bg-zinc-100">
                        {{ __('specialist_results.clear') }}
                    </button>
                    <button type="button" wire:click="applyFilterPanel" class="rounded-xl bg-[#10B981] px-4 py-2 text-sm font-semibold text-white shadow hover:brightness-95">
                        {{ __('specialist_results.apply') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if (count($this->visibleSpecialists) === 0)
        <div class="rounded-2xl border border-zinc-200/90 bg-white p-8 text-center shadow-md shadow-black/10">
            <flux:heading size="lg" class="text-zinc-900">{{ __('specialist_results.no_results_title') }}</flux:heading>
            <flux:text class="mx-auto mt-2 max-w-md text-zinc-600">{{ __('specialist_results.no_results_hint') }}</flux:text>
            <flux:button
                :href="route('patient.schedule.filter')"
                variant="primary"
                class="mt-6 border-[#064e3b] !bg-[#064e3b] !text-white hover:!brightness-[0.97]"
                wire:navigate
            >
                {{ __('specialist_results.adjust_filters') }}
            </flux:button>
        </div>
    @else
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            @foreach ($this->visibleSpecialists as $specialist)
                @include('partials.patient-specialist-result-card', [
                    'specialist' => $specialist,
                    'likes' => $this->likeCounts[$specialist['id']] ?? (int) $specialist['likes'],
                    'selectedDate' => $this->selectedDate,
                    'availableSlots' => $this->availableSlots($specialist),
                    'displayTimezone' => $this->patientTimezone(),
                ])
            @endforeach
        </div>
    @endif
</div>
