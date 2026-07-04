@php
    /** @var \App\Models\Appointment $appointment */
@endphp

<div class="mt-3 ps-2" data-test="patient-missed-resolution">
    <p class="mb-2.5 text-xs leading-relaxed text-orange-900">{{ __('patient.missed.prompt') }}</p>
    <div class="flex flex-col gap-2">
        <a
            href="{{ route('patient.appointments.missed-reschedule', $appointment) }}"
            wire:navigate
            class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl border border-[#10B981]/30 bg-white px-4 py-2.5 text-xs font-bold text-[#047857] shadow-sm transition hover:bg-[#10B981]/5"
        >
            <flux:icon name="calendar-days" variant="mini" class="size-4" />
            {{ __('patient.missed.reschedule') }}
        </a>
        <button
            type="button"
            wire:click="promptRefundMissed({{ $appointment->id }})"
            class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl bg-[#10B981] px-4 py-2.5 text-xs font-bold text-white shadow-sm shadow-emerald-900/15 transition hover:bg-[#059669]"
        >
            <flux:icon name="banknotes" variant="mini" class="size-4" />
            {{ __('patient.missed.refund') }}
        </button>
    </div>
</div>
