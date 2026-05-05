<x-layouts::patient>
    @php
        /** @var \App\Models\Appointment|null $appointment */
        $appointment = $appointment ?? null;
    @endphp
    <div class="mx-auto max-w-lg px-4 py-12 text-center">
        <flux:icon name="check-circle" variant="solid" class="mx-auto size-14 text-emerald-600" />
        <h1 class="mt-4 text-xl font-semibold text-zinc-900">{{ __('patient_booking.payment_success_title') }}</h1>
        <p class="mt-2 text-zinc-600">{{ __('patient_booking.payment_success_body') }}</p>
        @if ($appointment !== null && filled($appointment->appointment_number))
            <p class="mt-4 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-800">
                <span class="font-semibold">{{ __('patient_booking.appointment_number_label') }}</span>
                {{ $appointment->appointment_number }}
            </p>
        @endif
        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
            <flux:button :href="route('patient.appointments')" variant="outline" wire:navigate>
                {{ __('patient_booking.view_appointments') }}
            </flux:button>
            <flux:button :href="route('patient.home')" variant="primary" class="border-[#0B163E] !bg-[#0B163E] !text-white hover:!brightness-[0.97]" wire:navigate>
                {{ __('patient_booking.back_home') }}
            </flux:button>
        </div>
    </div>
</x-layouts::patient>
