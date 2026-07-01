{{-- Awaan logo link for blue/green chrome (see patient-brand-logo.blade.php). --}}
@php
    /** @var 'compact'|'sidebar' */
    $density ??= 'sidebar';
    $align ??= $density === 'compact' ? 'start' : 'center';

    $logoImgClass = match ($density) {
        'compact' => 'h-9 w-auto max-w-[min(100%,10rem)] object-contain object-start',
        default => 'h-12 w-auto max-w-[min(100%,11rem)] object-contain object-center',
    };

    $linkClass = match ($density) {
        'compact' => 'inline-flex min-w-0 items-center justify-'.$align.' text-white no-underline',
        default => 'flex w-full min-w-0 justify-center px-1 text-white no-underline',
    };
@endphp

<a
    href="{{ route('patient.home') }}"
    wire:navigate
    class="{{ $linkClass }}"
    title="{{ __('patient.brand') }}"
>
    @include('partials.patient-brand-logo', [
        'svgClass' => $logoImgClass,
        'onGreenChrome' => true,
    ])
</a>
