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

<flux:dropdown position="bottom" align="start" class="min-w-0">
    <button
        type="button"
        @class([
            'active-scale text-start',
            'flex min-w-0 items-center gap-3' => $variant === 'home',
            'patient-luxury-page-header__avatar shrink-0 overflow-hidden rounded-full bg-white shadow-sm ring-2 ring-white' => $variant === 'avatar',
        ])
        aria-label="{{ __('patient.account_menu_aria') }}"
        data-test="{{ $testId }}-trigger"
    >
        @if ($variant === 'home')
            <span class="relative shrink-0">
                @if (filled($profilePhotoUrl))
                    <img
                        src="{{ $profilePhotoUrl }}"
                        alt=""
                        class="size-11 rounded-full object-cover shadow-sm ring-2 ring-white"
                    />
                @else
                    <flux:avatar :name="$resolvedUserName" circle class="size-11 ring-2 ring-white" />
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
                <img src="{{ $profilePhotoUrl }}" alt="" class="size-10 object-cover" />
            @else
                <flux:avatar :name="$resolvedUserName" circle class="size-10" />
            @endif
        @endif
    </button>

    <flux:menu class="min-w-[11rem]">
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <flux:menu.item
                as="button"
                type="submit"
                icon="arrow-right-start-on-rectangle"
                class="w-full cursor-pointer"
                data-test="patient-logout-button"
            >
                {{ __('patient.menu.sign_out') }}
            </flux:menu.item>
        </form>
    </flux:menu>
</flux:dropdown>
