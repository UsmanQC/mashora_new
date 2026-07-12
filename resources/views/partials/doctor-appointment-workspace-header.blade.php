@props(['appointment', 'active'])

@php
    /** @var \App\Models\Appointment $appointment */

    $followUpService = app(\App\Services\FollowUpAppointmentService::class);

    $tabs = [
        'conversation' => [
            'label' => __('doctor.workspace.tab_conversation'),
            'route' => route('doctor.appointments.conversation', $appointment),
            'icon' => 'chat-bubble-left-right',
        ],
        'medical_history' => [
            'label' => __('doctor.workspace.tab_medical_history'),
            'route' => route('doctor.appointments.medical-history', $appointment),
            'icon' => 'clipboard-document-list',
        ],
        'diagnosis' => [
            'label' => __('doctor.workspace.tab_diagnosis'),
            'route' => route('doctor.appointments.diagnosis', $appointment),
            'icon' => 'document-text',
        ],
        'prescription' => [
            'label' => __('doctor.workspace.tab_prescription'),
            'route' => route('doctor.appointments.prescription', $appointment),
            'icon' => 'beaker',
        ],
    ];

    if ($appointment->parent_id === null && $appointment->status === 'completed') {
        $pendingFollowUp = $followUpService->pendingFollowUpFor($appointment);

        if ($pendingFollowUp !== null || $followUpService->parentCanScheduleFollowUp($appointment)) {
            $tabs['follow_up'] = [
                'label' => __('doctor.workspace.tab_follow_up'),
                'route' => route('doctor.appointments.follow-up', $appointment),
                'icon' => 'calendar-days',
            ];
        }
    }

    $statusKey = (string) $appointment->status;
    $statusLabel = __('doctor.appointment_status.'.$statusKey);
    $statusBadgeClass = match ($statusKey) {
        'in_process' => 'bg-emerald-50 text-emerald-800 ring-emerald-600/15',
        'completed' => 'bg-zinc-100 text-zinc-700 ring-zinc-900/10',
        'new', 'rescheduled' => 'bg-sky-50 text-sky-800 ring-sky-600/15',
        default => 'bg-amber-50 text-amber-900 ring-amber-600/15',
    };
    $mobileStatusBadgeClass = match ($statusKey) {
        'in_process' => 'bg-emerald-100 text-emerald-800',
        'completed' => 'bg-zinc-100 text-zinc-600',
        'new', 'rescheduled' => 'bg-sky-100 text-sky-800',
        default => 'bg-amber-100 text-amber-900',
    };
@endphp

<div class="lg:hidden">
    <div class="mb-3 flex items-center gap-3">
        <a
            href="{{ route('doctor.dashboard') }}"
            wire:navigate
            class="flex size-10 shrink-0 items-center justify-center rounded-full border border-slate-100 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50"
            aria-label="{{ __('doctor.workspace.back_dashboard') }}"
        >
            <flux:icon name="chevron-left" variant="mini" class="size-5 rtl:rotate-180" />
        </a>
        <div class="min-w-0 flex-1">
            <p class="truncate text-base font-bold text-slate-900">{{ $appointment->patient_name }}</p>
            <p class="mt-0.5 truncate text-xs text-slate-500">
                @if ($appointment->appointment_number)
                    {{ $appointment->appointment_number }} ·
                @endif
                {{ $appointment->appointment_date?->format('d/m/Y') }}
                @if ($appointment->formattedSessionStart() !== '')
                    · {{ $appointment->formattedSessionStart() }}
                @endif
            </p>
        </div>
        <span @class([
            'shrink-0 rounded-full px-2.5 py-1 text-[0.625rem] font-bold uppercase tracking-wide',
            $mobileStatusBadgeClass,
        ])>
            {{ $statusLabel }}
        </span>
    </div>

    <div class="doctor-workspace-tabs -mx-1 mb-3 flex gap-1.5 overflow-x-auto overscroll-x-contain px-1 pb-1">
        @foreach ($tabs as $key => $tab)
            @php $isActive = $key === $active; @endphp
            <a
                href="{{ $tab['route'] }}"
                wire:navigate
                @class([
                    'flex shrink-0 items-center gap-1.5 rounded-full border px-3 py-2 text-xs font-bold whitespace-nowrap transition',
                    'border-transparent bg-[#047857] text-white shadow-sm' => $isActive,
                    'border-slate-200 bg-white text-slate-600' => ! $isActive,
                ])
                @if ($isActive) aria-current="page" @endif
            >
                <flux:icon :name="$tab['icon']" variant="mini" class="size-3.5 shrink-0" />
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>

    @include('partials.doctor-appointment-workflow-stepper', ['appointment' => $appointment, 'active' => $active])
</div>

<div class="hidden space-y-4 lg:block">
    <div class="flex items-center justify-between gap-3">
        <flux:link :href="route('doctor.dashboard')" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-zinc-500 transition hover:text-[#10B981]">
            <flux:icon name="chevron-left" variant="mini" class="size-4 rtl:rotate-180" />
            {{ __('doctor.workspace.back_dashboard') }}
        </flux:link>

        <span @class([
            'inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide ring-1 ring-inset',
            $statusBadgeClass,
        ])>
            {{ $statusLabel }}
        </span>
    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm ring-1 ring-zinc-900/[0.03]">
        <div class="border-b border-zinc-100 bg-gradient-to-b from-zinc-50/80 to-white px-4 py-4 sm:px-6 sm:py-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    @if ($appointment->appointment_number)
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-400">
                            {{ __('doctor.workspace.appointment_number', ['number' => $appointment->appointment_number]) }}
                        </p>
                    @endif
                    <flux:heading size="xl" class="mt-1 font-semibold tracking-tight text-zinc-900 sm:text-2xl">
                        {{ $appointment->patient_name }}
                    </flux:heading>
                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-zinc-600">
                        <span class="inline-flex items-center gap-1.5">
                            <flux:icon name="calendar-days" variant="mini" class="size-4 text-zinc-400" />
                            {{ $appointment->appointment_date?->format('d/m/Y') }}
                        </span>
                        @if ($appointment->formattedSessionStart() !== '')
                            <span class="hidden text-zinc-300 sm:inline" aria-hidden="true">|</span>
                            <span class="inline-flex items-center gap-1.5">
                                <flux:icon name="clock" variant="mini" class="size-4 text-zinc-400" />
                                {{ $appointment->formattedSessionStart() }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <nav class="px-3 py-3 sm:px-4" aria-label="{{ __('doctor.workspace.title') }}">
            <div class="doctor-workspace-tabs overflow-x-auto">
                <ul class="flex min-w-max gap-1 rounded-xl bg-zinc-100/90 p-1 sm:min-w-0 sm:w-full">
                    @foreach ($tabs as $key => $tab)
                        @php $isActive = $key === $active; @endphp
                        <li class="min-w-0 sm:flex-1">
                            <a
                                href="{{ $tab['route'] }}"
                                wire:navigate
                                @class([
                                    'doctor-workspace-tab flex items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-xs font-semibold transition sm:px-4 sm:text-sm',
                                    'bg-white text-[#047857] shadow-sm ring-1 ring-zinc-900/[0.04]' => $isActive,
                                    'text-zinc-600 hover:bg-white/70 hover:text-zinc-900' => ! $isActive,
                                ])
                                @if ($isActive) aria-current="page" @endif
                            >
                                <flux:icon :name="$tab['icon']" variant="mini" @class([
                                    'size-4 shrink-0',
                                    'text-[#10B981]' => $isActive,
                                    'text-zinc-400' => ! $isActive,
                                ]) />
                                <span class="whitespace-nowrap">{{ $tab['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </nav>

        <div class="border-t border-zinc-100 px-3 pb-4 sm:px-4 sm:pb-5">
            @include('partials.doctor-appointment-workflow-stepper', ['appointment' => $appointment, 'active' => $active])
        </div>
    </div>
</div>
