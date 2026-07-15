@php
    $compact = $compact ?? true;
    $labelSurface = $labelSurface ?? 'bg-white';

    $methods = [
        ['file' => 'mada.svg', 'label' => __('patient_booking.payment_brand_mada')],
        ['file' => 'visa.svg', 'label' => __('patient_booking.payment_brand_visa')],
        ['file' => 'mastercard.svg', 'label' => __('patient_booking.payment_brand_mastercard')],
        ['file' => 'apple-pay.svg', 'label' => __('patient_booking.payment_brand_apple_pay')],
    ];
@endphp

<div class="space-y-3" data-test="patient-checkout-payment-methods">
    <div class="relative flex items-center justify-center py-0.5">
        <div class="absolute inset-x-0 top-1/2 h-px bg-slate-100" aria-hidden="true"></div>
        <span @class(['relative px-3 text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-slate-400', $labelSurface])>
            {{ __('patient_booking.pay_through') }}
        </span>
    </div>

    <ul
        class="flex flex-wrap items-center justify-center gap-2"
        role="list"
        aria-label="{{ __('patient_booking.checkout_accepts') }}"
    >
        @foreach ($methods as $method)
            <li class="flex h-9 min-w-[4.25rem] items-center justify-center rounded-lg border border-slate-100/90 bg-slate-50/70 px-3 transition hover:border-[#10B981] hover:bg-emerald-50 hover:shadow-[0_0_0_1px_rgba(16,185,129,0.35)]">
                <img
                    src="{{ asset('images/payment/'.$method['file']) }}"
                    alt="{{ $method['label'] }}"
                    class="h-4 w-auto max-w-[3.5rem] object-contain object-center opacity-90 select-none"
                    loading="lazy"
                    decoding="async"
                />
            </li>
        @endforeach
    </ul>
</div>
