@php
    /** @var string $callbackUrl */
    /** @var string $checkoutId */
    /** @var string|null $integrity */
    /** @var string $env */
@endphp

<div class="hyperpay-ltr space-y-3" dir="ltr" wire:ignore wire:key="hyperpay-widget-{{ $checkoutId }}">
    @include('partials.hyperpay-ltr')

    <form
        action="{{ $callbackUrl }}"
        class="paymentWidgets min-h-[10rem] w-full rounded-xl border border-zinc-200 bg-white p-2"
        data-brands="MADA VISA MASTER APPLEPAY"
    ></form>

    <script>
        var wpwlOptions = window.hyperpayWrapOnReady({
            style: 'plain',
            locale: @js(app()->getLocale() === 'ar' ? 'ar' : 'en'),
            paymentTarget: '_top',
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
        src="{{ app(\App\Services\HyperpayCheckoutService::class)->widgetScriptUrl($checkoutId) }}"
        @if (filled($integrity)) integrity="{{ $integrity }}" @endif
        crossorigin="anonymous"
    ></script>
</div>
