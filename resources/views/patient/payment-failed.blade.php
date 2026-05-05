<x-layouts::patient>
    <div class="mx-auto max-w-lg px-4 py-12 text-center">
        <flux:icon name="x-circle" variant="solid" class="mx-auto size-14 text-red-600" />
        <h1 class="mt-4 text-xl font-semibold text-zinc-900">{{ __('patient_booking.payment_failed_title') }}</h1>
        <p class="mt-2 text-zinc-600">{{ __('patient_booking.payment_failed_body') }}</p>
        <flux:button :href="route('patient.schedule.specialists')" variant="outline" class="mt-8" wire:navigate>
            {{ __('patient_booking.back_specialists') }}
        </flux:button>
    </div>
</x-layouts::patient>
