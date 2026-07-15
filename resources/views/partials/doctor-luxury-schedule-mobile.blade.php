@php
    $selectedDay = \Illuminate\Support\Carbon::parse($day)->locale(app()->getLocale());
@endphp

<div
    class="doctor-luxury-schedule relative flex h-[100dvh] flex-col overflow-hidden bg-slate-50 lg:hidden"
    data-test="doctor-luxury-schedule"
>
    <header class="shrink-0 bg-gradient-to-b from-white to-slate-50 px-5 pb-4 pt-[max(2.25rem,env(safe-area-inset-top))]">
        <p class="mb-1 text-[0.6875rem] font-medium text-slate-500">
            {{ $selectedDay->translatedFormat('F Y') }}
        </p>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">
            {{ __('doctor.mobile.schedule_title') }}
        </h1>
    </header>

    <div class="shrink-0 px-5 pb-3">
        <div class="doctor-luxury-schedule-days -mx-1 flex gap-2 overflow-x-auto overscroll-x-contain pb-1">
            @foreach ($this->scheduleDays() as $scheduleDay)
                <button
                    type="button"
                    wire:click="selectScheduleDay('{{ $scheduleDay['iso'] }}')"
                    wire:key="doctor-schedule-day-{{ $scheduleDay['iso'] }}"
                    @class([
                        'doctor-luxury-schedule-day shrink-0',
                        'is-selected' => $scheduleDay['is_selected'],
                        'is-today' => $scheduleDay['is_today'] && ! $scheduleDay['is_selected'],
                    ])
                    aria-pressed="{{ $scheduleDay['is_selected'] ? 'true' : 'false' }}"
                    aria-label="{{ $scheduleDay['weekday'] }} {{ $scheduleDay['day_num'] }}"
                >
                    <span class="doctor-luxury-schedule-day__weekday">{{ $scheduleDay['weekday'] }}</span>
                    <span class="doctor-luxury-schedule-day__num">{{ $scheduleDay['day_num'] }}</span>
                    @if ($scheduleDay['has_appointments'])
                        <span class="doctor-luxury-schedule-day__dot" aria-hidden="true"></span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    <div class="shrink-0 px-5 pb-3">
        <div
            class="doctor-luxury-schedule-filters flex gap-1 overflow-x-auto overscroll-x-contain rounded-xl bg-slate-200/50 p-1"
            role="tablist"
            aria-label="{{ __('doctor.appointments.filters_aria') }}"
        >
            @foreach ($this->statusOptions() as $key => $label)
                @php
                    $count = $this->dayFilterCount($key);
                    $isActive = $status === $key;
                    $filterLabel = $key === 'all'
                        ? $label.' • '.$count
                        : $label.($count > 0 ? ' ('.$count.')' : '');
                @endphp
                <button
                    type="button"
                    role="tab"
                    wire:click="$set('status', '{{ $key }}')"
                    wire:key="doctor-luxury-filter-{{ $key }}"
                    aria-selected="{{ $isActive ? 'true' : 'false' }}"
                    @class([
                        'shrink-0 rounded-lg px-3 py-2 text-xs font-semibold whitespace-nowrap transition',
                        $isActive
                            ? 'border border-slate-100 bg-white text-slate-900 shadow-sm'
                            : 'font-semibold text-slate-900 hover:text-slate-900',
                    ])
                >
                    {{ $filterLabel }}
                </button>
            @endforeach
        </div>
    </div>

    <main class="mx-auto flex min-h-0 w-full max-w-md flex-1 flex-col overflow-y-auto overscroll-contain px-5 pb-[calc(4.75rem+env(safe-area-inset-bottom))]">
        @if ($this->mobileScheduleAppointments->isEmpty())
            <div class="rounded-3xl border border-slate-100 bg-white px-6 py-12 text-center shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <flux:icon name="calendar-days" variant="outline" class="size-7" />
                </div>
                <p class="text-sm leading-relaxed text-slate-500">{{ __('doctor.appointments.empty') }}</p>
            </div>
        @else
            <p class="mb-3 text-[0.625rem] font-medium text-slate-400">
                {{ __('doctor.mobile.schedule_swipe_hint') }}
            </p>

            <div class="space-y-3">
                @foreach ($this->mobileScheduleAppointments as $appointment)
                    @php
                        $badge = $this->mobileInlineBadge($appointment);
                        $showStartTimer = $this->shouldShowStartTimer($appointment);
                        $canManageFollowUp = $this->canManageFollowUpActions($appointment);
                        $followUpRescheduleHref = $canManageFollowUp ? $this->followUpRescheduleHref($appointment) : null;
                    @endphp
                    <div
                        wire:key="doctor-luxury-appt-{{ $appointment->id }}"
                        @class([
                            'doctor-luxury-schedule-card overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]',
                            'border-s-4 '.$this->mobileCardAccentClass($appointment),
                        ])
                    >
                        <a
                            href="{{ $this->mobileCardHref($appointment) }}"
                            wire:navigate
                            class="block transition active:scale-[0.99]"
                        >
                            <div class="flex items-center gap-3 p-4">
                                <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-[#10B981]/10 text-sm font-bold text-[#047857]">
                                    {{ $this->initialsFor($appointment->patient_name) }}
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="mb-0.5 flex flex-wrap items-center gap-2">
                                        <p class="truncate text-sm font-bold text-slate-900">{{ $appointment->patient_name }}</p>
                                        @if ($badge)
                                            <span @class(['inline-flex items-center rounded-full px-2 py-0.5 text-[0.625rem] font-semibold', $badge['classes']])>
                                                @if ($badge['label'] === __('doctor.mobile.badge_now'))
                                                    <span class="me-1 size-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                                                @endif
                                                {{ $badge['label'] }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="truncate text-[0.6875rem] text-slate-500">
                                        {{ $this->mobileSessionLine($appointment) }}
                                    </p>
                                </div>

                                <div class="shrink-0 text-end">
                                    <p class="text-sm font-bold tabular-nums text-slate-900">
                                        {{ $appointment->formattedSessionStart() ?: \Illuminate\Support\Str::limit((string) $appointment->start_time, 5, '') }}
                                    </p>
                                    <p class="text-[0.625rem] font-medium text-slate-400">
                                        {{ __('doctor.dashboard.minutes_label', ['m' => $appointment->duration]) }}
                                    </p>
                                </div>
                            </div>
                        </a>

                        @if ($showStartTimer)
                            <div
                                class="border-t border-slate-100 px-4 py-2.5"
                                x-data="appointmentStartTimer(@js($this->sessionStartsAtIso($appointment)))"
                                x-init="start()"
                            >
                                <p class="flex items-center justify-center gap-1.5 text-xs font-semibold text-[#047857]">
                                    <flux:icon name="clock" variant="mini" class="size-3.5" />
                                    <span x-text="label"></span>
                                </p>
                            </div>
                        @endif

                        @if ($canManageFollowUp)
                            <div class="flex gap-2 border-t border-slate-100 px-4 py-3" data-test="doctor-follow-up-mobile-actions">
                                @if ($followUpRescheduleHref)
                                    <a
                                        href="{{ $followUpRescheduleHref }}"
                                        wire:navigate
                                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2.5 text-xs font-semibold text-indigo-800 transition active:scale-[0.98]"
                                    >
                                        <flux:icon name="calendar-days" variant="mini" class="size-3.5 shrink-0" />
                                        {{ __('doctor.follow_up.reschedule') }}
                                    </a>
                                @endif
                                <button
                                    type="button"
                                    wire:click="promptCancelAppointment({{ $appointment->id }})"
                                    class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-xs font-semibold text-rose-700 transition active:scale-[0.98]"
                                >
                                    <flux:icon name="x-circle" variant="mini" class="size-3.5 shrink-0" />
                                    {{ __('doctor.follow_up.cancel') }}
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </main>
</div>
