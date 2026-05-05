{{-- Mashora logo: public/images/logo.png. On blue bars, monochrome white via Tailwind filters. --}}
@php
    /** @var string Tailwind img classes */
    $imgClass ??= 'h-10 w-auto max-w-[min(100%,13rem)] object-contain object-start';

    /** If true, forces a white mark (works best with solid / single-color artwork). Set false for a pre-white PNG. */
    $whiteOnBlue ??= true;

    $filterClasses = $whiteOnBlue ? 'brightness-0 invert' : '';
@endphp
<img
    src="{{ asset('images/logo.png') }}"
    alt="{{ __('patient.brand') }}"
    class="{{ trim($filterClasses.' '.$imgClass) }}"
    loading="eager"
    decoding="async"
/>
