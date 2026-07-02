@php
    $upcomingCount = $this->mobileSegmentCounts['upcoming'] ?? 0;
    $previousCount = $this->mobileSegmentCounts['previous'] ?? 0;
    $filterUrl = route('patient.schedule.filter');
    $chevron = app()->getLocale() === 'ar' ? 'left' : 'right';
@endphp

<div class="patient-luxury-appointments bg-slate-50 pb-[calc(4.75rem+env(safe-area-inset-bottom))] sm:hidden" data-test="patient-luxury-appointments">
    @include('partials.patient-luxury-page-header', [
        'title' => __('patient.appointments.title'),
        'subtitle' => $this->headerDateLabel(),
        'profilePhotoUrl' => $this->profilePhotoUrl(),
        'userName' => auth()->user()?->name,
        'testId' => 'patient-luxury-appointments-header',
    ])

    <main class="space-y-6 px-6 pt-6">
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
                    @endphp
                    @if ($cardUrl)
                        <a
                            href="{{ $cardUrl }}"
                            wire:navigate
                            class="patient-luxury-appointments__card active-scale relative block overflow-hidden rounded-3xl border border-slate-100/80 bg-white p-5 shadow-[0_8px_32px_0_rgba(0,0,0,0.03)]"
                            wire:key="luxury-appointment-{{ $appointment->id }}"
                        >
                            @include('partials.patient-luxury-appointment-card-body', ['appointment' => $appointment, 'doctorName' => $doctorName])
                        </a>
                    @else
                        <article
                            class="patient-luxury-appointments__card relative overflow-hidden rounded-3xl border border-slate-100/80 bg-white p-5 shadow-[0_8px_32px_0_rgba(0,0,0,0.03)]"
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

        @if ($mobileSegment === 'upcoming')
            <a
                href="{{ $filterUrl }}"
                wire:navigate
                class="patient-luxury-appointments__instant active-scale flex items-center justify-between rounded-3xl border border-slate-100/80 bg-white p-4 shadow-[0_8px_32px_0_rgba(0,0,0,0.03)]"
            >
                <div class="flex items-center gap-4">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-amber-50 text-amber-500">
                        <flux:icon name="bolt" variant="outline" class="size-5" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">{{ __('patient.appointments.luxury.instant_title') }}</h3>
                        <p class="text-[10px] text-slate-500">{{ __('patient.appointments.luxury.instant_note') }}</p>
                    </div>
                </div>
                <flux:icon name="chevron-{{ $chevron }}" variant="outline" class="size-4 text-slate-300" />
            </a>
        @endif
    </main>
</div>
