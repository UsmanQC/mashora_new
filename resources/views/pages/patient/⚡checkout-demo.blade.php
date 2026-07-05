<?php

use Flux\Flux;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient')] #[Title('Checkout demo')] class extends Component
{
    private string $demoAppointmentDate = '2026-06-15';

    private string $demoStartTime = '14:30:00';

    private string $demoSpecialistName = 'Dr. Demo Specialist';

    public function mount(): void
    {
        abort_unless(config('patient.demo_checkout_enabled'), 404);
    }

    public function formattedDate(): string
    {
        try {
            return Carbon::parse($this->demoAppointmentDate)
                ->locale(app()->getLocale())
                ->format(__('patient_booking.date_format'));
        } catch (\Throwable) {
            return $this->demoAppointmentDate;
        }
    }

    public function formattedTime(): string
    {
        try {
            return Carbon::createFromFormat('H:i:s', $this->demoStartTime)
                ->timezone(config('app.timezone'))
                ->locale(app()->getLocale())
                ->translatedFormat('g:i a');
        } catch (\Throwable) {
            return $this->demoStartTime;
        }
    }

    public function specialistName(): string
    {
        return $this->demoSpecialistName;
    }

    public function demoDurationMinutes(): int
    {
        return 45;
    }

    public function demoAmount(): float
    {
        return 250.0;
    }

    public function demoDiscount(): float
    {
        return 25.0;
    }

    public function demoTotal(): float
    {
        return 225.0;
    }

    public function demoPayClick(): void
    {
        Flux::toast(variant: 'success', text: __('patient_booking.checkout_demo_pay_toast'));
    }
}; ?>

<div class="mx-auto max-w-4xl space-y-8 px-4 py-6 pb-28 sm:pb-12">
    <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
        <span class="me-2 inline-flex shrink-0 rounded-md bg-amber-200/80 px-2 py-0.5 text-xs font-semibold uppercase tracking-wide">{{ __('patient_booking.checkout_demo_badge') }}</span>
        {{ __('patient_booking.checkout_demo_banner') }}
    </p>

    <header class="space-y-2">
        <div class="flex flex-wrap items-center gap-2">
            <flux:heading size="xl" class="font-semibold text-zinc-900">
                {{ __('patient_booking.checkout_title') }}
            </flux:heading>
            <span class="rounded-full bg-zinc-200 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-zinc-700">{{ __('patient_booking.checkout_demo_badge') }}</span>
        </div>
        <flux:text class="text-zinc-600">{{ __('patient_booking.checkout_subtitle') }}</flux:text>
    </header>

    <div class="grid gap-8 lg:grid-cols-2">
        <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-md shadow-black/10">
            <div class="flex justify-between gap-3 border-b border-zinc-100 pb-3">
                <span class="text-sm font-semibold text-zinc-600">{{ __('patient_booking.specialist_name') }}</span>
                <span class="min-w-0 text-end text-sm font-semibold text-zinc-900">{{ $this->specialistName() }}</span>
            </div>
            <div class="mt-3 flex justify-between gap-3 text-sm">
                <span class="text-zinc-600">{{ __('patient_booking.session_date') }}</span>
                <span class="font-medium text-zinc-900">{{ $this->formattedDate() }}</span>
            </div>
            <div class="mt-2 flex justify-between gap-3 text-sm">
                <span class="text-zinc-600">{{ __('patient_booking.session_time') }}</span>
                <span class="font-medium text-zinc-900">{{ $this->formattedTime() }}</span>
            </div>
            <div class="mt-2 flex justify-between gap-3 text-sm">
                <span class="text-zinc-600">{{ __('patient_booking.session_duration') }}</span>
                <span class="font-medium text-zinc-900">{{ $this->demoDurationMinutes() }}</span>
            </div>
            <hr class="my-4 border-zinc-100" />
            <div class="flex justify-between gap-3 text-sm">
                <span class="text-zinc-600">{{ __('patient_booking.session_price') }}</span>
                <span class="font-medium tabular-nums text-zinc-900">{{ number_format($this->demoAmount(), 2) }} {{ __('patient_booking.sar') }}</span>
            </div>
            <div class="mt-2 flex justify-between gap-3 text-sm">
                <span class="text-zinc-600">{{ __('patient_booking.discount') }}</span>
                <span class="font-medium tabular-nums text-zinc-900">{{ number_format($this->demoDiscount(), 2) }} {{ __('patient_booking.sar') }}</span>
            </div>
            <div class="mt-3 flex justify-between gap-3 border-t border-zinc-100 pt-3 text-sm font-semibold">
                <span class="text-zinc-800">{{ __('patient_booking.total') }}</span>
                <span class="tabular-nums text-zinc-900">{{ number_format($this->demoTotal(), 2) }} {{ __('patient_booking.sar') }}</span>
            </div>
        </div>

        <div class="space-y-4">
            <flux:text class="text-sm text-zinc-600">{{ __('patient_booking.checkout_demo_pay_hint') }}</flux:text>
            <flux:button
                type="button"
                variant="primary"
                class="w-full border-[#064e3b] !bg-[#064e3b] !text-white hover:!brightness-[0.97] sm:w-auto"
                wire:click="demoPayClick"
            >
                {{ __('patient_booking.pay_now') }}
            </flux:button>

            <div class="pt-2">
                <flux:link :href="route('patient.home')" wire:navigate>{{ __('patient_booking.back_home') }}</flux:link>
            </div>

            <div class="mt-4 border-t border-zinc-100 pt-4">
                @include('partials.patient-checkout-payment-methods', ['compact' => true])
            </div>
        </div>
    </div>
</div>
