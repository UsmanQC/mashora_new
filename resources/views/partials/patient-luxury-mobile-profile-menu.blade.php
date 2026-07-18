@props([
    'profilePhotoUrl' => null,
    'userName' => '',
    'greeting' => null,
    'variant' => 'home',
    'testId' => 'patient-luxury-profile-menu',
])

@php
    $resolvedUserName = (string) $userName;
@endphp

<a
    href="{{ route('patient.menu') }}"
    wire:navigate
    aria-label="{{ __('patient.nav.my_account') }}"
    data-test="{{ $testId }}-trigger"
    @class([
        'active-scale text-start no-underline',
        'patient-luxury-page-header__avatar inline-block size-10 shrink-0 overflow-hidden rounded-full bg-white ring-2 ring-white' => $variant === 'avatar',
        'flex min-w-0 items-center gap-3' => $variant === 'home',
    ])
>
    @if ($variant === 'home')
        <span class="relative shrink-0">
            @if (filled($profilePhotoUrl))
                <img
                    src="{{ $profilePhotoUrl }}"
                    alt=""
                    class="size-10 rounded-full object-cover ring-2 ring-white"
                />
            @else
                <flux:avatar :name="$resolvedUserName" circle class="size-10 ring-2 ring-white" />
            @endif
            <span class="absolute bottom-0 end-0 size-3 rounded-full border-2 border-white bg-[#10B981]" aria-hidden="true"></span>
        </span>
        <span class="min-w-0">
            @if (filled($greeting))
                <span class="mb-0.5 block text-[0.6875rem] font-medium text-slate-500">{{ $greeting }}</span>
            @endif
            <span class="block truncate text-base font-bold tracking-tight text-slate-900">{{ $resolvedUserName }}</span>
        </span>
    @else
        @if (filled($profilePhotoUrl))
            <img src="{{ $profilePhotoUrl }}" alt="" class="size-full object-cover" />
        @else
            <flux:avatar :name="$resolvedUserName" circle class="size-10" />
        @endif
    @endif
</a>
