@php
    $variant = $variant ?? 'chrome';
    $showLabel = $showLabel ?? false;

    $activeClasses = match ($variant) {
        'chrome' => 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80',
        'header', 'sidebar' => 'bg-[#047857] text-white shadow-sm ring-1 ring-white/20',
        default => 'bg-[#10B981] text-white shadow-sm',
    };

    $inactiveClasses = match ($variant) {
        'chrome' => 'text-slate-600 hover:text-slate-900',
        'header', 'sidebar' => 'bg-white/15 text-white hover:bg-white/25',
        default => 'bg-[#10B981]/10 text-[#10B981] hover:bg-[#10B981]/15',
    };

    $pillClass = match ($variant) {
        'sidebar' => 'rounded-full px-3 py-1 text-xs font-semibold transition hover:opacity-90',
        'chrome' => 'rounded-md px-2.5 py-1.5 text-[0.6875rem] font-semibold uppercase tracking-[0.08em] transition',
        default => 'rounded-full px-2.5 py-1 text-xs font-semibold transition hover:opacity-90',
    };

    $wrapperClass = match ($variant) {
        'sidebar' => 'flex flex-wrap gap-2',
        'chrome' => 'inline-flex shrink-0 items-center gap-0.5 rounded-lg border border-slate-200/90 bg-slate-100/90 p-0.5',
        default => 'flex shrink-0 items-center gap-1',
    };
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
