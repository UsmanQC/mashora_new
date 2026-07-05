@php
    $isLive = $appointment->status === 'in_process';
@endphp

<div @class([
    'absolute bottom-0 start-0 top-0 w-1',
    'bg-[#10B981]' => ! $isLive,
    'bg-gradient-to-b from-[#10B981] to-emerald-400' => $isLive,
]) aria-hidden="true"></div>

<div class="mb-4 flex items-start justify-between gap-3 ps-2">
    <div class="flex min-w-0 items-center gap-3">
        @if ($this->doctorPhotoUrl($appointment) !== null)
            <img
                src="{{ $this->doctorPhotoUrl($appointment) }}"
                alt=""
                class="size-12 shrink-0 rounded-full bg-slate-100 object-cover ring-2 ring-white"
            />
        @else
            <flux:avatar :name="$doctorName" circle class="size-12 shrink-0 ring-2 ring-white" />
        @endif
        <div class="min-w-0">
            <h3 class="truncate text-sm font-bold text-slate-900">{{ $doctorName }}</h3>
            <p class="truncate text-[11px] font-medium text-[#059669]">{{ $this->doctorSpecialtyLabel($appointment) }}</p>
        </div>
    </div>
    <span @class([
        'shrink-0 rounded-full px-3 py-1 text-[10px] font-bold',
        $this->luxuryStatusBadgeClasses($appointment),
    ])>
        @if ($isLive)
            <span class="inline-flex items-center gap-1.5">
                <span class="relative flex size-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span>
                </span>
                {{ __('patient.appointments.luxury.live_session') }}
            </span>
        @else
            {{ $this->luxuryStatusLabel($appointment) }}
        @endif
    </span>
</div>

<div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 bg-slate-50 p-4 ps-2">
    <div class="flex min-w-0 items-center gap-2">
        <flux:icon name="calendar-days" variant="outline" class="size-4 shrink-0 text-slate-400" />
        <div class="min-w-0">
            <span class="block text-[10px] font-medium text-slate-500">{{ __('patient.appointments.luxury.schedule_label') }}</span>
            <span class="block text-xs font-bold text-slate-800">{{ $this->formattedLuxurySessionSchedule($appointment) }}</span>
        </div>
    </div>
    <div class="flex min-w-0 items-center gap-2">
        <flux:icon name="video-camera" variant="outline" class="size-4 shrink-0 text-slate-400" />
        <div class="min-w-0">
            <span class="block text-[10px] font-medium text-slate-500">{{ __('patient.appointments.luxury.session_type_label') }}</span>
            <span class="block text-xs font-bold text-slate-800">{{ $this->sessionTypeLabel($appointment) }}</span>
        </div>
    </div>
</div>

@if ($isLive && $appointment->allowsPatientCalls())
    <div class="mt-3 ps-2">
        <span class="flex w-full items-center justify-center gap-2 rounded-2xl bg-[#10B981] px-4 py-2.5 text-xs font-bold text-white shadow-sm shadow-emerald-900/15">
            <flux:icon name="video-camera" variant="mini" class="size-4" />
            {{ __('patient.appointments.luxury.join_video') }}
        </span>
    </div>
@elseif ($this->canResolveMissed($appointment))
    @include('partials.patient-luxury-missed-resolution', ['appointment' => $appointment])
@endif
