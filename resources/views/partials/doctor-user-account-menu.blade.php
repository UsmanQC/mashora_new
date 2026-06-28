@php
    $density = $density ?? 'chrome';
    $doctor = auth('doctor')->user();
    $displayName = $doctor?->displayName() ?? '';

    $initials = collect(explode(' ', trim($displayName)))
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
            :aria-label="__('doctor.account_menu_aria')"
            data-test="doctor-account-menu-button"
            class="size-9! rounded-full! border border-zinc-200/90! bg-[#047857]! p-0! text-xs! font-semibold! text-white! shadow-sm! hover:border-zinc-300! hover:bg-[#10B981]!"
        >
            <span aria-hidden="true">{{ $initials !== '' ? $initials : 'D' }}</span>
        </flux:button>
    @else
        <flux:button
            variant="ghost"
            size="sm"
            icon="user"
            type="button"
            :aria-label="__('doctor.account_menu_aria')"
            data-test="doctor-account-menu-button"
            @class([
                'w-full justify-start text-white hover:!bg-[#047857] hover:!text-white active:!bg-[#047857] active:!text-white' => $density === 'sidebar',
                'max-w-[11rem] text-white hover:bg-white/15 hover:!text-white [&]:text-white' => $density === 'header',
            ])
        >
            @if ($density === 'header')
                <span class="truncate">{{ \Illuminate\Support\Str::limit($displayName, 16) }}</span>
            @elseif ($density === 'sidebar')
                {{ $displayName }}
            @endif
        </flux:button>
    @endif

    <flux:menu @class(['min-w-[12rem]' => $density === 'chrome'])>
        <flux:menu.item :href="route('doctor.settings.profile')" icon="user" wire:navigate data-test="doctor-personal-profile-link">
            {{ __('doctor.settings.personal_profile') }}
        </flux:menu.item>

        <flux:menu.separator />

        <div class="px-2 py-2">
            @include('partials.doctor-language-switch', ['variant' => 'menu'])
        </div>

        <flux:menu.separator />

        <form method="POST" action="{{ route('doctor.logout') }}" class="w-full">
            @csrf
            <flux:menu.item
                as="button"
                type="submit"
                icon="arrow-right-start-on-rectangle"
                class="w-full cursor-pointer"
                data-test="doctor-logout-button"
            >
                {{ __('doctor.auth.sign_out') }}
            </flux:menu.item>
        </form>
    </flux:menu>
</flux:dropdown>
