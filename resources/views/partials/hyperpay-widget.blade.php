@php
    /** @var string $callbackUrl */
    /** @var string $checkoutId */
    /** @var string|null $integrity */
    /** @var string $env */
    $isTestEnv = in_array($env ?? 'test', ['test', 'dev'], true);
@endphp

<div class="hyperpay-ltr space-y-3" dir="ltr" wire:ignore wire:key="hyperpay-widget-{{ $checkoutId }}">
    @include('partials.hyperpay-ltr')

    <form
        action="{{ $callbackUrl }}"
        class="paymentWidgets min-h-[10rem] w-full rounded-xl border border-zinc-200 bg-white p-2"
        data-brands="APPLEPAY VISA MASTER MADA"
    ></form>

    <script>
        var wpwlOptions = window.hyperpayWrapOnReady({
            style: 'plain',
            locale: @js(app()->getLocale() === 'ar' ? 'ar' : 'en'),
            paymentTarget: '_top',
            iframeStyles: {
                'card-number-placeholder': {
                    'color': '#a1a1aa',
                    'font-size': '16px',
                    'font-family': 'system-ui, -apple-system, "Segoe UI", Arial, sans-serif',
                },
                'cvv-placeholder': {
                    'color': '#a1a1aa',
                    'font-size': '16px',
                    'font-family': 'system-ui, -apple-system, "Segoe UI", Arial, sans-serif',
                },
            },
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
