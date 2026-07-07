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

    .hyperpay-ltr,
    .hyperpay-ltr .paymentWidgets,
    .hyperpay-ltr .wpwl-form,
    .hyperpay-ltr .wpwl-form-card {
        color: #18181b !important;
    }

    .hyperpay-ltr .wpwl-label {
        direction: ltr !important;
        text-align: left !important;
        color: #18181b !important;
    }

    .hyperpay-ltr .wpwl-control,
    .hyperpay-ltr input,
    .hyperpay-ltr select {
        color: #18181b !important;
        -webkit-text-fill-color: #18181b !important;
        background-color: #ffffff !important;
        caret-color: #18181b !important;
    }

    .hyperpay-ltr .wpwl-control::placeholder,
    .hyperpay-ltr input::placeholder {
        color: #52525b !important;
        opacity: 1 !important;
    }

    .dark .hyperpay-ltr .wpwl-label,
    .dark .hyperpay-ltr .wpwl-control,
    .dark .hyperpay-ltr input,
    .dark .hyperpay-ltr select {
        color: #18181b !important;
        -webkit-text-fill-color: #18181b !important;
    }

    .hyperpay-ltr .wpwl-wrapper-cardNumber,
    .hyperpay-ltr .wpwl-wrapper-brand,
    .hyperpay-ltr .wpwl-group-brand {
        position: relative !important;
        text-align: left !important;
    }

    .hyperpay-ltr .wpwl-wrapper-brand,
    .hyperpay-ltr .wpwl-group-brand {
        display: block !important;
        width: 100% !important;
        clear: both !important;
        float: none !important;
        margin: 0 0 0.75rem !important;
    }

    .hyperpay-ltr .wpwl-brand,
    .hyperpay-ltr .wpwl-brand-card,
    .hyperpay-ltr [class*="wpwl-brand-"] {
        position: relative !important;
        left: 0 !important;
        right: auto !important;
        inset-inline-start: 0 !important;
        inset-inline-end: auto !important;
        float: left !important;
        clear: left !important;
        margin: 0 0 0.75rem !important;
        transform: none !important;
    }

    .hyperpay-ltr .wpwl-wrapper-cardNumber .wpwl-brand,
    .hyperpay-ltr .wpwl-wrapper-cardNumber .wpwl-brand-card,
    .hyperpay-ltr .wpwl-wrapper-cardNumber [class*="wpwl-brand-"] {
        position: absolute !important;
        top: 50% !important;
        left: 10px !important;
        right: auto !important;
        inset-inline-start: 10px !important;
        inset-inline-end: auto !important;
        float: none !important;
        clear: none !important;
        margin: 0 !important;
        transform: translateY(-50%) !important;
    }

    .hyperpay-ltr .wpwl-wrapper-cardNumber .wpwl-control-cardNumber {
        padding-left: 3.25rem !important;
        padding-inline-start: 3.25rem !important;
    }

    .hyperpay-ltr .wpwl-button-pay {
        float: right !important;
    }

    @media (min-width: 480px) {
        .hyperpay-ltr .wpwl-label,
        .hyperpay-ltr .wpwl-wrapper {
            float: left !important;
        }
    }

    /* Premium theme, layered on top of HyperPay's "plain" style per their
       customization guide (plain + custom CSS is the recommended approach). */
    .hyperpay-ltr .wpwl-form {
        font-family: inherit !important;
        max-width: 100% !important;
    }

    .hyperpay-ltr .wpwl-group {
        margin-bottom: 1rem !important;
    }

    .hyperpay-ltr .wpwl-label {
        display: block !important;
        font-size: 0.6875rem !important;
        font-weight: 600 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.03em !important;
        color: #71717a !important;
        margin-bottom: 0.375rem !important;
    }

    .hyperpay-ltr .wpwl-wrapper {
        width: 100% !important;
    }

    .hyperpay-ltr .wpwl-control,
    .hyperpay-ltr select.wpwl-control {
        width: 100% !important;
        min-height: 3rem !important;
        padding: 0.75rem 1rem !important;
        font-size: 16px !important;
        font-family: inherit !important;
        border: 1.5px solid #e4e4e7 !important;
        border-radius: 0.875rem !important;
        background-color: #ffffff !important;
        box-shadow: none !important;
        transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
    }

    .hyperpay-ltr .wpwl-control:focus,
    .hyperpay-ltr select.wpwl-control:focus {
        outline: none !important;
        border-color: #10B981 !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15) !important;
    }

    .hyperpay-ltr .wpwl-control.wpwl-has-error,
    .hyperpay-ltr .wpwl-wrapper.wpwl-has-error .wpwl-control {
        border-color: #f43f5e !important;
    }

    .hyperpay-ltr .wpwl-control.wpwl-has-error:focus {
        box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.15) !important;
    }

    .hyperpay-ltr .wpwl-hint,
    .hyperpay-ltr [class*="wpwl-hint-"] {
        display: block !important;
        margin-top: 0.375rem !important;
        font-size: 0.75rem !important;
        font-weight: 500 !important;
        color: #e11d48 !important;
    }

    .hyperpay-ltr .wpwl-brand,
    .hyperpay-ltr .wpwl-brand-card,
    .hyperpay-ltr [class*="wpwl-brand-"] {
        max-height: 1.75rem !important;
        height: 1.75rem !important;
        width: auto !important;
        max-width: 3rem !important;
        object-fit: contain !important;
    }

    .hyperpay-ltr .wpwl-button,
    .hyperpay-ltr .wpwl-button-pay {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
        float: none !important;
        clear: both !important;
        min-height: 3rem !important;
        margin: 0.5rem 0 0 !important;
        padding: 0.75rem 1.5rem !important;
        border: none !important;
        border-radius: 999px !important;
        background: #10B981 !important;
        background-image: none !important;
        color: #ffffff !important;
        font-size: 0.9375rem !important;
        font-weight: 700 !important;
        box-shadow: 0 8px 25px -5px rgba(16, 185, 129, 0.3) !important;
        transition: filter 0.15s ease !important;
        cursor: pointer !important;
    }

    .hyperpay-ltr .wpwl-button:hover,
    .hyperpay-ltr .wpwl-button-pay:hover {
        filter: brightness(0.97) !important;
    }

    .hyperpay-ltr .wpwl-button:disabled,
    .hyperpay-ltr .wpwl-button-pay:disabled {
        opacity: 0.6 !important;
        cursor: not-allowed !important;
    }

    .hyperpay-ltr .wpwl-spinner {
        border-color: rgba(255, 255, 255, 0.35) !important;
        border-top-color: #ffffff !important;
    }
</style>

<script>
    window.hyperpayApplyReadableStyles = function () {
        var textColor = '#18181b';
        var placeholderColor = '#52525b';

        document.querySelectorAll('[class*="wpwl"]').forEach(function (el) {
            el.style.direction = 'ltr';
            el.setAttribute('dir', 'ltr');
        });

        document.querySelectorAll('.wpwl-form, .wpwl-form-card').forEach(function (el) {
            el.style.direction = 'ltr';
            el.setAttribute('dir', 'ltr');
            el.setAttribute('lang', 'en');
            el.style.color = textColor;
        });

        document.querySelectorAll('.hyperpay-ltr .wpwl-label').forEach(function (el) {
            el.style.color = textColor;
        });

        document.querySelectorAll('.hyperpay-ltr .wpwl-brand, .hyperpay-ltr .wpwl-brand-card, .hyperpay-ltr [class*="wpwl-brand-"]').forEach(function (el) {
            var inCardNumber = el.closest('.wpwl-wrapper-cardNumber') !== null;

            el.style.right = 'auto';
            el.style.insetInlineEnd = 'auto';
            el.style.float = inCardNumber ? 'none' : 'left';
            el.style.clear = inCardNumber ? 'none' : 'left';

            if (inCardNumber) {
                el.style.position = 'absolute';
                el.style.top = '50%';
                el.style.left = '10px';
                el.style.insetInlineStart = '10px';
                el.style.margin = '0';
                el.style.transform = 'translateY(-50%)';
            } else {
                el.style.position = 'relative';
                el.style.left = '0';
                el.style.insetInlineStart = '0';
                el.style.margin = '0 0 0.75rem';
                el.style.transform = 'none';
            }
        });

        document.querySelectorAll('.hyperpay-ltr .wpwl-wrapper-brand, .hyperpay-ltr .wpwl-group-brand').forEach(function (el) {
            el.style.textAlign = 'left';
            el.style.width = '100%';
        });

        document.querySelectorAll(
            '.wpwl-wrapper-cardNumber input, .wpwl-wrapper-expiry input, .wpwl-wrapper-cvv input, .wpwl-wrapper-cardHolder input, .hyperpay-ltr .wpwl-control, .hyperpay-ltr input, .hyperpay-ltr select'
        ).forEach(function (el) {
            el.style.direction = 'ltr';
            el.setAttribute('dir', 'ltr');
            el.style.unicodeBidi = 'isolate';
            el.style.color = textColor;
            el.style.webkitTextFillColor = textColor;
            el.style.caretColor = textColor;
            el.style.backgroundColor = '#ffffff';

            if (el.placeholder !== '') {
                el.style.setProperty('--placeholder-color', placeholderColor);
            }
        });
    };

    window.hyperpayApplyLtrDirection = window.hyperpayApplyReadableStyles;

    window.hyperpayWrapOnReady = function (options) {
        var originalOnReady = options.onReady;

        options.onReady = function () {
            window.hyperpayApplyReadableStyles();

            var container = document.querySelector('.hyperpay-ltr');

            if (container && ! container.dataset.hyperpayStyleObserver) {
                container.dataset.hyperpayStyleObserver = '1';

                var observer = new MutationObserver(function () {
                    window.hyperpayApplyReadableStyles();
                });

                observer.observe(container, { childList: true, subtree: true });
            }

            if (typeof originalOnReady === 'function') {
                originalOnReady.apply(this, arguments);
            }
        };

        return options;
    };
</script>
