{{-- Text brand mark used across patient and doctor chrome. --}}
@php
    /** @var string Tailwind classes for sizing (legacy imgClass callers). */
    $imgClass ??= 'h-10 w-auto max-w-[min(100%,13rem)] object-contain object-start';

    /** If true, use white text on blue chrome backgrounds. */
    $whiteOnBlue ??= true;

    $textSizeClass = match (true) {
        str_contains($imgClass, 'h-11') => 'text-xl',
        str_contains($imgClass, 'h-8') => 'text-base',
        str_contains($imgClass, 'h-9') => 'text-lg',
        default => 'text-lg',
    };

    $colorClass = $whiteOnBlue ? 'text-white' : 'text-zinc-900';
@endphp
<span class="{{ trim($colorClass.' '.$textSizeClass.' font-bold tracking-tight') }}">
    {{ __('patient.brand') }}
</span>
