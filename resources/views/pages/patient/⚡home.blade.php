<?php

use App\Models\Appointment;
use App\Models\Notification;
use App\Models\PatientMood;
use App\Models\User;
use App\Services\PatientMoodLogService;
use App\Support\PatientMoodImage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient')] #[Title('Home')] class extends Component
{
    /**
     * @return list<array{iso: string, is_today: bool, label: string, mood_key: string|null}>
     */
    #[Computed]
    public function moodWeekDays(): array
    {
        $locale = app()->getLocale();

        $start = Carbon::now()->subDays(3)->startOfDay();
        $today = Carbon::now()->startOfDay();
        $rangeEnd = $start->copy()->addDays(6)->startOfDay();

        /** @var array<string, string> */
        $moodByIso = [];
        $user = Auth::user();
        if ($user !== null) {
            $records = PatientMood::query()
                ->where('user_id', $user->getKey())
                ->whereBetween('date', [$start->toDateString(), $rangeEnd->toDateString()])
                ->orderByDesc('id')
                ->get(['date', 'mood']);

            foreach ($records as $record) {
                $iso = $record->date->toDateString();
                if (isset($moodByIso[$iso])) {
                    continue;
                }
                $key = $record->mood;
                if (is_string($key) && in_array($key, PatientMoodImage::MOOD_KEYS, true)) {
                    $moodByIso[$iso] = $key;
                }
            }
        }

        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $d = $start->copy()->addDays($i)->locale($locale);
            $iso = $d->toDateString();

            $days[] = [
                'iso' => $iso,
                'is_today' => $d->equalTo($today),
                'label' => $d->isSameDay($today)
                    ? __('patient.today')
                    : $d->translatedFormat('D'),
                'mood_key' => $moodByIso[$iso] ?? null,
            ];
        }

        return $days;
    }

    #[Computed]
    public function greetingLabel(): string
    {
        $hour = (int) now()->format('G');

        if ($hour < 12) {
            return __('patient.home_luxury.greeting_morning');
        }

        if ($hour < 17) {
            return __('patient.home_luxury.greeting_afternoon');
        }

        return __('patient.home_luxury.greeting_evening');
    }

    #[Computed]
    public function todayMoodKey(): ?string
    {
        $user = Auth::user();

        if ($user === null) {
            return null;
        }

        return app(PatientMoodLogService::class)->moodKeyForToday($user);
    }

    /**
     * @return list<array{key: string, label: string, image_url: string|null, emoji: string}>
     */
    #[Computed]
    public function moodOptions(): array
    {
        return collect(PatientMoodImage::MOOD_KEYS)
            ->map(static fn (string $key): array => [
                'key' => $key,
                'label' => __('patient.mood_selector_options.'.$key),
                'image_url' => PatientMoodImage::url($key),
                'emoji' => PatientMoodImage::emoji($key),
            ])
            ->all();
    }

    #[Computed]
    public function activeSessionAppointment(): ?Appointment
    {
        $user = Auth::user();

        if ($user === null) {
            return null;
        }

        return Appointment::query()
            ->where('user_id', $user->getKey())
            ->where('status', 'in_process')
            ->with('doctor')
            ->latest('id')
            ->first();
    }

    #[Computed]
    public function unreadNotificationCount(): int
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return 0;
        }

        return Notification::query()
            ->where('userable_type', User::class)
            ->where('userable_id', $user->getKey())
            ->whereNull('read_at')
            ->count();
    }

    #[Computed]
    public function profilePhotoUrl(): ?string
    {
        $user = Auth::user();

        if ($user === null || ! filled($user->profile_photo_path)) {
            return null;
        }

        return Storage::disk('public')->url((string) $user->profile_photo_path);
    }

    public function selectMoodDay(string $iso): void
    {
        if (! Auth::check()) {
            $this->redirect(route('patient.phone'), navigate: true);

            return;
        }

        $this->dispatch('open-patient-mood-picker', dateIso: $iso);
    }

    public function selectMoodQuick(string $key): void
    {
        if (! Auth::check()) {
            $this->redirect(route('patient.phone'), navigate: true);

            return;
        }

        if (! in_array($key, PatientMoodImage::MOOD_KEYS, true)) {
            return;
        }

        $this->dispatch('open-patient-mood-picker', dateIso: now()->toDateString(), moodKey: $key);
    }

    #[On('patient-mood-saved')]
    public function refreshMoodWeek(): void
    {
        unset($this->moodWeekDays, $this->todayMoodKey, $this->moodOptions);
    }

    /**
     * @return array{text: string}
     */
    #[Computed]
    public function dailyThought(): array
    {
        return [
            'text' => __('patient.daily_balance'),
        ];
    }

}; ?>

<div class="relative w-full pb-4">
    <div
        wire:loading.flex
        wire:target="selectMoodDay,selectMoodQuick"
        class="absolute inset-0 z-20 items-center justify-center rounded-2xl bg-[#10B981]"
        aria-hidden="true"
    >
        @include('partials.patient-brand-logo', [
            'svgClass' => 'h-9 w-auto max-w-[9rem] object-contain',
            'onGreenChrome' => true,
        ])
    </div>

    @include('partials.patient-luxury-home-mobile')

    <div class="hidden space-y-5 sm:block sm:space-y-6">
        <section class="rounded-2xl border border-zinc-200/80 bg-white p-5 shadow-sm ring-1 ring-zinc-900/[0.04]">
            <flux:heading level="2" size="xl" class="font-semibold tracking-tight text-[#047857]">
                @if (auth()->check())
                    {{ __('patient.welcome_user', ['name' => Auth::user()?->name ?? '']) }}
                @else
                    {{ __('patient.welcome_guest') }}
                @endif
            </flux:heading>
            <flux:text class="mt-1.5 text-sm font-medium tabular-nums text-zinc-500">
                {{ now()->locale(app()->getLocale())->translatedFormat('M j, Y') }}
            </flux:text>
        </section>

        {{-- Mood week strip --}}
        <section class="space-y-3">
            <flux:heading size="lg" class="text-base font-semibold text-zinc-900 sm:text-lg">
                {{ __('patient.mood_section') }}
            </flux:heading>
            <div class="grid grid-cols-7 gap-1 sm:gap-2">
                @foreach ($this->moodWeekDays as $day)
                    @php($moodSrc = \App\Support\PatientMoodImage::url($day['mood_key'] ?? null))
                    <button
                        type="button"
                        wire:click="selectMoodDay('{{ $day['iso'] }}')"
                        wire:key="mood-day-{{ $day['iso'] }}"
                        class="flex cursor-pointer flex-col items-center gap-1.5 rounded-xl px-0.5 py-1 text-center transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#10B981]/35 focus-visible:ring-offset-2 sm:gap-2 sm:py-1.5"
                        aria-label="{{ $day['is_today'] ? __('patient.mood_strip_today_aria') : $day['label'] }}"
                    >
                        <span
                            @class([
                                'flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-full sm:size-[3.25rem]',
                                'bg-[#10B981] text-white shadow-sm ring-2 ring-[#10B981]/20' => $day['is_today'],
                                'border border-emerald-100/80 bg-emerald-50/60' => ! $day['is_today'],
                            ])
                        >
                            @if ($moodSrc !== null)
                                <img
                                    src="{{ $moodSrc }}"
                                    alt=""
                                    class="pointer-events-none size-7 object-contain sm:size-8"
                                    decoding="async"
                                    loading="lazy"
                                />
                            @else
                                <flux:icon
                                    name="{{ $day['is_today'] ? 'face-smile' : 'sparkles' }}"
                                    variant="mini"
                                    @class([
                                        'size-5 shrink-0 sm:size-6',
                                        '!text-white' => $day['is_today'],
                                        'text-[#10B981]' => ! $day['is_today'],
                                    ])
                                />
                            @endif
                        </span>
                        <flux:text @class([
                            'text-[0.625rem] font-semibold leading-none sm:text-sm',
                            'text-[#047857]' => $day['is_today'],
                            'text-zinc-600' => ! $day['is_today'],
                        ])>{{ $day['label'] }}</flux:text>
                    </button>
                @endforeach
            </div>
        </section>

        {{-- Session actions --}}
        <section class="space-y-3">
            <flux:heading size="lg" class="text-base font-semibold text-zinc-900 sm:text-lg">
                {{ __('patient.nav.appointments') }}
            </flux:heading>
            <div class="grid gap-3 sm:grid-cols-3 sm:gap-4">
                <a
                    href="{{ route('patient.schedule.filter') }}"
                    wire:navigate
                    class="group relative flex items-center gap-3 overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-3.5 shadow-sm ring-1 ring-zinc-900/[0.03] transition hover:border-orange-200 hover:shadow-md active:scale-[0.995] sm:flex-col sm:items-start sm:p-4"
                >
                    <span class="absolute inset-y-3 start-0 w-1 rounded-full bg-orange-500 sm:hidden" aria-hidden="true"></span>
                    <div
                        class="ms-1 flex size-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 text-white shadow-sm sm:ms-0 sm:mb-3 sm:size-12"
                    >
                        <flux:icon name="calendar-days" variant="mini" class="size-6 sm:size-7" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <flux:heading size="sm" class="font-semibold text-zinc-900">{{ __('patient.book_title') }}</flux:heading>
                        <flux:text class="mt-0.5 line-clamp-2 text-sm text-zinc-500">{{ __('patient.book_note') }}</flux:text>
                    </div>
                    <flux:icon
                        name="chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"
                        variant="mini"
                        class="size-5 shrink-0 text-orange-400/90 sm:hidden"
                    />
                    <flux:icon
                        name="chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"
                        variant="mini"
                        class="mt-3 hidden size-6 text-orange-500/70 ltr:group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 rtl:rotate-180 sm:block"
                    />
                </a>

                <a
                    href="{{ auth()->check() ? route('patient.schedule.filter') : route('patient.phone') }}"
                    wire:navigate
                    class="group relative flex items-center gap-3 overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-3.5 shadow-sm ring-1 ring-zinc-900/[0.03] transition hover:border-emerald-200 hover:shadow-md active:scale-[0.995] sm:flex-col sm:items-start sm:p-4"
                >
                    <span class="absolute inset-y-3 start-0 w-1 rounded-full bg-[#10B981] sm:hidden" aria-hidden="true"></span>
                    <div
                        class="ms-1 flex size-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-[#10B981] to-[#047857] text-white shadow-sm sm:ms-0 sm:mb-3 sm:size-12"
                    >
                        <flux:icon name="bolt" variant="mini" class="size-6 sm:size-7" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <flux:heading size="sm" class="font-semibold text-zinc-900">{{ __('patient.instant_title') }}</flux:heading>
                        <flux:text class="mt-0.5 line-clamp-2 text-sm text-zinc-500">{{ __('patient.instant_note') }}</flux:text>
                    </div>
                    <flux:icon
                        name="chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"
                        variant="mini"
                        class="size-5 shrink-0 text-emerald-500/90 sm:hidden"
                    />
                    <flux:icon
                        name="chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"
                        variant="mini"
                        class="mt-3 hidden size-6 text-emerald-600/70 ltr:group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 rtl:rotate-180 sm:block"
                    />
                </a>

                <a
                    href="{{ auth()->check() ? route('patient.appointments') : route('patient.phone') }}"
                    wire:navigate
                    class="group relative flex items-center gap-3 overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-3.5 shadow-sm ring-1 ring-zinc-900/[0.03] transition hover:border-sky-200 hover:shadow-md active:scale-[0.995] sm:flex-col sm:items-start sm:p-4"
                >
                    <span class="absolute inset-y-3 start-0 w-1 rounded-full bg-sky-500 sm:hidden" aria-hidden="true"></span>
                    <div
                        class="ms-1 flex size-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-sky-500 to-sky-600 text-white shadow-sm sm:ms-0 sm:mb-3 sm:size-12"
                    >
                        <flux:icon name="clipboard-document-check" variant="mini" class="size-6 sm:size-7" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <flux:heading size="sm" class="font-semibold text-zinc-900">{{ __('patient.ongoing_title') }}</flux:heading>
                        <flux:text class="mt-0.5 line-clamp-2 text-sm text-zinc-500">{{ __('patient.ongoing_note') }}</flux:text>
                    </div>
                    <flux:icon
                        name="chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"
                        variant="mini"
                        class="size-5 shrink-0 text-sky-500/90 sm:hidden"
                    />
                    <flux:icon
                        name="chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"
                        variant="mini"
                        class="mt-3 hidden size-6 text-sky-600/70 ltr:group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 rtl:rotate-180 sm:block"
                    />
                </a>
            </div>
        </section>
    </div>
</div>
