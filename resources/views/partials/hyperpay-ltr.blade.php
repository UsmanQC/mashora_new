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
