<div
    class="doctor-luxury-appointments-week relative flex h-[100dvh] flex-col overflow-hidden bg-slate-50 lg:hidden"
    data-test="doctor-luxury-appointments-week"
>
    <header class="shrink-0 bg-gradient-to-b from-white to-slate-50 px-5 pb-4 pt-[max(2.25rem,env(safe-area-inset-top))]">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('doctor.dashboard') }}"
                wire:navigate
                class="flex size-10 shrink-0 items-center justify-center rounded-full border border-slate-100 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50"
                aria-label="{{ __('doctor.auth.back') }}"
            >
                <flux:icon name="chevron-left" variant="mini" class="size-5 rtl:rotate-180" />
            </a>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-bold tracking-tight text-slate-900">{{ __('doctor.appointments_week.title') }}</h1>
                <p class="mt-0.5 text-xs text-slate-500">{{ $this->weekRangeLabel }}</p>
            </div>
        </div>
    </header>

    <main class="doctor-luxury-scroll mx-auto flex min-h-0 w-full max-w-md flex-1 flex-col gap-4 overflow-y-auto overscroll-contain px-5 pb-[calc(5.5rem+env(safe-area-inset-bottom))] pt-1">
        @foreach ($this->weekDays as $day)
            <section wire:key="week-day-{{ $day['iso'] }}">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <p @class([
                        'text-[0.6875rem] font-bold uppercase tracking-wider',
                        'text-[#047857]' => $day['is_today'],
                        'text-slate-500' => ! $day['is_today'],
                    ])>
                        {{ $day['label'] }} · {{ $day['day_num'] }}
                        @if ($day['is_today'])
                            <span class="ms-1 rounded-full bg-[#10B981]/10 px-1.5 py-0.5 text-[0.5625rem] font-bold normal-case text-[#047857]">
                                {{ __('doctor.appointments_week.today') }}
                            </span>
                        @endif
                    </p>
                    <span class="text-[0.625rem] font-semibold text-slate-400">{{ $day['appointments']->count() }}</span>
                </div>

                @if ($day['appointments']->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white/60 px-4 py-3 text-center">
                        <p class="text-xs text-slate-400">{{ __('doctor.appointments_week.no_sessions') }}</p>
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach ($day['appointments'] as $appointment)
                            <a
                                href="{{ route('doctor.appointments.conversation', $appointment) }}"
                                wire:navigate
                                wire:key="week-appt-{{ $appointment->id }}"
                                class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-white p-3 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] transition active:scale-[0.99]"
                            >
                                <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#10B981]/10 text-xs font-bold text-[#047857]">
                                    {{ $this->initialsFor($appointment->patient_name) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-bold text-slate-900">{{ $appointment->patient_name }}</p>
                                    <p class="mt-0.5 text-[0.6875rem] text-slate-500">{{ $appointment->formattedSessionStart() ?: '—' }}</p>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-[0.625rem] font-bold {{ $this->statusBadgeClassesFor($appointment) }}">
                                    {{ __('doctor.appointment_status.'.$appointment->status) }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </main>
</div>
