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

    .hyperpay-ltr .wpwl-label {
        direction: ltr !important;
        text-align: left !important;
    }

    .hyperpay-ltr .wpwl-brand-card {
        left: 10px !important;
        right: auto !important;
    }

    @media (min-width: 480px) {
        .hyperpay-ltr .wpwl-label,
        .hyperpay-ltr .wpwl-wrapper,
        .hyperpay-ltr .wpwl-brand-card {
            float: left !important;
        }
    }

    .hyperpay-ltr .wpwl-button-pay {
        float: right !important;
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

    window.hyperpayWrapOnReady = function (options) {
        var originalOnReady = options.onReady;

        options.onReady = function () {
            window.hyperpayApplyLtrDirection();

            if (typeof originalOnReady === 'function') {
                originalOnReady.apply(this, arguments);
            }
        };

        return options;
    };
</script>
