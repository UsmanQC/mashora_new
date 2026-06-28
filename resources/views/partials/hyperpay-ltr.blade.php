<style>
    .hyperpay-ltr,
    .hyperpay-ltr .paymentWidgets,
    .hyperpay-ltr [class*="wpwl"] {
        direction: ltr !important;
        text-align: left !important;
    }

    .hyperpay-ltr .wpwl-form,
    .hyperpay-ltr .wpwl-form-card,
    .hyperpay-ltr .wpwl-group,
    .hyperpay-ltr .wpwl-wrapper,
    .hyperpay-ltr .wpwl-wrapper-cardNumber,
    .hyperpay-ltr .wpwl-wrapper-expiry,
    .hyperpay-ltr .wpwl-wrapper-cvv,
    .hyperpay-ltr .wpwl-wrapper-cardHolder,
    .hyperpay-ltr .wpwl-wrapper-brand,
    .hyperpay-ltr .wpwl-control,
    .hyperpay-ltr input,
    .hyperpay-ltr select {
        direction: ltr !important;
        unicode-bidi: isolate !important;
        text-align: left !important;
    }

    .hyperpay-ltr .paymentWidgets,
    .hyperpay-ltr .wpwl-form,
    .hyperpay-ltr .wpwl-form-card {
        min-height: 12rem !important;
        overflow: visible !important;
    }

    .hyperpay-ltr .wpwl-group {
        display: block !important;
        clear: both !important;
        margin-bottom: 0.75rem !important;
        visibility: visible !important;
        opacity: 1 !important;
        width: 100% !important;
    }

    .hyperpay-ltr .wpwl-label,
    .hyperpay-ltr .wpwl-label-cardNumber,
    .hyperpay-ltr .wpwl-label-expiry,
    .hyperpay-ltr .wpwl-label-cvv,
    .hyperpay-ltr .wpwl-label-cardHolder,
    .hyperpay-ltr .wpwl-label-brand {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        width: 100% !important;
        float: none !important;
        margin-bottom: 0.35rem !important;
        padding: 0 !important;
        color: #525252 !important;
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        line-height: 1.25 !important;
    }

    .hyperpay-ltr .wpwl-wrapper {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        width: 100% !important;
        max-width: 100% !important;
        margin-bottom: 0.25rem !important;
    }

    .hyperpay-ltr .wpwl-control,
    .hyperpay-ltr input.wpwl-control,
    .hyperpay-ltr select.wpwl-control {
        display: block !important;
        width: 100% !important;
        min-height: 42px !important;
        padding: 0.5rem 0.75rem !important;
        color: #18181b !important;
        background-color: #ffffff !important;
        border: 1px solid #d4d4d8 !important;
        border-radius: 0.5rem !important;
        opacity: 1 !important;
        visibility: visible !important;
        box-shadow: none !important;
    }

    .hyperpay-ltr .wpwl-control::placeholder {
        color: #a3a3a3 !important;
        opacity: 1 !important;
    }

    .hyperpay-ltr .wpwl-brand-card {
        left: 10px !important;
        right: auto !important;
    }

    .hyperpay-ltr .wpwl-button,
    .hyperpay-ltr .wpwl-button-pay {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-height: 2.75rem !important;
        margin-top: 0.5rem !important;
        border-radius: 9999px !important;
        background-color: #10b981 !important;
        color: #ffffff !important;
        font-weight: 600 !important;
        float: none !important;
    }

    @media (min-width: 480px) {
        .hyperpay-ltr .wpwl-label {
            width: 38% !important;
            float: left !important;
        }

        .hyperpay-ltr .wpwl-wrapper {
            width: 58% !important;
            float: left !important;
        }

        .hyperpay-ltr .wpwl-brand-card {
            float: left !important;
        }

        .hyperpay-ltr .wpwl-button-pay {
            float: right !important;
        }
    }

    @media (max-width: 479px) {
        .hyperpay-ltr .wpwl-label,
        .hyperpay-ltr .wpwl-wrapper,
        .hyperpay-ltr .wpwl-brand-card {
            float: none !important;
            width: 100% !important;
        }
    }
</style>

<script>
    window.hyperpayApplyLtrDirection = function () {
        document.querySelectorAll('[class*="wpwl"]').forEach(function (el) {
            el.style.direction = 'ltr';
            el.setAttribute('dir', 'ltr');
        });

        document.querySelectorAll('.wpwl-form, .wpwl-form-card').forEach(function (el) {
            el.style.direction = 'ltr';
            el.setAttribute('dir', 'ltr');
            el.setAttribute('lang', 'en');
        });

        document.querySelectorAll(
            '.wpwl-wrapper-cardNumber input, .wpwl-wrapper-expiry input, .wpwl-wrapper-cvv input, .wpwl-wrapper-cardHolder input'
        ).forEach(function (el) {
            el.style.direction = 'ltr';
            el.setAttribute('dir', 'ltr');
            el.style.unicodeBidi = 'isolate';
        });
    };

    window.hyperpayEnsureFieldHints = function () {
        var labelMap = @json([
            'cardHolder' => __('patient_booking.payment_label_card_holder'),
            'cardNumber' => __('patient_booking.payment_label_card_number'),
            'expiry' => __('patient_booking.payment_label_expiry'),
            'cvv' => __('patient_booking.payment_label_cvv'),
        ]);

        Object.keys(labelMap).forEach(function (key) {
            document.querySelectorAll('.hyperpay-ltr .wpwl-label-' + key).forEach(function (el) {
                if (!el.textContent || el.textContent.trim() === '') {
                    el.textContent = labelMap[key];
                }

                el.style.display = 'block';
                el.style.visibility = 'visible';
                el.style.color = '#525252';
            });
        });

        document.querySelectorAll('.hyperpay-ltr .wpwl-control').forEach(function (el) {
            el.style.color = '#18181b';
            el.style.backgroundColor = '#ffffff';
            el.style.border = '1px solid #d4d4d8';
        });
    };

    window.hyperpayWrapOnReady = function (options) {
        var originalOnReady = options.onReady;

        options.onReady = function () {
            window.hyperpayApplyLtrDirection();
            window.hyperpayEnsureFieldHints();

            if (typeof originalOnReady === 'function') {
                originalOnReady.apply(this, arguments);
            }
        };

        return options;
    };
</script>
