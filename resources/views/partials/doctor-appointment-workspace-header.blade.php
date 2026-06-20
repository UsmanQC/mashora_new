@props(['appointment', 'active'])

@php
    /** @var \App\Models\Appointment $appointment */

    $tabs = [
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
        'conversation' => [
            'label' => __('doctor.workspace.tab_conversation'),
            'route' => route('doctor.appointments.conversation', $appointment),
            'icon' => 'chat-bubble-left-right',
        ],
    ];

    if ($appointment->status === 'completed' && $appointment->parent_id === null) {
        $tabs['follow_up'] = [
            'label' => __('doctor.workspace.tab_follow_up'),
            'route' => route('doctor.appointments.follow-up', $appointment),
            'icon' => 'calendar-days',
        ];
    }

    if (in_array($appointment->status, ['new', 'in_process', 'rescheduled'], true)) {
        $tabs['reschedule'] = [
            'label' => __('doctor.workspace.tab_reschedule'),
            'route' => route('doctor.appointments.reschedule', $appointment),
            'icon' => 'clock',
        ];
    }
@endphp

<div class="space-y-5">
    <div>
        <flux:link :href="route('doctor.dashboard')" wire:navigate class="text-sm font-medium text-[#3C5CF7]">
            <flux:icon name="chevron-left" variant="mini" class="inline size-4 align-middle rtl:rotate-180" />
            {{ __('doctor.workspace.back_dashboard') }}
        </flux:link>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                @if ($appointment->appointment_number)
                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                        {{ __('doctor.workspace.appointment_number', ['number' => $appointment->appointment_number]) }}
                    </p>
                @endif
                <flux:heading size="lg" class="mt-1 font-semibold text-zinc-900">
                    {{ $appointment->patient_name }}
                </flux:heading>
                <p class="mt-1 text-sm text-zinc-600">
                    {{ $appointment->appointment_date?->format('d/m/Y') }}
                    @if ($appointment->formattedSessionStart() !== '')
                        — {{ $appointment->formattedSessionStart() }}
                    @endif
                </p>
            </div>
        </div>
    </div>

    <nav class="-mx-1 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="{{ __('doctor.workspace.title') }}">
        <ul class="flex min-w-max gap-2 px-1">
            @foreach ($tabs as $key => $tab)
                @php $isActive = $key === $active; @endphp
                <li>
                    <a
                        href="{{ $tab['route'] }}"
                        wire:navigate
                        @class([
                            'inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium transition',
                            '!border-[#132A6E] bg-[#132A6E] text-white' => $isActive,
                            'border-zinc-200 bg-white text-zinc-700 hover:border-[#3C5CF7] hover:text-[#3C5CF7]' => ! $isActive,
                        ])
                        @if ($isActive) aria-current="page" @endif
                    >
                        <flux:icon :name="$tab['icon']" variant="mini" class="size-4" />
                        {{ $tab['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
</div>
