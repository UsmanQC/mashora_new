@props([
    'title',
    'subtitle' => null,
    'profileUrl' => null,
    'profilePhotoUrl' => null,
    'userName' => null,
    'testId' => null,
])

@php
    $profileHref = $profileUrl ?? (auth()->check() ? route('profile.edit') : route('patient.phone'));
    $resolvedUserName = $userName ?? auth()->user()?->name ?? '';
@endphp

<header
    class="patient-luxury-page-header sticky top-0 z-40 border-b border-slate-200/50 bg-slate-50/80 px-6 pb-4 pt-[max(2.25rem,env(safe-area-inset-top))] backdrop-blur-xl"
    @if ($testId) data-test="{{ $testId }}" @endif
>
    <div class="flex items-center justify-between gap-4">
        <div class="min-w-0 flex-1">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $title }}</h1>
            @if (filled($subtitle))
                <p class="mt-0.5 text-xs font-medium text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>

        <div class="flex shrink-0 items-center gap-2">
            @include('partials.patient-language-switch', ['variant' => 'luxury'])

            <a
                href="{{ $profileHref }}"
                wire:navigate
                class="patient-luxury-page-header__avatar active-scale shrink-0 overflow-hidden rounded-full bg-white shadow-sm ring-2 ring-white"
                aria-label="{{ __('patient.nav.my_account') }}"
                data-test="patient-luxury-page-header-avatar"
            >
                @if (filled($profilePhotoUrl))
                    <img src="{{ $profilePhotoUrl }}" alt="" class="size-10 object-cover" />
                @else
                    <flux:avatar :name="$resolvedUserName" circle class="size-10" />
                @endif
            </a>
        </div>
    </div>
</header>
