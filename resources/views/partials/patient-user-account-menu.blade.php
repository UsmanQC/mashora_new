@php
    $density = $density ?? 'sidebar';
@endphp

<flux:dropdown position="bottom" align="{{ $density === 'chrome' ? 'end' : 'start' }}" @class(['w-full' => $density === 'sidebar'])>
    <flux:button
        variant="ghost"
        size="sm"
        icon="user"
        type="button"
        :aria-label="$density === 'chrome' ? __('patient.account_menu_aria') : null"
        data-test="{{ $density === 'chrome' ? 'patient-account-menu-button' : '' }}"
        @class([
            'w-full justify-start text-white hover:!bg-[#132A6E] hover:!text-white active:!bg-[#132A6E] active:!text-white' => $density === 'sidebar',
            'max-w-[11rem] text-white hover:bg-white/15 hover:!text-white [&]:text-white' => $density === 'header',
            'text-[#1565c0]! [&_[data-slot=icon]]:!text-current' => $density === 'chrome',
        ])
    >
        @if ($density === 'header')
            <span class="truncate">{{ \Illuminate\Support\Str::limit(auth()->user()->name, 16) }}</span>
        @elseif ($density === 'sidebar')
            {{ auth()->user()->name }}
        @endif
    </flux:button>

    <flux:menu @class(['min-w-[12rem]' => $density === 'chrome'])>
        <flux:menu.item :href="route('profile.edit')" icon="user" wire:navigate>
            {{ __('patient.menu.personal_profile') }}
        </flux:menu.item>

        <flux:menu.separator />

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
