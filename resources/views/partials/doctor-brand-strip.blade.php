{{-- Doctor portal: Mashora logo on blue chrome — same asset/filter pattern as patient-brand-strip. --}}
@php
    /** @var 'compact'|'sidebar' */
    $density ??= 'sidebar';

    /** @var string Home link (e.g. dashboard or welcome for guests). */
    $href ??= route('doctor.dashboard');

    $logoImgClass = match ($density) {
        'compact' => 'h-8 w-auto max-w-[min(100%,11rem)] object-contain object-start',
        default => 'h-11 w-auto max-w-[min(100%,12.5rem)] object-contain object-start',
    };
@endphp

<a
    href="{{ $href }}"
    wire:navigate
    class="flex w-full min-w-0 justify-start px-1 text-white no-underline"
    title="{{ __('patient.brand') }}"
    aria-label="{{ __('patient.brand') }}"
>
    @include('partials.patient-brand-logo', ['imgClass' => $logoImgClass])
</a>
