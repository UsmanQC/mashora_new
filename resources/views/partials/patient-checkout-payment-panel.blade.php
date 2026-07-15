@php
    $checkoutTotal = (float) $this->temporaryAppointment->total;
    $checkoutDue = $this->amountDue();
@endphp

@if ($paymentError !== '')
    <div class="rounded-2xl border border-red-200/90 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        {{ $paymentError }}
    </div>
@endif

@if ($this->walletApplied() > 0 && $checkoutDue <= 0)
    <p class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ __('patient_booking.wallet_covers_full') }}
    </p>
    <flux:button
        type="button"
        variant="primary"
        class="min-h-12 w-full !rounded-2xl !border-[#10B981] !bg-[#10B981] !text-white shadow-[0_10px_28px_-8px_rgba(16,185,129,0.45)] hover:!brightness-[0.97]"
        wire:click="payWithWalletOnly"
        wire:loading.attr="disabled"
        data-test="patient-checkout-pay-wallet"
    >
        <span wire:loading.remove wire:target="payWithWalletOnly">{{ __('patient_booking.pay_with_wallet_only') }}</span>
        <span wire:loading wire:target="payWithWalletOnly">{{ __('patient_booking.payment_processing') }}</span>
    </flux:button>
@elseif ($this->paymentGatewayConfigured())
    @if ($this->walletApplied() > 0 && $checkoutDue > 0)
        <p class="rounded-2xl border border-sky-200/80 bg-sky-50/90 px-4 py-3 text-sm text-sky-900">
            {{ __('patient_booking.wallet_partial_hint') }}
        </p>
    @endif

    @if ($this->usesStripe())
        <flux:button
            type="button"
            variant="primary"
            class="min-h-12 w-full !rounded-2xl !border-[#10B981] !bg-[#10B981] !text-white shadow-[0_10px_28px_-8px_rgba(16,185,129,0.45)] hover:!brightness-[0.97]"
            wire:click="startStripePayment"
            wire:loading.attr="disabled"
            data-test="patient-checkout-pay-stripe"
        >
            <span wire:loading.remove wire:target="startStripePayment">
                @if ($this->walletApplied() > 0)
                    {{ __('patient_booking.pay_wallet_and_card', [
                        'wallet' => number_format($this->walletApplied(), 2),
                        'due' => number_format($checkoutDue, 2),
                    ]) }}
                @else
                    {{ __('patient_booking.pay_now_stripe') }}
                @endif
            </span>
            <span wire:loading wire:target="startStripePayment">{{ __('patient_booking.payment_processing') }}</span>
        </flux:button>

        <flux:text class="text-center text-xs text-slate-500">{{ __('patient_booking.payment_stripe_note') }}</flux:text>
    @elseif ($this->usesHyperPay() && $embeddedReady)
        @include('partials.hyperpay-widget', [
            'callbackUrl' => $hyperpayCallbackUrl,
            'checkoutId' => $hyperpayCheckoutId,
            'integrity' => $hyperpayIntegrity,
            'env' => $hyperpayEnv,
            'amountDue' => $checkoutDue,
        ])

        <flux:button
            type="button"
            variant="ghost"
            class="w-full !text-slate-500"
            wire:click="initHyperpayCheckout"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="initHyperpayCheckout">{{ __('patient_booking.payment_retry') }}</span>
            <span wire:loading wire:target="initHyperpayCheckout">{{ __('patient_booking.payment_processing') }}</span>
        </flux:button>

        <p class="text-center text-xs leading-relaxed text-slate-500">{{ __('patient_booking.payment_hyperpay_note') }}</p>
    @elseif ($this->usesMyFatoorah() && $embeddedReady)
        <div
            class="space-y-4"
            wire:ignore
            wire:key="mf-v3-{{ $this->mfSessionId }}"
            data-test="patient-checkout-mf-embed"
        >
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('patient_booking.payment_card_details') }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('patient_booking.payment_embedded_v3_hint') }}</p>
                </div>
                <span class="hidden shrink-0 items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-wide text-emerald-700 ring-1 ring-emerald-100 sm:inline-flex">
                    <flux:icon name="shield-check" variant="mini" class="size-3.5" />
                    {{ __('patient_booking.ssl_badge') }}
                </span>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-3 sm:p-4">
                <div id="payment-sessions" class="min-h-[18rem] w-full"></div>
            </div>

            <p id="mf-card-error" class="hidden rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                {{ __('patient_booking.payment_embedded_unavailable') }}
            </p>

            <p class="text-center text-[0.7rem] leading-relaxed text-slate-400">
                {{ __('patient_booking.payment_hyperpay_note') }}
            </p>
        </div>
    @else
        <div class="space-y-4" data-test="patient-checkout-redirect-pay">
            @if ($this->usesMyFatoorah())
                <flux:button
                    type="button"
                    variant="primary"
                    class="min-h-12 w-full !rounded-2xl !border-[#10B981] !bg-[#10B981] !text-white shadow-[0_10px_28px_-8px_rgba(16,185,129,0.45)] hover:!brightness-[0.97]"
                    wire:click="initMyFatoorahEmbeddedV3"
                    wire:loading.attr="disabled"
                    data-test="patient-checkout-mf-retry"
                >
                    <span wire:loading.remove wire:target="initMyFatoorahEmbeddedV3">{{ __('patient_booking.payment_retry') }}</span>
                    <span wire:loading wire:target="initMyFatoorahEmbeddedV3">{{ __('patient_booking.payment_processing') }}</span>
                </flux:button>

                <flux:button
                    type="button"
                    variant="ghost"
                    class="min-h-11 w-full !rounded-2xl !border !border-slate-200 !bg-slate-50 !font-semibold !text-slate-700"
                    wire:click="startCardPayment"
                    wire:loading.attr="disabled"
                    data-test="patient-checkout-pay-card"
                >
                    <span wire:loading.remove wire:target="startCardPayment">{{ __('patient_booking.pay_now_fallback') }}</span>
                    <span wire:loading wire:target="startCardPayment">{{ __('patient_booking.payment_processing') }}</span>
                </flux:button>
            @else
                <flux:button
                    type="button"
                    variant="primary"
                    class="min-h-12 w-full !rounded-2xl !border-[#10B981] !bg-[#10B981] !text-white shadow-[0_10px_28px_-8px_rgba(16,185,129,0.45)] hover:!brightness-[0.97]"
                    wire:click="startCardPayment"
                    wire:loading.attr="disabled"
                    data-test="patient-checkout-pay-card"
                >
                    <span wire:loading.remove wire:target="startCardPayment">{{ __('patient_booking.pay_now') }}</span>
                    <span wire:loading wire:target="startCardPayment">{{ __('patient_booking.payment_processing') }}</span>
                </flux:button>
            @endif

            <p class="text-center text-[0.7rem] leading-relaxed text-slate-400">
                @if ($this->usesMyFatoorah())
                    {{ __('patient_booking.payment_secure_note') }}
                @elseif ($this->usesHyperPay())
                    {{ __('patient_booking.payment_hyperpay_note') }}
                @endif
            </p>
        </div>
    @endif
@else
    <p class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        @if ($this->usesStripe())
            {{ __('patient_booking.payment_stripe_missing') }}
        @elseif ($this->usesHyperPay())
            {{ __('patient_booking.payment_hyperpay_missing') }}
        @else
            {{ __('patient_booking.payment_api_missing') }}
        @endif
    </p>
@endif
