@php
    $density = $density ?? 'sidebar';

    $initials = collect(explode(' ', trim((string) auth()->user()->name)))
        ->filter()
        ->take(2)
        ->map(static fn (string $part): string => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->join('');
@endphp

<flux:dropdown position="bottom" align="{{ $density === 'chrome' ? 'end' : 'start' }}" @class(['w-full' => $density === 'sidebar'])>
    @if ($density === 'chrome')
        <flux:button
            variant="ghost"
            size="sm"
            type="button"
            :aria-label="__('patient.account_menu_aria')"
            data-test="patient-account-menu-button"
            class="size-9! rounded-full! border border-slate-200/90! bg-[#0B163E]! p-0! text-xs! font-semibold! text-white! shadow-sm! hover:border-slate-300! hover:bg-[#132A6E]!"
        >
            <span aria-hidden="true">{{ $initials !== '' ? $initials : 'U' }}</span>
        </flux:button>
    @else
        <flux:button
            variant="ghost"
            size="sm"
            icon="user"
            type="button"
            :aria-label="$density === 'header' ? null : null"
            @class([
                'w-full justify-start text-white hover:!bg-[#132A6E] hover:!text-white active:!bg-[#132A6E] active:!text-white' => $density === 'sidebar',
                'max-w-[11rem] text-white hover:bg-white/15 hover:!text-white [&]:text-white' => $density === 'header',
            ])
        >
            @if ($density === 'header')
                <span class="truncate">{{ \Illuminate\Support\Str::limit(auth()->user()->name, 16) }}</span>
            @elseif ($density === 'sidebar')
                {{ auth()->user()->name }}
            @endif
        </flux:button>
    @endif

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
