@php
    /** @var string $callbackUrl */
    /** @var string $checkoutId */
    /** @var string|null $integrity */
    /** @var string $env */
    $isTestEnv = in_array($env ?? 'test', ['test', 'dev'], true);

    $paymentLabels = [
        'cardHolder' => __('patient_booking.payment_label_card_holder'),
        'cardNumber' => __('patient_booking.payment_label_card_number'),
        'expiryDate' => __('patient_booking.payment_label_expiry'),
        'cvv' => __('patient_booking.payment_label_cvv'),
        'mmyy' => __('patient_booking.payment_placeholder_expiry'),
    ];

    $paymentPlaceholders = [
        'cardHolder' => __('patient_booking.payment_placeholder_card_holder'),
        'cardNumber' => __('patient_booking.payment_placeholder_card_number'),
        'expiryDate' => __('patient_booking.payment_placeholder_expiry'),
        'cvv' => __('patient_booking.payment_placeholder_cvv'),
        'mmyy' => __('patient_booking.payment_placeholder_expiry'),
    ];
@endphp

<div class="hyperpay-ltr space-y-3" dir="ltr" wire:ignore wire:key="hyperpay-widget-{{ $checkoutId }}">
    @include('partials.hyperpay-ltr')
    @include('partials.payment-card-field-guide')

    <form
        action="{{ $callbackUrl }}"
        class="paymentWidgets min-h-[12rem] w-full rounded-xl border border-zinc-200 bg-white p-3 sm:min-h-[14rem] sm:p-4"
        data-brands="APPLEPAY VISA MASTER MADA"
    ></form>

    <script>
        var wpwlOptions = window.hyperpayWrapOnReady({
            style: 'card',
            showLabels: true,
            showPlaceholders: true,
            locale: @js(app()->getLocale() === 'ar' ? 'ar' : 'en'),
            paymentTarget: '_top',
            labels: @js($paymentLabels),
            placeholders: @js($paymentPlaceholders),
            applePay: {
                displayName: @js(config('app.name')),
                total: { label: @js(config('app.name')) },
                buttonType: 'pay',
                supportedNetworks: ['masterCard', 'visa', 'mada'],
                supportedCountries: ['SA'],
                buttonStyle: 'black',
                buttonSource: 'js',
            },
        });
    </script>
    <script
        src="{{ $isTestEnv ? 'https://eu-test.oppwa.com' : 'https://oppwa.com' }}/v1/paymentWidgets.js?checkoutId={{ $checkoutId }}"
        @if (filled($integrity)) integrity="{{ $integrity }}" @endif
        crossorigin="anonymous"
    ></script>
</div>
