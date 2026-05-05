@props([
    'density' => 'sidebar',
])

<flux:dropdown position="bottom" align="start" @class(['w-full' => $density === 'sidebar'])>
    <flux:button
        variant="ghost"
        size="sm"
        icon="user"
        type="button"
        @class([
            'w-full justify-start text-white hover:bg-white/10 hover:!text-white active:bg-[#0B163E] active:!text-white' => $density === 'sidebar',
            'max-w-[11rem] text-white hover:bg-white/15 hover:!text-white [&]:text-white' => $density === 'header',
        ])
    >
        @if ($density === 'header')
            <span class="truncate">{{ \Illuminate\Support\Str::limit(auth()->user()->name, 16) }}</span>
        @else
            {{ auth()->user()->name }}
        @endif
    </flux:button>

    <flux:menu>
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
