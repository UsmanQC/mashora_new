@php
    $variant = $variant ?? 'chrome';
    $showLabel = $showLabel ?? false;

    $activeClasses = match ($variant) {
        'chrome' => 'bg-[#1565c0] text-white shadow-sm',
        'header', 'sidebar' => 'bg-[#132A6E] text-white shadow-sm ring-1 ring-white/20',
        default => 'bg-[#1565c0] text-white shadow-sm',
    };

    $inactiveClasses = match ($variant) {
        'chrome' => 'bg-[#1565c0]/10 text-[#1565c0] hover:bg-[#1565c0]/15',
        'header', 'sidebar' => 'bg-white/15 text-white hover:bg-white/25',
        default => 'bg-[#1565c0]/10 text-[#1565c0] hover:bg-[#1565c0]/15',
    };

    $pillClass = $variant === 'sidebar'
        ? 'rounded-full px-3 py-1 text-xs font-semibold transition hover:opacity-90'
        : 'rounded-full px-2.5 py-1 text-xs font-semibold transition hover:opacity-90';

    $wrapperClass = $variant === 'sidebar'
        ? 'flex flex-wrap gap-2'
        : 'flex shrink-0 items-center gap-1';
@endphp

@if ($showLabel)
    <p class="pb-2 text-[0.65rem] font-semibold uppercase tracking-wider text-white/55">
        {{ __('patient.menu.language') }}
    </p>
@endif

<div
    class="{{ $wrapperClass }}"
    role="group"
    aria-label="{{ __('patient.menu.language_aria') }}"
    @if ($variant === 'chrome' || $variant === 'header') data-test="patient-navbar-language-switch" @endif
>
    <a
        href="{{ route('patient.locale', ['locale' => 'en']) }}"
        wire:navigate="false"
        @class([$pillClass, app()->getLocale() === 'en' ? $activeClasses : $inactiveClasses])
    >
        {{ __('patient.menu.locale_en') }}
    </a>
    <a
        href="{{ route('patient.locale', ['locale' => 'ar']) }}"
        wire:navigate="false"
        @class([$pillClass, app()->getLocale() === 'ar' ? $activeClasses : $inactiveClasses])
    >
        {{ __('patient.menu.locale_ar_short') }}
    </a>
</div>
