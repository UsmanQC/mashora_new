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
        @php
            $mfContainerId = $mfContainerId ?? 'mf-unified-session';
            $mfEmbedConfig = [
                'completeUrl' => $this->mfCompleteUrl,
                'sessionId' => $this->mfSessionId,
                'scriptUrl' => $this->mfSessionJsUrl,
                'successUrl' => route('patient.payment.success', ['temporaryAppointment' => $this->temporaryAppointment->id]),
                'csrfToken' => csrf_token(),
                'containerId' => $mfContainerId,
                'preferDesktop' => (bool) ($mfPreferDesktop ?? false),
                'lang' => app()->getLocale() === 'ar' ? 'ar' : 'en',
                'insertCard' => __('myfatoorah.insertCardDetails'),
                'placeholders' => [
                    'holderName' => __('myfatoorah.holderName'),
                    'cardNumber' => __('myfatoorah.cardNumber'),
                    'expiryDate' => __('myfatoorah.expiryDate'),
                    'securityCode' => __('myfatoorah.securityCode'),
                ],
            ];
        @endphp
        {{-- Config in JSON script avoids Alpine/HTML quote breakage; init() uses `this` (not $data). --}}
        <script type="application/json" id="mf-embed-config-{{ $mfContainerId }}">@json($mfEmbedConfig)</script>
        <div
            class="space-y-4"
            wire:ignore
            wire:key="mf-v3-{{ $this->mfSessionId }}-{{ $mfContainerId }}"
            data-test="patient-checkout-mf-embed"
            data-mf-container="{{ $mfContainerId }}"
            data-mf-config-id="mf-embed-config-{{ $mfContainerId }}"
            x-data="{
                booting: true,
                failed: false,
                failReason: '',
                softError: '',
                ready: false,
                paying: false,
                submitPay() {
                    if (this.paying || ! window.myfatoorah?.submitCardPayment) {
                        return;
                    }
                    this.paying = true;
                    this.softError = '';
                    try {
                        window.myfatoorah.submitCardPayment();
                    } catch (e) {
                        this.paying = false;
                        this.failed = true;
                        this.failReason = e?.message || 'submit failed';
                    }
                },
                init() {
                    const boot = () => {
                        if (typeof window.startMyFatoorahEmbed !== 'function') {
                            this.booting = false;
                            this.failed = true;
                            this.failReason = 'payment helper missing';
                            return;
                        }

                        const configEl = document.getElementById(this.$el.dataset.mfConfigId || '');
                        let config = {};
                        try {
                            config = JSON.parse(configEl?.textContent || '{}');
                        } catch (e) {
                            this.booting = false;
                            this.failed = true;
                            this.failReason = 'invalid payment config';
                            return;
                        }

                        window.startMyFatoorahEmbed(this, config);
                    };

                    if (typeof window.startMyFatoorahEmbed === 'function') {
                        boot();
                        return;
                    }

                    // Layout script may still be parsing on first paint.
                    let tries = 0;
                    const timer = setInterval(() => {
                        tries += 1;
                        if (typeof window.startMyFatoorahEmbed === 'function' || tries >= 40) {
                            clearInterval(timer);
                            boot();
                        }
                    }, 50);
                },
            }"
        >
            <div class="mf-embed-shell mx-auto w-full max-w-[400px] rounded-2xl bg-white px-1 sm:px-0">
                <div x-show="booting && !failed" x-cloak class="space-y-3 py-4">
                    <div class="h-10 animate-pulse rounded-xl bg-slate-100"></div>
                    <div class="h-10 animate-pulse rounded-xl bg-slate-100"></div>
                    <div class="h-24 animate-pulse rounded-xl bg-slate-100"></div>
                    <p class="text-center text-xs text-slate-400">{{ __('patient_booking.payment_form_loading') }}</p>
                </div>

                <div id="{{ $mfContainerId }}" class="mf-embed-root w-full bg-white"></div>
            </div>

            <div
                class="sticky bottom-[calc(4.75rem+env(safe-area-inset-bottom))] z-30 -mx-1 bg-gradient-to-t from-white via-white to-white/90 px-1 pt-3 pb-1 sm:static sm:bottom-auto sm:z-auto sm:mx-0 sm:bg-none sm:px-0 sm:pt-4 sm:pb-0"
                x-show="ready && !failed"
                x-cloak
            >
                <button
                    type="button"
                    class="flex min-h-12 w-full items-center justify-center rounded-2xl border border-[#10B981] bg-[#10B981] px-4 text-base font-semibold text-white shadow-[0_10px_28px_-8px_rgba(16,185,129,0.45)] hover:brightness-[0.97] disabled:cursor-not-allowed disabled:opacity-70"
                    data-test="patient-checkout-mf-pay-now"
                    x-bind:disabled="paying"
                    x-on:click="submitPay()"
                >
                    <span x-show="!paying">{{ __('myfatoorah.payNow') }}</span>
                    <span x-show="paying" x-cloak>{{ __('patient_booking.payment_processing') }}</span>
                </button>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900" x-cloak x-show="softError && !failed">
                <p x-text="softError"></p>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900" x-cloak x-show="failed">
                <p>{{ __('patient_booking.payment_embedded_unavailable') }}</p>
                <p class="mt-1 font-mono text-[0.65rem] text-amber-800/80" x-show="failReason" x-text="failReason"></p>
            </div>

            <button
                type="button"
                class="w-full py-2 text-center text-xs font-medium text-slate-400 underline-offset-2 hover:text-slate-600 hover:underline"
                wire:click="initMyFatoorahEmbeddedV3"
                wire:loading.attr="disabled"
                data-test="patient-checkout-mf-retry"
                x-show="failed"
                x-cloak
            >
                <span wire:loading.remove wire:target="initMyFatoorahEmbeddedV3">{{ __('patient_booking.payment_retry') }}</span>
                <span wire:loading wire:target="initMyFatoorahEmbeddedV3">{{ __('patient_booking.payment_processing') }}</span>
            </button>
        </div>
    @elseif ($this->usesMyFatoorah())
        <div class="space-y-4" data-test="patient-checkout-mf-boot">
            @if ($paymentError === '')
                <p class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ __('patient_booking.payment_embedded_unavailable') }}
                </p>
            @endif

            <flux:button
                type="button"
                variant="primary"
                class="min-h-12 w-full !rounded-2xl !border-[#10B981] !bg-[#10B981] !text-white shadow-[0_10px_28px_-8px_rgba(16,185,129,0.45)] hover:!brightness-[0.97]"
                wire:click="initMyFatoorahEmbeddedV3"
                wire:loading.attr="disabled"
                wire:target="initMyFatoorahEmbeddedV3"
                data-test="patient-checkout-mf-retry"
            >
                <span wire:loading.remove wire:target="initMyFatoorahEmbeddedV3">{{ __('patient_booking.payment_retry') }}</span>
                <span wire:loading wire:target="initMyFatoorahEmbeddedV3">{{ __('patient_booking.payment_processing') }}</span>
            </flux:button>
        </div>
    @else
        <div class="space-y-4" data-test="patient-checkout-redirect-pay">
            @if ($this->usesHyperPay())
                <flux:button
                    type="button"
                    variant="primary"
                    class="min-h-12 w-full !rounded-2xl !border-[#10B981] !bg-[#10B981] !text-white shadow-[0_10px_28px_-8px_rgba(16,185,129,0.45)] hover:!brightness-[0.97]"
                    wire:click="initHyperpayCheckout"
                    wire:loading.attr="disabled"
                    data-test="patient-checkout-pay-card"
                >
                    <span wire:loading.remove wire:target="initHyperpayCheckout">{{ __('patient_booking.payment_retry') }}</span>
                    <span wire:loading wire:target="initHyperpayCheckout">{{ __('patient_booking.payment_processing') }}</span>
                </flux:button>
            @endif

            <p class="text-center text-[0.7rem] leading-relaxed text-slate-400">
                @if ($this->usesHyperPay())
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
