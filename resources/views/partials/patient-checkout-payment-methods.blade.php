@php
    $compact = $compact ?? true;
    $labelSurface = $labelSurface ?? 'bg-white';

    $methods = [
        ['file' => 'mada.svg', 'label' => __('patient_booking.payment_brand_mada'), 'width' => '3.75rem'],
        ['file' => 'visa.svg', 'label' => __('patient_booking.payment_brand_visa'), 'width' => '3.25rem'],
        ['file' => 'mastercard.svg', 'label' => __('patient_booking.payment_brand_mastercard'), 'width' => '2.75rem'],
        ['file' => 'apple-pay.svg', 'label' => __('patient_booking.payment_brand_apple_pay'), 'width' => '3.5rem'],
    ];
@endphp

<div class="space-y-3.5" data-test="patient-checkout-payment-methods">
    <div class="relative flex items-center justify-center py-0.5">
        <div class="absolute inset-x-0 top-1/2 h-px bg-slate-200" aria-hidden="true"></div>
        <span @class(['relative px-3 text-[0.6875rem] font-medium tracking-wide text-slate-400', $labelSurface])>
            {{ __('patient_booking.pay_through') }}
        </span>
    </div>

    <ul
        class="grid grid-cols-4 gap-2"
        role="list"
        aria-label="{{ __('patient_booking.checkout_accepts') }}"
    >
        @foreach ($methods as $method)
            <li class="flex h-11 items-center justify-center rounded-xl bg-gradient-to-b from-white to-slate-50/80 px-2 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                <img
                    src="{{ asset('images/payment/'.$method['file']) }}"
                    alt="{{ $method['label'] }}"
                    class="h-[1.125rem] w-auto max-w-full object-contain object-center select-none"
                    style="max-width: {{ $method['width'] }};"
                    loading="lazy"
                    decoding="async"
                />
            </li>
        @endforeach
    </ul>
</div>
