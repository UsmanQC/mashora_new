@php
    $checkoutTotal = (float) $this->temporaryAppointment->total;
    $checkoutDue = $this->amountDue();
    $showAmountDue = $this->walletApplied() > 0 && $checkoutDue < $checkoutTotal;
    $summaryAmount = $showAmountDue ? $checkoutDue : $checkoutTotal;
@endphp

<main class="space-y-4 px-3 py-4" data-test="patient-checkout-step-payment">
    @if (session('flash_payment'))
        <p class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ session('flash_payment') }}</p>
    @endif

    {{-- Compact booking reminder — full receipt lives on earlier steps / desktop. --}}
    <section
        class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-white px-3.5 py-3 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]"
        data-test="patient-checkout-mobile-summary"
    >
        @if ($this->doctorPhotoUrl() !== null)
            <img src="{{ $this->doctorPhotoUrl() }}" alt="" class="size-11 shrink-0 rounded-full object-cover ring-2 ring-[#10B981]/15" />
        @else
            <flux:avatar :name="$this->specialistName()" circle class="size-11 shrink-0 ring-2 ring-[#10B981]/15" />
        @endif

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-bold text-slate-900">{{ $this->specialistName() }}</p>
            <p class="mt-0.5 truncate text-xs text-slate-500">
                {{ $this->formattedLuxuryDate() }}
                <span class="text-slate-300" aria-hidden="true">·</span>
                {{ $this->formattedTime() }}
                <span class="text-slate-300" aria-hidden="true">·</span>
                {{ __('patient_booking.luxury.duration_minutes', ['minutes' => $temporaryAppointment->duration]) }}
            </p>
        </div>

        <div class="shrink-0 text-end">
            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.12em] text-slate-400">
                {{ $showAmountDue ? __('patient_booking.amount_due') : __('patient_booking.total') }}
            </p>
            <p class="text-base font-bold tabular-nums text-[#059669]">
                {{ number_format($summaryAmount, 2) }}
                <span class="text-xs font-semibold">{{ __('patient_booking.sar') }}</span>
            </p>
        </div>
    </section>

    @if ($this->walletBalance() > 0)
        <section class="rounded-2xl border border-slate-100 bg-white p-3.5 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 text-sm">
                    <span class="font-semibold text-slate-800">{{ __('patient_booking.use_wallet') }}</span>
                    <span class="mt-1 block text-xs text-slate-500">
                        {{ __('patient_booking.wallet_balance') }}:
                        {{ number_format($this->walletBalance(), 2) }} {{ __('patient_booking.sar') }}
                    </span>
                    <flux:link :href="route('patient.wallet')" wire:navigate class="mt-1 inline-block text-xs font-medium text-[#10B981]">
                        {{ __('patient_booking.view_wallet') }}
                    </flux:link>
                </div>
                <flux:switch wire:model.live="useWallet" />
            </div>
        </section>
    @endif

    @include('partials.patient-checkout-payment-card', [
        'showBackHome' => false,
        'mfContainerId' => 'mf-unified-mobile',
        'mfPreferDesktop' => false,
    ])
</main>
