{{-- Parent: doctor dashboard Livewire page (wire:click targets it). --}}
@props(['appointment', 'dashboard'])

@php
    /** @var \App\Models\Appointment $appointment */
    /** @var \Livewire\Component $dashboard */

    $statusSlug = (string) $appointment->status;
    $statusLabel = $dashboard->statusLabelFor($appointment);
    $badgeColor = $dashboard->statusBadgeColorFor($appointment);
@endphp

<div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm" wire:key="up-{{ $appointment->id }}">
    @if ($appointment->appointment_number)
        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
            {{ __('doctor.dashboard.appt_number', ['number' => $appointment->appointment_number]) }}
        </p>
        <hr class="mt-3 border-zinc-100" />
    @endif

    <dl class="mt-4 space-y-4 text-sm">
        <div class="flex justify-between gap-6">
            <dt class="shrink-0 font-semibold text-zinc-900">{{ __('doctor.card.patient') }}</dt>
            <dd class="min-w-0 text-end font-medium text-zinc-900">{{ $appointment->patient_name }}</dd>
        </div>
        <div class="flex justify-between gap-6">
            <dt class="shrink-0 font-semibold text-zinc-900">{{ __('doctor.card.session_status') }}</dt>
            <dd class="min-w-0 text-end">
                <flux:badge variant="pill" color="{{ $badgeColor }}" size="sm">{{ $statusLabel }}</flux:badge>
            </dd>
        </div>
        <div class="flex justify-between gap-6">
            <dt class="shrink-0 font-semibold text-zinc-900">{{ __('doctor.card.session_date') }}</dt>
            <dd class="min-w-0 text-end tabular-nums font-medium text-zinc-900">{{ $appointment->appointment_date?->format('d/m/Y') }}</dd>
        </div>
        <div class="flex justify-between gap-6">
            <dt class="shrink-0 font-semibold text-zinc-900">{{ __('doctor.card.session_time') }}</dt>
            <dd class="min-w-0 text-end tabular-nums font-medium text-zinc-900">{{ $appointment->formattedSessionStart() }}</dd>
        </div>
        <div class="flex justify-between gap-6">
            <dt class="shrink-0 font-semibold text-zinc-900">{{ __('doctor.card.session_duration') }}</dt>
            <dd class="min-w-0 text-end font-medium text-zinc-900">{{ __('doctor.dashboard.minutes_label', ['m' => $appointment->duration]) }}</dd>
        </div>
    </dl>

    <div class="mt-6 grid grid-cols-2 gap-3">
        <flux:button
            class="min-h-[2.75rem] !border-[#10B981] !text-[#10B981] hover:!bg-[#10B981]/10"
            size="sm"
            variant="outline"
            :href="route('doctor.appointments.medical-history', $appointment)"
            wire:navigate
        >
            {{ __('doctor.card.medical_history') }}
        </flux:button>
        <flux:button
            class="min-h-[2.75rem] !border-[#10B981] !text-[#10B981] hover:!bg-[#10B981]/10"
            size="sm"
            variant="outline"
            :href="route('doctor.appointments.diagnosis', $appointment)"
            wire:navigate
        >
            {{ __('doctor.card.diagnosis') }}
        </flux:button>
        <flux:button
            class="min-h-[2.75rem] !border-[#10B981] !text-[#10B981] hover:!bg-[#10B981]/10"
            size="sm"
            variant="outline"
            :href="route('doctor.appointments.prescription', $appointment)"
            wire:navigate
        >
            {{ __('doctor.card.prescription') }}
        </flux:button>
        @if ($appointment->status === 'in_process')
            <flux:button
                class="min-h-[2.75rem] border-0 bg-red-600 text-white hover:bg-red-700"
                size="sm"
                type="button"
                wire:click="requestCompleteAppointment({{ $appointment->id }})"
            >
                {{ __('doctor.card.mark_complete') }}
            </flux:button>
        @else
            <div aria-hidden="true"></div>
        @endif
    </div>

    @if ($statusSlug === 'new' || $statusSlug === 'in_process')
        <flux:button
            class="mt-4 min-h-[2.875rem] w-full !border-[0] !bg-[#10B981] !text-white hover:!brightness-[0.96]"
            size="sm"
            variant="primary"
            icon="chat-bubble-left-right"
            :href="route('doctor.appointments.conversation', $appointment)"
            wire:navigate
        >
            {{ __('doctor.card.start_conversation') }}
        </flux:button>
    @endif
</div>
