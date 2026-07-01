@php
    $authBack ??= \App\Support\PatientAuthBackNavigation::resolve();
@endphp

@if ($authBack !== null)
    <a
        href="{{ $authBack['url'] }}"
        @if (\App\Support\PatientPageNavigation::usesLivewireNavigate($authBack['url'])) wire:navigate @endif
        data-test="patient-auth-back"
        class="inline-flex min-h-11 min-w-11 shrink-0 items-center gap-0.5 rounded-full px-1.5 text-sm font-semibold text-[#10B981] transition hover:bg-[#10B981]/8 active:scale-[0.98]"
        aria-label="{{ $authBack['label'] }}"
        title="{{ $authBack['label'] }}"
    >
        <flux:icon name="chevron-left" variant="mini" class="size-5 shrink-0 rtl:rotate-180" />
        <span class="max-w-[5.5rem] truncate sm:max-w-none">{{ $authBack['label'] }}</span>
    </a>
@else
    <span class="min-w-11 shrink-0" aria-hidden="true"></span>
@endif
