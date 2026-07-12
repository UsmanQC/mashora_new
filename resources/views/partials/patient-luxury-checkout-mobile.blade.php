@php
    $checkoutTotal = (float) $this->temporaryAppointment->total;
    $checkoutDue = $this->amountDue();
    $showAmountDue = $this->walletApplied() > 0 && $checkoutDue < $checkoutTotal;
@endphp

<main class="space-y-6 px-4 py-6" data-test="patient-checkout-step-payment">
    @if (session('flash_payment'))
        <p class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ session('flash_payment') }}</p>
    @endif

    <div class="flex items-center gap-4 rounded-2xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
        @if ($this->doctorPhotoUrl() !== null)
            <img src="{{ $this->doctorPhotoUrl() }}" alt="" class="size-14 shrink-0 rounded-full object-cover" />
        @else
            <flux:avatar :name="$this->specialistName()" circle class="size-14 shrink-0" />
        @endif
        <div class="min-w-0">
            <h2 class="truncate text-sm font-bold text-slate-900">{{ $this->specialistName() }}</h2>
            <p class="mt-0.5 truncate text-xs text-slate-500">{{ $this->doctorSpecialtyLabel() }}</p>
        </div>
    </div>

    <div class="relative overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-sm">
        <div class="absolute inset-x-0 top-0 h-1 bg-[#10B981]" aria-hidden="true"></div>

        <div class="space-y-5 p-6">
            <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-slate-500">{{ __('patient_booking.session_date') }}</span>
                    <span class="font-bold text-slate-900">{{ $this->formattedLuxuryDate() }}</span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-slate-500">{{ __('patient_booking.session_time') }}</span>
                    <span class="font-bold text-slate-900">{{ $this->formattedTime() }}</span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-slate-500">{{ __('patient_booking.session_duration') }}</span>
                    <span class="font-bold text-slate-900">{{ __('patient_booking.luxury.duration_minutes', ['minutes' => $temporaryAppointment->duration]) }}</span>
                </div>
            </div>

            <div class="patient-luxury-booking-receipt-divider w-full" aria-hidden="true"></div>

            <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-slate-500">{{ __('patient_booking.session_price') }}</span>
                    <span class="font-semibold tabular-nums text-slate-900">{{ number_format((float) $temporaryAppointment->amount, 0) }} {{ __('patient_booking.sar') }}</span>
                </div>

                @if ((float) $temporaryAppointment->discount > 0)
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">{{ __('patient_booking.discount') }}</span>
                        <span class="font-semibold tabular-nums text-slate-900">−{{ number_format((float) $temporaryAppointment->discount, 0) }} {{ __('patient_booking.sar') }}</span>
                    </div>
                @endif

                @if ($this->walletApplied() > 0)
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">{{ __('patient_booking.wallet_applied') }}</span>
                        <span class="font-semibold tabular-nums text-emerald-600">−{{ number_format($this->walletApplied(), 0) }} {{ __('patient_booking.sar') }}</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="flex items-end justify-between gap-3 border-t border-slate-100 bg-slate-50 p-6">
            <div>
                <span class="mb-1 block text-xs font-semibold text-slate-500">
                    {{ $showAmountDue ? __('patient_booking.amount_due') : __('patient_booking.luxury.total_due') }}
                </span>
                <span class="text-xs text-slate-400">{{ __('patient_booking.luxury.vat_included') }}</span>
            </div>
            <div class="flex items-baseline gap-1 text-[#059669]">
                <span class="text-2xl font-bold tabular-nums">{{ number_format($showAmountDue ? $checkoutDue : $checkoutTotal, 0) }}</span>
                <span class="text-sm font-bold">{{ __('patient_booking.sar') }}</span>
            </div>
        </div>
    </div>

    @if ($this->walletBalance() > 0)
        <section class="rounded-2xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
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

    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-[0_8px_30px_-12px_rgba(15,23,42,0.12)]" data-test="patient-checkout-payment-card">
        <div class="border-b border-slate-100 bg-gradient-to-b from-slate-50/90 to-white px-5 py-4">
            <div class="flex items-start gap-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#10B981]/10 text-[#10B981] ring-1 ring-[#10B981]/15">
                    <flux:icon name="lock-closed" variant="mini" class="size-4" />
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-bold text-slate-900">{{ __('patient_booking.checkout_accepts') }}</h2>
                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500">{{ __('patient_booking.luxury.trust_badge') }}</p>
                </div>
            </div>
        </div>

        <div class="space-y-4 p-5">
            @include('partials.patient-checkout-payment-panel')

            <div class="border-t border-slate-100 pt-4">
                @include('partials.patient-checkout-payment-methods', ['compact' => true, 'labelSurface' => 'bg-white'])
            </div>
        </div>
    </section>
</main>
