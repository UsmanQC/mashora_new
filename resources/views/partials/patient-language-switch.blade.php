@php
    $variant = $variant ?? 'chrome';
    $showLabel = $showLabel ?? false;

    $alternateLocale = app()->getLocale() === 'ar' ? 'en' : 'ar';
    $alternateLabel = $alternateLocale === 'en'
        ? __('patient.menu.locale_en')
        : __('patient.menu.locale_ar_short');

    $singleLinkClass = match ($variant) {
        'header', 'sidebar' => 'inline-flex shrink-0 items-center justify-center rounded-full border border-white/30 bg-white/15 px-3.5 py-1.5 text-[0.6875rem] font-bold uppercase tracking-[0.06em] text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.12)] transition hover:bg-white/25',
        'luxury' => 'inline-flex shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[0.6875rem] font-bold uppercase tracking-[0.06em] text-slate-700 shadow-sm transition hover:border-emerald-200 hover:text-[#059669]',
        'chrome', 'guest' => 'inline-flex shrink-0 items-center justify-center rounded-full border border-zinc-200/90 bg-zinc-100/95 px-3.5 py-1.5 text-[0.6875rem] font-bold uppercase tracking-[0.06em] text-zinc-700 shadow-sm transition hover:bg-white',
        'menu' => 'flex w-full items-center justify-center rounded-full border border-zinc-200/90 bg-zinc-100/95 px-3.5 py-2 text-xs font-bold uppercase tracking-[0.06em] text-zinc-700 transition hover:bg-white',
        default => 'inline-flex shrink-0 items-center justify-center rounded-full border border-zinc-200/90 bg-zinc-100/95 px-3.5 py-1.5 text-xs font-bold uppercase text-zinc-700 transition hover:bg-white',
    };
@endphp

@if ($showLabel)
    <p class="pb-2 text-[0.65rem] font-semibold uppercase tracking-wider text-white/55">
        {{ __('patient.menu.language') }}
    </p>
@endif

<a
    href="{{ route('patient.locale', ['locale' => $alternateLocale]) }}"
    wire:navigate="false"
    class="{{ $singleLinkClass }}"
    role="button"
    aria-label="{{ __('patient.menu.language_aria') }}"
    @if (in_array($variant, ['chrome', 'header', 'guest', 'luxury'], true)) data-test="patient-navbar-language-switch" @endif
    @if ($variant === 'menu') data-test="patient-account-menu-language-switch" @endif
>
    {{ $alternateLabel }}
</a>
