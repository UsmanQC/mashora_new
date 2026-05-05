@php
    $methods = [
        ['file' => 'visa.svg', 'label' => __('patient_booking.payment_brand_visa')],
        ['file' => 'mastercard.svg', 'label' => __('patient_booking.payment_brand_mastercard')],
        ['file' => 'mada.svg', 'label' => __('patient_booking.payment_brand_mada')],
        ['file' => 'apple-pay.svg', 'label' => __('patient_booking.payment_brand_apple_pay')],
    ];
@endphp

<section aria-label="{{ __('patient_booking.checkout_accepts') }}">
    <ul class="flex flex-wrap items-center justify-center gap-7 sm:justify-start sm:gap-8 md:gap-10" role="list">
        @foreach ($methods as $method)
            <li class="shrink-0">
                <img
                    src="{{ asset('images/payment/'.$method['file']) }}"
                    alt="{{ $method['label'] }}"
                    class="h-14 w-auto max-w-[9.5rem] object-contain select-none sm:h-16 md:h-[4.25rem] md:max-w-[11rem]"
                    loading="lazy"
                    decoding="async"
                />
            </li>
        @endforeach
    </ul>
</section>
