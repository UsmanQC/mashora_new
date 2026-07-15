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
        {{-- Embedded Payment v3 only (no hosted MyFatoorah redirect). --}}
        <div
            class="space-y-4"
            wire:ignore
            wire:key="mf-v3-{{ $this->mfSessionId }}"
            data-test="patient-checkout-mf-embed"
            x-data="{ booting: true, failed: false }"
            x-init="
                (() => {
                    const completeUrl = @js($this->mfCompleteUrl);
                    const sessionId = @js($this->mfSessionId);
                    const scriptUrl = @js($this->mfSessionJsUrl);
                    const csrf = @js(csrf_token());
                    const lang = @js(app()->getLocale() === 'ar' ? 'ar' : 'en');
                    const payNow = @js(__('myfatoorah.payNow'));
                    const insertCard = @js(__('myfatoorah.insertCardDetails'));

                    const fail = () => {
                        booting = false;
                        failed = true;
                    };

                    const payment = (response) => {
                        if (!response || !response.isSuccess) {
                            fail();
                            return;
                        }

                        // Embedded card / wallets with shouldHandlePaymentUrl: true
                        if (response.paymentCompleted && response.paymentData) {
                            fetch(completeUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({
                                    paymentData: response.paymentData,
                                    sessionId: response.sessionId || sessionId,
                                }),
                            })
                                .then((res) => res.json().then((data) => ({ ok: res.ok, data })))
                                .then(({ data }) => {
                                    if (data?.redirect) {
                                        window.location.href = data.redirect;
                                        return;
                                    }
                                    fail();
                                })
                                .catch(fail);
                            return;
                        }

                        // Do not open MyFatoorah hosted pages — stay embedded only.
                        fail();
                    };

                    const eventHandler = (event) => {
                        if (event?.name === 'VIEW_READY') {
                            booting = false;
                        }
                    };

                    const start = () => {
                        if (!window.myfatoorah) {
                            fail();
                            return;
                        }

                        try {
                            window.myfatoorah.init({
                                sessionId: sessionId,
                                callback: payment,
                                containerId: 'unified-session',
                                shouldHandlePaymentUrl: true,
                                eventListener: eventHandler,
                                subscribedEvents: [
                                    'VIEW_READY',
                                    'CARD_IDENTIFIED',
                                    'PAYMENT_STARTED',
                                    'PAYMENT_COMPLETED',
                                    'SESSION_STARTED',
                                    'SESSION_CANCELED',
                                    '3DS_CHALLENGE_INITIATED',
                                    'OTP_REQUESTED',
                                ],
                                settings: {
                                    loader: {
                                        display: 'none',
                                    },
                                    card: {
                                        language: lang,
                                        style: {
                                            showCardholderName: true,
                                            hideCardIcons: false,
                                            cardHeight: '220px',
                                            tokenHeight: '220px',
                                            input: {
                                                color: 'black',
                                                fontSize: '14px',
                                                fontFamily: 'sans-serif',
                                                inputHeight: '40px',
                                                inputMargin: '0px',
                                                borderColor: '#d1d5db',
                                                borderWidth: '1px',
                                                borderRadius: '8px',
                                                placeHolder: {
                                                    holderName: @js(__('myfatoorah.holderName')),
                                                    cardNumber: @js(__('myfatoorah.cardNumber')),
                                                    expiryDate: @js(__('myfatoorah.expiryDate')),
                                                    securityCode: @js(__('myfatoorah.securityCode')),
                                                },
                                            },
                                            label: {
                                                display: false,
                                            },
                                            error: {
                                                borderColor: 'red',
                                                borderRadius: '8px',
                                            },
                                            button: {
                                                useCustomButton: false,
                                                textContent: payNow,
                                                fontSize: '16px',
                                                fontFamily: 'sans-serif',
                                                color: 'white',
                                                backgroundColor: 'black',
                                                height: '44px',
                                                borderRadius: '8px',
                                                width: '100%',
                                                margin: '12px auto 0 auto',
                                                cursor: 'pointer',
                                            },
                                            separator: {
                                                useCustomSeparator: false,
                                                textContent: insertCard,
                                                fontSize: '14px',
                                                color: '#6b7280',
                                                fontFamily: 'sans-serif',
                                                textSpacing: '8px',
                                                lineStyle: 'solid',
                                                lineColor: '#e5e7eb',
                                                lineThickness: '1px',
                                            },
                                        },
                                    },
                                    applePay: {
                                        language: lang,
                                        style: {
                                            frameHeight: '44px',
                                            frameWidth: '100%',
                                            button: {
                                                height: '44px',
                                                type: 'pay',
                                                borderRadius: '8px',
                                            },
                                        },
                                    },
                                    googlePay: {
                                        language: lang,
                                        style: {
                                            frameHeight: '44px',
                                            frameWidth: '100%',
                                            button: {
                                                height: '44px',
                                                type: 'pay',
                                                borderRadius: '8px',
                                                color: 'black',
                                            },
                                        },
                                    },
                                    stcPay: {
                                        language: lang,
                                        style: {
                                            frameHeight: '44px',
                                            frameWidth: '100%',
                                            button: {
                                                borderRadius: '8px',
                                                height: '44px',
                                            },
                                        },
                                    },
                                },
                            });
                        } catch (e) {
                            fail();
                        }

                        setTimeout(() => {
                            if (booting) {
                                fail();
                            }
                        }, 15000);
                    };

                    if (!sessionId || !scriptUrl) {
                        fail();
                        return;
                    }

                    if (window.myfatoorah) {
                        start();
                        return;
                    }

                    const existing = document.querySelector('script[data-mf-v3-session]');
                    if (existing) {
                        existing.addEventListener('load', start, { once: true });
                        existing.addEventListener('error', fail, { once: true });
                        return;
                    }

                    const script = document.createElement('script');
                    script.src = scriptUrl;
                    script.async = true;
                    script.dataset.mfV3Session = '1';
                    script.addEventListener('load', start, { once: true });
                    script.addEventListener('error', fail, { once: true });
                    document.head.appendChild(script);
                })()
            "
        >
            <div class="mx-auto w-full max-w-[400px] bg-white">
                <div x-show="booting && !failed" x-cloak class="space-y-3 py-6">
                    <div class="h-10 animate-pulse rounded-xl bg-slate-100"></div>
                    <div class="h-10 animate-pulse rounded-xl bg-slate-100"></div>
                    <div class="h-28 animate-pulse rounded-xl bg-slate-100"></div>
                    <p class="text-center text-xs text-slate-400">{{ __('patient_booking.payment_processing') }}</p>
                </div>

                <div id="unified-session" class="min-h-[24rem] w-full"></div>
            </div>

            <p class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900" x-cloak x-show="failed">
                {{ __('patient_booking.payment_embedded_unavailable') }}
            </p>
        </div>

        <flux:button
            type="button"
            variant="ghost"
            class="w-full !text-slate-500"
            wire:click="initMyFatoorahEmbeddedV3"
            wire:loading.attr="disabled"
            data-test="patient-checkout-mf-retry"
        >
            <span wire:loading.remove wire:target="initMyFatoorahEmbeddedV3">{{ __('patient_booking.payment_retry') }}</span>
            <span wire:loading wire:target="initMyFatoorahEmbeddedV3">{{ __('patient_booking.payment_processing') }}</span>
        </flux:button>
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
