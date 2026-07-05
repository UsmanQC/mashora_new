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

<flux:dropdown
    position="bottom"
    align="{{ $variant === 'avatar' ? 'end' : 'start' }}"
    class="min-w-0"
>
    <flux:button
        variant="ghost"
        size="sm"
        type="button"
        :square="$variant === 'avatar'"
        :aria-label="__('patient.account_menu_aria')"
        data-test="{{ $testId }}-trigger"
        @class([
            'active-scale text-start',
            'patient-luxury-page-header__avatar !size-10 !min-h-0 shrink-0 overflow-hidden !rounded-full !border-0 !bg-white !p-0 !shadow-sm ring-2 ring-white hover:!bg-white' => $variant === 'avatar',
            '!h-auto !min-h-0 !justify-start !gap-3 !rounded-none !border-0 !bg-transparent !p-0 !shadow-none hover:!bg-transparent active:!bg-transparent' => $variant === 'home',
        ])
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
                <img src="{{ $profilePhotoUrl }}" alt="" class="size-full object-cover" />
            @else
                <flux:avatar :name="$resolvedUserName" circle class="size-10" />
            @endif
        @endif
    </flux:button>

    <flux:menu class="min-w-[11rem]">
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <flux:menu.item
                as="button"
                type="submit"
                icon="arrow-right-start-on-rectangle"
                icon:variant="outline"
                variant="danger"
                class="w-full cursor-pointer"
                data-test="patient-logout-button"
            >
                {{ __('patient.menu.sign_out') }}
            </flux:menu.item>
        </form>
    </flux:menu>
</flux:dropdown>
