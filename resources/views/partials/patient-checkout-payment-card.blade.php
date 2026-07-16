@php
    $checkoutTotal = (float) $this->temporaryAppointment->total;
    $checkoutDue = $this->amountDue();
    $showAmountDue = $this->walletApplied() > 0 && $checkoutDue < $checkoutTotal;
    $displayAmount = $showAmountDue ? $checkoutDue : $checkoutTotal;
    $showBackHome = $showBackHome ?? false;
@endphp

<section
    class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-[0_12px_40px_-16px_rgba(15,23,42,0.18)]"
    data-test="patient-checkout-payment-card"
>
    <div class="relative overflow-hidden border-b border-slate-100">
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_rgba(16,185,129,0.12),_transparent_55%),linear-gradient(180deg,#f8fafc_0%,#ffffff_100%)]"
            aria-hidden="true"
        ></div>

        <div class="relative flex flex-col gap-3 px-3 py-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4 sm:px-6 sm:py-5">
            <div class="flex min-w-0 items-start gap-2.5 sm:gap-3">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-2xl bg-[#10B981] text-white shadow-[0_8px_20px_-6px_rgba(16,185,129,0.55)] sm:size-11">
                    <flux:icon name="lock-closed" variant="mini" class="size-4 sm:size-5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-emerald-700/80">
                        {{ __('patient_booking.secure_payment') }}
                    </p>
                    <h2 class="mt-0.5 text-sm font-bold tracking-tight text-slate-900 sm:text-base">
                        {{ __('patient_booking.checkout_accepts') }}
                    </h2>
                    <p class="mt-0.5 hidden text-xs leading-relaxed text-slate-500 sm:mt-1 sm:block">
                        {{ __('patient_booking.luxury.trust_badge') }}
                    </p>
                </div>
            </div>

            <div class="flex w-full shrink-0 items-center justify-between rounded-2xl border border-emerald-100 bg-white/90 px-3 py-2 shadow-sm backdrop-blur-sm sm:w-auto sm:flex-col sm:items-end sm:justify-center sm:px-3.5 sm:py-2.5 sm:text-end">
                <p class="text-[0.6rem] font-semibold uppercase tracking-[0.12em] text-slate-400">
                    {{ $showAmountDue ? __('patient_booking.amount_due') : __('patient_booking.total') }}
                </p>
                <p class="text-lg font-bold tabular-nums tracking-tight text-[#059669] sm:mt-0.5 sm:text-2xl">
                    {{ number_format($displayAmount, 2) }}
                    <span class="text-sm font-semibold">{{ __('patient_booking.sar') }}</span>
                </p>
            </div>
        </div>
    </div>

    <div class="space-y-3 px-3 py-4 sm:space-y-4 sm:px-6 sm:py-5">
        @include('partials.patient-checkout-payment-panel', [
            'mfContainerId' => $mfContainerId ?? 'mf-unified-session',
            'mfPreferDesktop' => $mfPreferDesktop ?? false,
        ])

        {{-- Accepted cards strip (mada / Visa / Mastercard / Apple Pay)
        @include('partials.patient-checkout-payment-methods', ['compact' => true, 'labelSurface' => 'bg-white'])
        --}}
    </div>

    @if ($showBackHome)
        <div class="border-t border-slate-100 bg-slate-50/60 px-5 py-3.5 sm:px-6">
            <flux:button :href="route('patient.home')" wire:navigate variant="ghost" class="w-full !text-slate-500 hover:!text-slate-800">
                {{ __('patient_booking.back_home') }}
            </flux:button>
        </div>
    @endif
</section>
