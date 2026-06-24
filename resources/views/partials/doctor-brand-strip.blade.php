{{-- Doctor portal: Awaan logo on green chrome — same asset pattern as patient-brand-strip. --}}
@php
    /** @var 'compact'|'sidebar' */
    $density ??= 'sidebar';

    /** @var string Home link (e.g. dashboard or welcome for guests). */
    $href ??= route('doctor.dashboard');

    $logoImgClass = match ($density) {
        'compact' => 'h-9 w-auto max-w-[min(100%,10rem)] object-contain object-center',
        default => 'h-12 w-auto max-w-[min(100%,11rem)] object-contain object-center',
    };

    $linkClass = match ($density) {
        'compact' => 'flex w-full min-w-0 justify-start px-1 text-white no-underline',
        default => 'flex w-full min-w-0 justify-center px-1 text-white no-underline',
    };
@endphp

<a
    href="{{ $href }}"
    wire:navigate
    class="{{ $linkClass }}"
    title="{{ __('patient.brand') }}"
    aria-label="{{ __('patient.brand') }}"
>
    @include('partials.patient-brand-logo', [
        'svgClass' => $logoImgClass,
        'onGreenChrome' => true,
    ])
</a>
