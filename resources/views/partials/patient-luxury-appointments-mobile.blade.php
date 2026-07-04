@php
    $upcomingCount = $this->mobileSegmentCounts['upcoming'] ?? 0;
    $previousCount = $this->mobileSegmentCounts['previous'] ?? 0;
    $filterUrl = route('patient.schedule.filter');
    $chevron = app()->getLocale() === 'ar' ? 'left' : 'right';
@endphp

<div class="patient-luxury-appointments bg-slate-50 pb-[calc(4.75rem+env(safe-area-inset-bottom))] sm:hidden" data-test="patient-luxury-appointments">
    @include('partials.patient-luxury-page-header', [
        'title' => __('patient.appointments.title'),
        'subtitle' => $this->headerSubtitle(),
        'profilePhotoUrl' => $this->profilePhotoUrl(),
        'userName' => auth()->user()?->name,
        'testId' => 'patient-luxury-appointments-header',
    ])

    <main class="space-y-5 px-6 pt-5">
        @if ($mobileSegment === 'upcoming')
            <a
                href="{{ $filterUrl }}"
                wire:navigate
                class="active-scale inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#10B981] px-5 py-3 text-sm font-bold text-white shadow-[0_10px_30px_-8px_rgba(16,185,129,0.45)] transition hover:bg-[#059669]"
            >
                <flux:icon name="plus" variant="mini" class="size-4" />
                {{ __('patient.appointments.luxury.book_session_cta') }}
            </a>
        @endif

        <div
            class="flex gap-1 rounded-xl bg-slate-200/50 p-1"
            role="tablist"
            aria-label="{{ __('patient.appointments.tabs_aria') }}"
        >
            <button
                type="button"
                role="tab"
                wire:click="selectMobileSegment('upcoming')"
                wire:loading.attr="disabled"
                aria-selected="{{ $mobileSegment === 'upcoming' ? 'true' : 'false' }}"
                @class([
                    'flex-1 rounded-lg py-2 text-xs font-bold transition',
                    $mobileSegment === 'upcoming'
                        ? 'border border-slate-100 bg-white text-slate-900 shadow-sm'
                        : 'font-medium text-slate-500 hover:text-slate-700',
                ])
            >
                {{ __('patient.appointments.luxury.tab_upcoming') }} ({{ $upcomingCount }})
            </button>
            <button
                type="button"
                role="tab"
                wire:click="selectMobileSegment('previous')"
                wire:loading.attr="disabled"
                aria-selected="{{ $mobileSegment === 'previous' ? 'true' : 'false' }}"
                @class([
                    'flex-1 rounded-lg py-2 text-xs transition',
                    $mobileSegment === 'previous'
                        ? 'border border-slate-100 bg-white font-bold text-slate-900 shadow-sm'
                        : 'font-medium text-slate-500 hover:text-slate-700',
                ])
            >
                {{ __('patient.appointments.luxury.tab_previous') }}@if ($previousCount > 0) ({{ $previousCount }})@endif
            </button>
        </div>

        @if ($mobileSegment === 'upcoming')
            <a
                href="{{ $filterUrl }}"
                wire:navigate
                class="patient-luxury-appointments__instant active-scale group relative block overflow-hidden rounded-3xl bg-[#10B981] p-4 shadow-[0_10px_40px_-10px_rgba(16,185,129,0.35)] transition-colors hover:bg-[#059669]"
            >
                <div class="absolute end-0 top-0 size-28 translate-x-1/2 -translate-y-1/2 rounded-full bg-white/20 blur-3xl" aria-hidden="true"></div>
                <div class="relative z-10 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex size-10 items-center justify-center rounded-xl border border-white/20 bg-white/15 text-white backdrop-blur-sm">
                            <flux:icon name="bolt" variant="outline" class="size-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white">{{ __('patient.appointments.luxury.instant_title') }}</h3>
                            <p class="text-[10px] text-emerald-50/80">{{ __('patient.appointments.luxury.instant_note') }}</p>
                        </div>
                    </div>
                    <flux:icon name="chevron-{{ $chevron }}" variant="outline" class="size-4 text-white/60 transition group-hover:text-white" />
                </div>
            </a>
        @endif

        @if ($this->mobileAppointments->isEmpty())
            <div class="rounded-3xl border border-slate-100 bg-white px-6 py-12 text-center shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <flux:icon name="calendar-days" variant="outline" class="size-7" />
                </div>
                <p class="text-sm leading-relaxed text-slate-500">{{ $this->mobileEmptyMessage() }}</p>
                @if ($mobileSegment === 'upcoming')
                    <a
                        href="{{ $filterUrl }}"
                        wire:navigate
                        class="mt-5 inline-flex rounded-full bg-[#10B981] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#059669]"
                    >
                        {{ __('patient.appointments.book_new') }}
                    </a>
                @endif
            </div>
        @else
            <div class="space-y-4">
                @foreach ($this->mobileAppointments as $appointment)
                    @php
                        $cardUrl = $this->appointmentCardUrl($appointment);
                        $doctorName = $appointment->doctor?->displayName() ?: __('patient.appointments.specialist_label');
                        $isLive = $appointment->status === 'in_process';
                    @endphp
                    @if ($cardUrl)
                        <a
                            href="{{ $cardUrl }}"
                            wire:navigate
                            @class([
                                'patient-luxury-appointments__card active-scale relative block overflow-hidden rounded-3xl border bg-white p-5 shadow-[0_8px_32px_0_rgba(0,0,0,0.03)]',
                                'border-emerald-200/80 ring-1 ring-emerald-100' => $isLive,
                                'border-slate-100/80' => ! $isLive,
                            ])
                            wire:key="luxury-appointment-{{ $appointment->id }}"
                        >
                            @include('partials.patient-luxury-appointment-card-body', ['appointment' => $appointment, 'doctorName' => $doctorName])
                        </a>
                    @else
                        <article
                            @class([
                                'patient-luxury-appointments__card relative overflow-hidden rounded-3xl border bg-white p-5 shadow-[0_8px_32px_0_rgba(0,0,0,0.03)]',
                                'border-emerald-200/80 ring-1 ring-emerald-100' => $isLive,
                                'border-slate-100/80' => ! $isLive,
                            ])
                            wire:key="luxury-appointment-{{ $appointment->id }}"
                        >
                            @include('partials.patient-luxury-appointment-card-body', ['appointment' => $appointment, 'doctorName' => $doctorName])
                        </article>
                    @endif
                @endforeach
            </div>

            @if ($this->mobileAppointments->hasPages())
                <div class="pt-1">
                    {{ $this->mobileAppointments->links() }}
                </div>
            @endif
        @endif
    </main>
</div>
