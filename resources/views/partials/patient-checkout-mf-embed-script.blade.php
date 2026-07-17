{{-- Loaded once; startMyFatoorahEmbed(state, config) is called from Alpine x-init. --}}
<script>
    window.startMyFatoorahEmbed = function (state, config) {
        const completeUrl = config.completeUrl || '';
        const sessionId = config.sessionId || '';
        const scriptUrl = config.scriptUrl || '';
        const successUrl = config.successUrl || '';
        const containerId = config.containerId || '';
        const preferDesktop = Boolean(config.preferDesktop);
        const lang = config.lang || 'en';
        const insertCard = config.insertCard || '';
        const placeholders = config.placeholders || {};
        const csrfFallback = config.csrfToken || '';
        const isNarrow = ! window.matchMedia('(min-width: 640px)').matches;

        const csrfMeta = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || csrfFallback;

        let supportsApplePay = false;
        try {
            supportsApplePay = Boolean(
                window.ApplePaySession
                && typeof ApplePaySession.canMakePayments === 'function'
                && ApplePaySession.canMakePayments()
            );
        } catch (e) {
            supportsApplePay = false;
        }

        const paymentOptions = supportsApplePay
            ? ['ApplePay', 'GooglePay', 'Card']
            : ['GooglePay', 'Card'];

        const fail = (reason = '') => {
            state.booting = false;
            state.ready = false;
            state.paying = false;
            state.failed = true;
            state.softError = '';
            state.failReason = reason || '';
            console.error('[MyFatoorah embed]', reason || 'init failed');
        };

        const softFail = (reason = '') => {
            state.paying = false;
            state.softError = reason || 'payment unsuccessful';
            console.warn('[MyFatoorah embed]', state.softError);
        };

        const isThisViewport = () => {
            const desktop = window.matchMedia('(min-width: 640px)').matches;

            return preferDesktop ? desktop : ! desktop;
        };

        // SessionId is one-time; never mount into the hidden mobile/desktop twin.
        if (! isThisViewport()) {
            state.booting = false;

            return;
        }

        const extractPaymentId = (response) => {
            if (response?.paymentId) {
                return String(response.paymentId);
            }

            if (! response?.redirectionUrl) {
                return '';
            }

            try {
                const url = new URL(response.redirectionUrl, window.location.origin);

                return url.searchParams.get('paymentId') || url.searchParams.get('Id') || '';
            } catch (e) {
                return '';
            }
        };

        const completeBooking = (payload) => {
            state.paying = true;
            state.softError = '';

            const body = { sessionId: payload.sessionId || sessionId };

            if (payload.paymentData) {
                body.paymentData = typeof payload.paymentData === 'string'
                    ? payload.paymentData
                    : String(payload.paymentData);
            }

            if (payload.paymentId) {
                body.paymentId = String(payload.paymentId);
            }

            fetch(completeUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfMeta(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            })
                .then(async (res) => {
                    let data = null;

                    try {
                        data = await res.json();
                    } catch (e) {
                        data = null;
                    }

                    return { ok: res.ok, status: res.status, data };
                })
                .then(({ status, data }) => {
                    if (data?.redirect) {
                        window.location.href = data.redirect;

                        return;
                    }

                    // Money may already be taken — recover via success page CustomerReference lookup.
                    softFail(data?.message || ('booking completion failed (' + status + ')'));
                    setTimeout(() => { window.location.href = successUrl; }, 2000);
                })
                .catch(() => {
                    softFail('booking completion network error');
                    setTimeout(() => { window.location.href = successUrl; }, 2000);
                });
        };

        const payment = (response) => {
            state.paying = false;
            console.info('[MyFatoorah embed] callback', response);

            if (! response || ! response.isSuccess) {
                softFail(response?.message || response?.error || 'payment unsuccessful');

                return;
            }

            const paymentId = extractPaymentId(response);
            let paymentData = response.paymentData;

            if (paymentData && typeof paymentData !== 'string') {
                paymentData = paymentData.paymentData || paymentData.data || null;

                if (paymentData && typeof paymentData !== 'string') {
                    paymentData = null;
                }
            }

            if (response.paymentCompleted && paymentData) {
                completeBooking({
                    paymentData: paymentData,
                    paymentId: paymentId || undefined,
                    sessionId: response.sessionId || sessionId,
                });

                return;
            }

            // Apple Pay / wallets may finish with paymentId only (no encrypted paymentData).
            if (paymentId) {
                completeBooking({
                    paymentId: paymentId,
                    sessionId: response.sessionId || sessionId,
                });

                return;
            }

            // Intermediate callback (OTP / 3DS still in progress) — keep waiting.
            if (response.paymentCompleted === false) {
                return;
            }

            // Last resort: success page confirms via CustomerReference / paymentId query.
            if (response.paymentCompleted === true) {
                window.location.href = successUrl;

                return;
            }

            softFail('unexpected payment callback');
        };

        const eventHandler = (event) => {
            if (event?.name === 'VIEW_READY') {
                state.booting = false;
                state.ready = true;
            }

            if (event?.name === 'PAYMENT_STARTED') {
                state.paying = true;
            }

            if (event?.name === 'SESSION_CANCELED' || event?.name === 'PAYMENT_COMPLETED') {
                state.paying = false;
            }
        };

        const start = () => {
            if (! window.myfatoorah) {
                fail('session.js did not expose window.myfatoorah');

                return;
            }

            if (! document.getElementById(containerId)) {
                fail('container #' + containerId + ' missing');

                return;
            }

            if (window.__mfSessionBooted === sessionId) {
                state.booting = false;
                state.ready = true;

                return;
            }

            try {
                // Card fields only in iframe; Pay Now is our sticky button (less mobile scroll).
                window.myfatoorah.init({
                    sessionId: sessionId,
                    callback: payment,
                    containerId: containerId,
                    shouldHandlePaymentUrl: true,
                    paymentOptions: paymentOptions,
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
                                cardHeight: isNarrow ? '190px' : '200px',
                                tokenHeight: isNarrow ? '190px' : '200px',
                                input: {
                                    color: '#0f172a',
                                    fontSize: isNarrow ? '16px' : '14px',
                                    fontFamily: 'sans-serif',
                                    inputHeight: isNarrow ? '40px' : '38px',
                                    inputMargin: '0px',
                                    borderColor: '#e2e8f0',
                                    borderWidth: '1px',
                                    borderRadius: '10px',
                                    placeHolder: {
                                        holderName: placeholders.holderName || '',
                                        cardNumber: placeholders.cardNumber || '',
                                        expiryDate: placeholders.expiryDate || '',
                                        securityCode: placeholders.securityCode || '',
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
                                    useCustomButton: true,
                                },
                                separator: {
                                    useCustomSeparator: false,
                                    textContent: insertCard,
                                    fontSize: '13px',
                                    color: '#94a3b8',
                                    fontFamily: 'sans-serif',
                                    textSpacing: '8px',
                                    lineStyle: 'solid',
                                    lineColor: '#e2e8f0',
                                    lineThickness: '1px',
                                },
                            },
                        },
                        applePay: {
                            language: lang,
                            style: {
                                frameHeight: '48px',
                                frameWidth: '100%',
                                button: {
                                    height: '44px',
                                    type: 'plain',
                                    borderRadius: '10px',
                                },
                            },
                        },
                        googlePay: {
                            language: lang,
                            style: {
                                frameHeight: '48px',
                                frameWidth: '100%',
                                button: {
                                    height: '44px',
                                    type: 'pay',
                                    borderRadius: '10px',
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
                if (state.booting) {
                    fail('embed timed out waiting for VIEW_READY');
                }
            }, 20000);
        };

        if (! sessionId || ! scriptUrl) {
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
    };
</script>
