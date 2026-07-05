@php
    $checkoutTotal = (float) $this->temporaryAppointment->total;
    $checkoutDue = $this->amountDue();
@endphp

@if ($paymentError !== '')
    <p class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $paymentError }}</p>
@endif

@if ($this->walletApplied() > 0 && $checkoutDue <= 0)
    <p class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ __('patient_booking.wallet_covers_full') }}
    </p>
    <flux:button
        type="button"
        variant="primary"
        class="min-h-12 w-full !rounded-2xl !border-[#10B981] !bg-[#10B981] !text-white shadow-[0_8px_25px_-5px_rgba(16,185,129,0.3)] hover:!brightness-[0.97]"
        wire:click="payWithWalletOnly"
        wire:loading.attr="disabled"
        data-test="patient-checkout-pay-wallet"
    >
        <span wire:loading.remove wire:target="payWithWalletOnly">{{ __('patient_booking.pay_with_wallet_only') }}</span>
        <span wire:loading wire:target="payWithWalletOnly">{{ __('patient_booking.payment_processing') }}</span>
    </flux:button>
@elseif ($this->paymentGatewayConfigured())
    @if ($this->walletApplied() > 0 && $checkoutDue > 0)
        <p class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
            {{ __('patient_booking.wallet_partial_hint') }}
        </p>
    @endif

    @if ($this->usesStripe())
        <flux:button
            type="button"
            variant="primary"
            class="min-h-12 w-full !rounded-2xl !border-[#10B981] !bg-[#10B981] !text-white shadow-[0_8px_25px_-5px_rgba(16,185,129,0.3)] hover:!brightness-[0.97]"
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
        ])

        <flux:button
            type="button"
            variant="ghost"
            class="w-full"
            wire:click="initHyperpayCheckout"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="initHyperpayCheckout">{{ __('patient_booking.payment_retry') }}</span>
            <span wire:loading wire:target="initHyperpayCheckout">{{ __('patient_booking.payment_processing') }}</span>
        </flux:button>
    @elseif ($this->usesMyFatoorah() && $embeddedReady)
        @include('partials.payment-card-field-guide')

        <div id="mf-form-element" class="min-h-[11rem] w-full overflow-visible rounded-xl border border-slate-200 bg-white p-3 sm:min-h-[13rem]"></div>
        <p id="mf-card-error" class="hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
            {{ __('patient_booking.payment_embedded_unavailable') }}
        </p>

        <button
            type="button"
            id="embedded-pay-now"
            class="min-h-12 w-full rounded-2xl border border-[#10B981] bg-[#10B981] py-3.5 text-sm font-bold text-white shadow-[0_8px_25px_-5px_rgba(16,185,129,0.3)] transition hover:brightness-[0.97]"
            data-test="patient-checkout-pay-embedded"
        >
            {{ __('patient_booking.pay_now') }}
        </button>

        <flux:button
            type="button"
            variant="ghost"
            class="w-full"
            wire:click="startCardPayment"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="startCardPayment">{{ __('patient_booking.pay_now_fallback') }}</span>
            <span wire:loading wire:target="startCardPayment">{{ __('patient_booking.payment_processing') }}</span>
        </flux:button>
    @else
        <flux:button
            type="button"
            variant="primary"
            class="min-h-12 w-full !rounded-2xl !border-[#10B981] !bg-[#10B981] !text-white shadow-[0_8px_25px_-5px_rgba(16,185,129,0.3)] hover:!brightness-[0.97]"
            wire:click="startCardPayment"
            wire:loading.attr="disabled"
            data-test="patient-checkout-pay-card"
        >
            <span wire:loading.remove wire:target="startCardPayment">{{ __('patient_booking.pay_now') }}</span>
            <span wire:loading wire:target="startCardPayment">{{ __('patient_booking.payment_processing') }}</span>
        </flux:button>
    @endif

    @if ($this->usesMyFatoorah())
        <flux:text class="text-center text-xs text-slate-500">{{ __('patient_booking.payment_secure_note') }}</flux:text>
    @elseif ($this->usesHyperPay())
        <flux:text class="text-center text-xs text-slate-500">{{ __('patient_booking.payment_hyperpay_note') }}</flux:text>
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
