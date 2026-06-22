{{-- Text brand link for blue chrome (see patient-brand-logo.blade.php). --}}
@php
    /** @var 'compact'|'sidebar' */
    $density ??= 'sidebar';

    $logoImgClass = match ($density) {
        'compact' => 'h-8 w-auto max-w-[min(100%,11rem)] object-contain object-start',
        default => 'h-11 w-auto max-w-[min(100%,12.5rem)] object-contain object-start',
    };
@endphp

<a
    href="{{ route('patient.home') }}"
    wire:navigate
    class="flex w-full min-w-0 justify-start px-1 text-white no-underline"
    title="{{ __('patient.brand') }}"
>
    @include('partials.patient-brand-logo', ['imgClass' => $logoImgClass])
</a>
