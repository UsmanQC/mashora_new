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
        @endphp
        {{-- Embedded Payment v3 only (no hosted MyFatoorah redirect). --}}
        <div
            class="space-y-4"
            wire:ignore
            wire:key="mf-v3-{{ $this->mfSessionId }}-{{ $mfContainerId }}"
            data-test="patient-checkout-mf-embed"
            data-mf-container="{{ $mfContainerId }}"
            x-data="{ booting: true, failed: false, failReason: '' }"
            x-init="
                (() => {
                    const completeUrl = @js($this->mfCompleteUrl);
                    const sessionId = @js($this->mfSessionId);
                    const scriptUrl = @js($this->mfSessionJsUrl);
                    const csrf = @js(csrf_token());
                    const containerId = @js($mfContainerId);
                    const preferDesktop = @js(($mfPreferDesktop ?? false));
                    const lang = @js(app()->getLocale() === 'ar' ? 'ar' : 'en');
                    const payNow = @js(__('myfatoorah.payNow'));
                    const insertCard = @js(__('myfatoorah.insertCardDetails'));
                    const isNarrow = ! window.matchMedia('(min-width: 640px)').matches;

                    const fail = (reason = '') => {
                        booting = false;
                        failed = true;
                        failReason = reason || '';
                        console.error('[MyFatoorah embed]', reason || 'init failed');
                    };

                    const isThisViewport = () => {
                        const desktop = window.matchMedia('(min-width: 640px)').matches;
                        return preferDesktop ? desktop : ! desktop;
                    };

                    // SessionId is one-time; never mount into the hidden mobile/desktop twin.
                    if (! isThisViewport()) {
                        booting = false;
                        return;
                    }

                    const payment = (response) => {
                        if (!response || !response.isSuccess) {
                            fail('payment callback unsuccessful');
                            return;
                        }

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
                                    fail('booking completion failed');
                                })
                                .catch(() => fail('booking completion network error'));
                            return;
                        }

                        fail('unexpected payment callback (hosted redirect blocked)');
                    };

                    const eventHandler = (event) => {
                        if (event?.name === 'VIEW_READY') {
                            booting = false;
                        }
                    };

                    const start = () => {
                        if (!window.myfatoorah) {
                            fail('session.js did not expose window.myfatoorah');
                            return;
                        }

                        if (!document.getElementById(containerId)) {
                            fail('container #' + containerId + ' missing');
                            return;
                        }

                        if (window.__mfSessionBooted === sessionId) {
                            booting = false;
                            return;
                        }

                        try {
                            // Default MyFatoorah layout + brand green Pay Now (mobile-friendly heights).
                            window.myfatoorah.init({
                                sessionId: sessionId,
                                callback: payment,
                                containerId: containerId,
                                shouldHandlePaymentUrl: true,
                                // Wallet row filter (KNET / Benefit excluded). Card keeps Insert Card Details.
                                paymentOptions: ['GooglePay', 'ApplePay', 'Card'],
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
                                            cardHeight: isNarrow ? '280px' : '240px',
                                            tokenHeight: isNarrow ? '280px' : '240px',
                                            input: {
                                                color: '#0f172a',
                                                fontSize: isNarrow ? '16px' : '14px',
                                                fontFamily: 'sans-serif',
                                                inputHeight: isNarrow ? '44px' : '40px',
                                                inputMargin: '0px',
                                                borderColor: '#e2e8f0',
                                                borderWidth: '1px',
                                                borderRadius: '10px',
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
                                                borderColor: '#ef4444',
                                                borderRadius: '10px',
                                            },
                                            button: {
                                                useCustomButton: false,
                                                textContent: payNow,
                                                fontSize: isNarrow ? '16px' : '15px',
                                                fontFamily: 'sans-serif',
                                                color: 'white',
                                                backgroundColor: '#10B981',
                                                height: isNarrow ? '48px' : '44px',
                                                borderRadius: '12px',
                                                width: '100%',
                                                margin: '14px auto 0 auto',
                                                cursor: 'pointer',
                                            },
                                            separator: {
                                                useCustomSeparator: false,
                                                textContent: insertCard,
                                                fontSize: '14px',
                                                color: '#94a3b8',
                                                fontFamily: 'sans-serif',
                                                textSpacing: '10px',
                                                lineStyle: 'solid',
                                                lineColor: '#e2e8f0',
                                                lineThickness: '1px',
                                            },
                                        },
                                    },
                                    applePay: {
                                        language: lang,
                                        style: {
                                            frameHeight: isNarrow ? '48px' : '44px',
                                            frameWidth: '100%',
                                            button: {
                                                height: isNarrow ? '48px' : '44px',
                                                type: 'pay',
                                                borderRadius: '12px',
                                            },
                                        },
                                    },
                                    googlePay: {
                                        language: lang,
                                        style: {
                                            frameHeight: isNarrow ? '48px' : '44px',
                                            frameWidth: '100%',
                                            button: {
                                                height: isNarrow ? '48px' : '44px',
                                                type: 'pay',
                                                borderRadius: '12px',
                                                color: 'black',
                                            },
                                        },
                                    },
                                },
                            });
                            window.__mfSessionBooted = sessionId;
                        } catch (e) {
                            fail(e?.message || 'myfatoorah.init threw');
                        }

                        setTimeout(() => {
                            if (booting) {
                                fail('embed timed out waiting for VIEW_READY');
                            }
                        }, 20000);
                    };

                    if (!sessionId || !scriptUrl) {
                        fail('missing sessionId or scriptUrl');
                        return;
                    }

                    if (window.myfatoorah) {
                        start();
                        return;
                    }

                    const existing = document.querySelector('script[data-mf-v3-session]');
                    if (existing) {
                        if (existing.dataset.ready === '1') {
                            start();
                            return;
                        }
                        existing.addEventListener('load', () => { existing.dataset.ready = '1'; start(); }, { once: true });
                        existing.addEventListener('error', () => fail('failed to load session.js'), { once: true });
                        return;
                    }

                    const script = document.createElement('script');
                    script.src = scriptUrl;
                    script.async = true;
                    script.dataset.mfV3Session = '1';
                    script.addEventListener('load', () => { script.dataset.ready = '1'; start(); }, { once: true });
                    script.addEventListener('error', () => fail('failed to load session.js'), { once: true });
                    document.head.appendChild(script);
                })()
            "
        >
            <div class="mx-auto w-full max-w-[400px] bg-white px-0 sm:px-0">
                <div x-show="booting && !failed" x-cloak class="space-y-3 py-6">
                    <div class="h-10 animate-pulse rounded-xl bg-slate-100"></div>
                    <div class="h-10 animate-pulse rounded-xl bg-slate-100"></div>
                    <div class="h-28 animate-pulse rounded-xl bg-slate-100"></div>
                    <p class="text-center text-xs text-slate-400">{{ __('patient_booking.payment_processing') }}</p>
                </div>

                <div id="{{ $mfContainerId }}" class="min-h-[22rem] w-full bg-white sm:min-h-[20rem]"></div>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900" x-cloak x-show="failed">
                <p>{{ __('patient_booking.payment_embedded_unavailable') }}</p>
                <p class="mt-1 font-mono text-[0.65rem] text-amber-800/80" x-show="failReason" x-text="failReason"></p>
            </div>
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
