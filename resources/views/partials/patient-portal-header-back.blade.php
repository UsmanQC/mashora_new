@php
    $portalBack ??= \App\Support\PatientPortalBackNavigation::resolve();
@endphp

@if ($portalBack !== null)
    <a
        href="{{ $portalBack['url'] }}"
        @if (\App\Support\PatientPageNavigation::usesLivewireNavigate($portalBack['url'])) wire:navigate @endif
        data-test="patient-portal-back"
        class="inline-flex min-h-10 min-w-10 shrink-0 items-center gap-0.5 rounded-full px-1 text-sm font-semibold text-white transition hover:bg-white/12 active:scale-[0.98]"
        aria-label="{{ $portalBack['label'] }}"
        title="{{ $portalBack['label'] }}"
    >
        <flux:icon name="chevron-left" variant="mini" class="size-5 shrink-0 rtl:rotate-180" />
        <span class="sr-only sm:not-sr-only sm:max-w-[5rem] sm:truncate">{{ $portalBack['label'] }}</span>
    </a>
@endif
