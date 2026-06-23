<?php

use App\Models\PatientMood;
use App\Support\PatientMoodImage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
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

    public function selectMoodDay(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('patient.phone'), navigate: true);

            return;
        }

        $this->dispatch('open-patient-mood-picker');
    }

    #[On('patient-mood-saved')]
    public function refreshMoodWeek(): void
    {
        unset($this->moodWeekDays);
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

<div class="mx-auto max-w-4xl pb-10">
    {{-- Masthead — legacy: blue heading on light gray canvas; brand stays in sidebar only --}}
    <header class="border-b border-slate-200/80 bg-slate-100 px-4 py-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <flux:heading level="2" size="xl" class="font-semibold text-emerald-700">
                    @if (auth()->check())
                        {{ __('patient.welcome_user', ['name' => Auth::user()?->name ?? '']) }}
                    @else
                        {{ __('patient.welcome_guest') }}
                    @endif
                </flux:heading>
                <flux:text class="mt-1 font-medium tabular-nums text-slate-500">
                    {{ now()->locale(app()->getLocale())->translatedFormat('M j, Y') }}
                </flux:text>
            </div>
        </div>
    </header>

    <div class="space-y-6 px-4 pt-6">
        {{-- Mood week strip: first after sign-in so daily check-in stays visible --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <flux:heading size="lg">{{ __('patient.mood_section') }}</flux:heading>
                <a
                    href="{{ route('patient.phone') }}"
                    wire:navigate
                    class="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-[#10B981] underline-offset-4 transition hover:text-[#059669] hover:underline focus:outline-none focus-visible:underline"
                >
                    {{ __('patient.view_all') }}
                    <flux:icon name="chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}" variant="mini" class="size-4 rtl:rotate-180" />
                </a>
            </div>
            <div
                class="flex snap-x snap-mandatory gap-4 overflow-x-auto py-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                x-data
                x-init="$nextTick(() => { const el = $el.querySelector('[data-today-mood]'); el?.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' }); })"
            >
                @foreach ($this->moodWeekDays as $day)
                    @php($moodSrc = \App\Support\PatientMoodImage::url($day['mood_key'] ?? null))
                    <button
                        type="button"
                        @if($day['is_today']) data-today-mood @endif
                        wire:click="selectMoodDay"
                        wire:key="mood-day-{{ $day['iso'] }}"
                        @class([
                            'flex min-w-[6.75rem] shrink-0 snap-center cursor-pointer flex-col items-center gap-3.5 rounded-2xl border px-5 py-5 text-center shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#10B981]/35 focus-visible:ring-offset-2',
                            'border-amber-400 bg-amber-50 ring-2 ring-amber-200/80 hover:border-amber-300/80' => $day['is_today'],
                            'border-zinc-200 bg-white hover:border-amber-300/60' => ! $day['is_today'],
                        ])
                        aria-label="{{ $day['is_today'] ? __('patient.mood_strip_today_aria') : $day['label'] }}"
                    >
                        <span
                            @class([
                                'flex size-[3.75rem] shrink-0 items-center justify-center overflow-hidden rounded-full p-1',
                                'bg-amber-500 text-white shadow-inner ring-2 ring-white/40' => $day['is_today'],
                                'border border-[#10B981]/20 bg-[#ecfdf5]/80 shadow-sm' => ! $day['is_today'],
                            ])
                        >
                            @if ($moodSrc !== null)
                                <img
                                    src="{{ $moodSrc }}"
                                    alt=""
                                    class="pointer-events-none size-[2.875rem] object-contain"
                                    decoding="async"
                                    loading="lazy"
                                />
                            @else
                                <flux:icon
                                    name="{{ $day['is_today'] ? 'face-smile' : 'sparkles' }}"
                                    variant="mini"
                                    @class([
                                        'size-7 shrink-0',
                                        '!text-white' => $day['is_today'],
                                        'text-[#10B981]' => ! $day['is_today'],
                                    ])
                                />
                            @endif
                        </span>
                        <flux:text class="text-sm font-semibold leading-tight text-zinc-700">{{ $day['label'] }}</flux:text>
                    </button>
                @endforeach
            </div>
        </div>

        <flux:separator />

        {{-- Spotlight tiles: original vibrant gradients; grid on sm+ --}}
        <div class="grid gap-4 sm:grid-cols-2 sm:gap-5">
            <div
                class="relative flex min-h-44 flex-col justify-between rounded-2xl bg-gradient-to-br from-amber-400 via-orange-500 to-orange-600 p-5 text-white shadow-lg shadow-orange-500/25"
            >
                <flux:text class="relative z-0 text-base leading-relaxed font-medium text-white/95">
                    {{ $this->dailyThought['text'] }}
                </flux:text>
                <div class="relative z-0 flex items-center justify-end gap-2 pt-5">
                    {{-- Alpine payload must stay off <flux:button>: Blade mishandles nested PHP/tags in the opener and drops component compilation. --}}
                    <div
                        class="contents"
                        x-data='{
                            thought: @json(__('patient.daily_balance')),
                            title: @json(__('patient.thought_badge')),
                            url: @json(route('patient.home')),
                            async shareThought() {
                                const payload = { title: this.title, text: this.thought, url: this.url };
                                if (navigator.share) {
                                    try {
                                        await navigator.share(payload);
                                    } catch (e) {
                                        if (e?.name !== "AbortError") {
                                            await this.copyThought(payload.text, payload.url);
                                        }
                                    }
                                    return;
                                }
                                await this.copyThought(payload.text, payload.url);
                            },
                            async copyThought(text, url) {
                                if (!navigator.clipboard?.writeText) {
                                    alert(@json(__('patient.share_failed')));
                                    return;
                                }
                                await navigator.clipboard.writeText(text + "\\n" + url);
                            }
                        }'
                    >
                        <flux:button
                            type="button"
                            variant="ghost"
                            class="shrink-0 [&]:text-white hover:[&]:bg-white/15"
                            icon="share"
                            x-on:click.prevent="shareThought()"
                        >
                            {{ __('patient.share') }}
                        </flux:button>
                    </div>
                </div>
            </div>

            <a
                href="{{ auth()->check() ? route('patient.menu') : route('patient.phone') }}"
                wire:navigate
                class="flex min-h-44 flex-col justify-between rounded-2xl bg-gradient-to-br from-emerald-400 via-teal-500 to-emerald-600 p-5 text-white shadow-lg shadow-teal-500/25 outline-none ring-transparent transition hover:ring-2 hover:ring-teal-200/70 focus-visible:ring-2 focus-visible:ring-teal-200/70"
                title="{{ __('patient.metrics_description') }}"
            >
                <div>
                    <flux:text class="text-sm font-semibold uppercase tracking-wide text-white/85">
                        {{ __('patient.metrics_title') }}
                    </flux:text>
                    <flux:heading size="lg" class="mt-3 text-balance text-white">
                        {{ __('patient.metrics_heading') }}
                    </flux:heading>
                </div>
                <flux:text class="mt-3 text-sm leading-relaxed text-emerald-50">
                    {{ __('patient.metrics_description') }}
                </flux:text>
                <flux:badge variant="pill" color="lime" class="mt-4 w-fit">
                    {{ __('patient.view_all') }}
                </flux:badge>
                @guest
                    <flux:text class="sr-only">{{ __('patient.metrics_sign_in_prompt') }}</flux:text>
                @endguest
            </a>
        </div>

        {{-- Three appointment actions --}}
        <flux:separator />
        <div class="grid gap-4 sm:grid-cols-3">
            <a
                href="{{ route('patient.schedule.filter') }}"
                wire:navigate
                class="group flex flex-col rounded-2xl border border-orange-200/80 bg-orange-50/90 p-4 shadow-sm transition hover:border-orange-300 hover:shadow-md"
            >
                <div
                    class="mb-3 flex size-12 items-center justify-center rounded-xl bg-orange-500 text-white shadow-inner"
                >
                    <flux:icon name="calendar-days" variant="mini" class="size-7" />
                </div>
                <flux:heading size="sm" class="text-orange-900">{{ __('patient.book_title') }}</flux:heading>
                <flux:text class="mt-2 grow text-sm text-orange-900/80">{{ __('patient.book_note') }}</flux:text>
                <flux:icon
                    name="chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"
                    variant="mini"
                    class="mt-3 size-6 text-orange-700/70 ltr:group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 rtl:rotate-180"
                />
            </a>

            <a
                href="{{ auth()->check() ? route('patient.schedule.filter') : route('patient.phone') }}"
                wire:navigate
                class="group flex flex-col rounded-2xl border border-emerald-200/80 bg-emerald-50/90 p-4 shadow-sm transition hover:border-emerald-300 hover:shadow-md"
            >
                <div
                    class="mb-3 flex size-12 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-inner"
                >
                    <flux:icon name="bolt" variant="mini" class="size-7" />
                </div>
                <flux:heading size="sm" class="text-emerald-900">{{ __('patient.instant_title') }}</flux:heading>
                <flux:text class="mt-2 grow text-sm text-emerald-900/80">{{ __('patient.instant_note') }}</flux:text>
                <flux:icon
                    name="chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"
                    variant="mini"
                    class="mt-3 size-6 text-emerald-800/70 ltr:group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 rtl:rotate-180"
                />
            </a>

            <a
                href="{{ auth()->check() ? route('patient.appointments') : route('patient.phone') }}"
                wire:navigate
                class="group flex flex-col rounded-2xl border border-sky-200/80 bg-sky-50/90 p-4 shadow-sm transition hover:border-sky-300 hover:shadow-md sm:col-span-1 sm:aspect-auto sm:justify-start"
            >
                <div
                    class="mb-3 flex size-12 items-center justify-center rounded-xl bg-sky-600 text-white shadow-inner"
                >
                    <flux:icon name="clipboard-document-check" variant="mini" class="size-7" />
                </div>
                <flux:heading size="sm" class="text-sky-950">{{ __('patient.ongoing_title') }}</flux:heading>
                <flux:text class="mt-2 grow text-sm text-sky-950/85">{{ __('patient.ongoing_note') }}</flux:text>
                <flux:icon
                    name="chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"
                    variant="mini"
                    class="mt-3 size-6 text-sky-900/70 ltr:group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 rtl:rotate-180"
                />
            </a>
        </div>
    </div>
</div>
