@php
    $variant = $variant ?? 'chrome';
    $showLabel = $showLabel ?? false;

    $activeClasses = match ($variant) {
        'chrome' => 'bg-white text-zinc-900 shadow-sm ring-1 ring-zinc-200/80',
        'header', 'sidebar' => 'bg-[#047857] text-white shadow-sm ring-1 ring-white/20',
        'guest' => 'bg-[#10B981]/10 text-[#10B981] ring-1 ring-[#10B981]/20',
        default => 'bg-[#10B981] text-white shadow-sm',
    };

    $inactiveClasses = match ($variant) {
        'chrome' => 'text-zinc-600 hover:text-zinc-900',
        'header', 'sidebar' => 'bg-white/15 text-white hover:bg-white/25',
        'guest' => 'text-zinc-600 hover:text-zinc-900',
        default => 'bg-[#10B981]/10 text-[#10B981] hover:bg-[#10B981]/15',
    };

    $pillClass = match ($variant) {
        'sidebar' => 'rounded-full px-3 py-1 text-xs font-semibold transition hover:opacity-90',
        'chrome', 'guest' => 'rounded-md px-2.5 py-1.5 text-[0.6875rem] font-semibold uppercase tracking-[0.08em] transition',
        default => 'rounded-full px-2.5 py-1 text-xs font-semibold transition hover:opacity-90',
    };

    $wrapperClass = match ($variant) {
        'sidebar' => 'flex flex-wrap gap-2',
        'chrome', 'guest' => 'inline-flex shrink-0 items-center gap-0.5 rounded-lg border border-zinc-200/90 bg-zinc-100/90 p-0.5',
        default => 'flex shrink-0 items-center gap-1',
    };
@endphp

@if ($showLabel)
    <p class="pb-2 text-[0.65rem] font-semibold uppercase tracking-wider text-white/55">
        {{ __('doctor.language.label') }}
    </p>
@endif

<div
    class="{{ $wrapperClass }}"
    role="group"
    aria-label="{{ __('doctor.language.aria') }}"
    @if ($variant === 'chrome' || $variant === 'header') data-test="doctor-navbar-language-switch" @endif
>
    <a
        href="{{ route('doctor.locale', ['locale' => 'en']) }}"
        wire:navigate="false"
        @class([$pillClass, app()->getLocale() === 'en' ? $activeClasses : $inactiveClasses])
    >
        {{ __('doctor.language.locale_en') }}
    </a>
    <a
        href="{{ route('doctor.locale', ['locale' => 'ar']) }}"
        wire:navigate="false"
        @class([$pillClass, app()->getLocale() === 'ar' ? $activeClasses : $inactiveClasses])
    >
        {{ __('doctor.language.locale_ar_short') }}
    </a>
</div>
