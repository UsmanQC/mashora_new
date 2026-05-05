@php
    /** @var 'horizontal'|'vertical' */
    $orientation ??= 'horizontal';
    /** @var 'default'|'legacy' Matches legacy patient portal (blue bar, navy active pill) */
    $theme ??= 'default';

    $isVertical = $orientation === 'vertical';
    $isLegacy = $theme === 'legacy';

    $guest = ! auth()->check();
    $phoneEntry = route('patient.phone');

    if ($isLegacy) {
        $dockButtonClass = $isVertical
            ? 'w-full shrink-0 justify-start gap-3 px-3 py-2.5 text-sm [--flux-button-icon-size:1.35rem]'
            : 'flex-1 shrink flex-col gap-0.5 py-2 text-[0.6875rem] [--flux-button-icon-size:1.35rem]';

        /** Selected/current route — solid #0B163E overrides Flux ghost (bg-transparent + zinc hover/text). */
        $dockActiveClass = 'rounded-lg !border-0 !bg-[#0B163E] !text-white shadow-sm ring-0 hover:!bg-[#0B163E] hover:!text-white active:!bg-[#080f34] dark:!border-0 dark:!bg-[#0B163E] dark:!text-white dark:hover:!bg-[#0B163E] [&_svg]:!text-white';
        $dockInactiveClass = $isVertical
            ? 'rounded-lg !border-0 !bg-transparent !font-medium !text-white ring-0 hover:!bg-white/12 hover:!text-white active:!bg-[#0B163E] active:!text-white dark:!bg-transparent dark:!text-white dark:hover:!bg-white/12'
            : '!border-0 !bg-transparent !text-white ring-0 hover:!bg-white/18 hover:!text-white active:!bg-[#0B163E] active:!text-white dark:!text-white dark:hover:!bg-white/18';
    } else {
        $dockButtonClass = $isVertical
            ? 'w-full shrink-0 justify-start gap-3 px-3 py-2.5 text-sm [--flux-button-icon-size:1.35rem]'
            : 'flex-1 shrink flex-col gap-0.5 py-2 text-xs [--flux-button-icon-size:1.35rem]';
        $dockActiveClass = 'bg-amber-50 text-amber-900 ring-1 ring-amber-200/80';
        $dockInactiveClass = $isVertical ? 'text-zinc-600 hover:bg-zinc-50' : 'text-zinc-600';
    }

    $menuHref = auth()->check() ? route('patient.menu') : $phoneEntry;

    $hrefHome = $guest ? $phoneEntry : route('patient.home');
    $hrefAppointments = $guest ? $phoneEntry : route('patient.appointments');
    $hrefNumbers = $guest ? $phoneEntry : route('patient.important-numbers');

    $activeHome = request()->routeIs('patient.home');
    $activeAppointments = ! $guest && request()->routeIs(['patient.appointments', 'patient.schedule.filter', 'patient.schedule.specialists']);
    $activeNumbers = ! $guest && request()->routeIs('patient.important-numbers');
    $activeMenu = ! $guest && request()->routeIs([
        'patient.menu',
        'profile.edit',
    ]);

    $dockClassHome = $dockButtonClass.' '.($activeHome ? $dockActiveClass : $dockInactiveClass);
    $dockClassAppt = $dockButtonClass.' '.($activeAppointments ? $dockActiveClass : $dockInactiveClass);
    $dockClassMenu = $dockButtonClass.' '.($activeMenu ? $dockActiveClass : $dockInactiveClass);
    $dockClassNumbers = $dockButtonClass.' '.($activeNumbers ? $dockActiveClass : $dockInactiveClass);

    $wrapperClass = match (true) {
        $orientation === 'vertical' => 'flex flex-col gap-1',
        $theme === 'legacy' => 'flex w-full shrink-0 justify-between gap-0 px-0.5',
        default => 'mx-auto flex max-w-4xl justify-between gap-1',
    };
@endphp

<div {{ $attributes->class([$wrapperClass]) }}>
    <flux:button href="{{ $hrefHome }}" wire:navigate variant="ghost" icon="home" class="{{ $dockClassHome }}">
        {{ __('patient.nav.home') }}
    </flux:button>

    <flux:button
        href="{{ $hrefAppointments }}"
        wire:navigate
        variant="ghost"
        icon="calendar-days"
        class="{{ $dockClassAppt }}"
    >
        {{ __('patient.nav.appointments') }}
    </flux:button>

    <flux:button href="{{ $menuHref }}" wire:navigate variant="ghost" icon="squares-2x2" class="{{ $dockClassMenu }}">
        {{ __('patient.nav.menu') }}
    </flux:button>

    <flux:button
        href="{{ $hrefNumbers }}"
        wire:navigate
        variant="ghost"
        icon="phone"
        class="{{ $dockClassNumbers }}"
    >
        {{ __('patient.nav.important_numbers') }}
    </flux:button>
</div>
