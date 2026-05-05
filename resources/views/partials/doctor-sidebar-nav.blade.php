@php
    $activeDash = request()->routeIs('doctor.dashboard');
    $activeAppt = request()->routeIs('doctor.appointments');
    $activeRatings = request()->routeIs('doctor.ratings');
    $activeSettings = request()->routeIs('doctor.settings');
@endphp

<flux:button
    :href="route('doctor.dashboard')"
    wire:navigate
    variant="ghost"
    class="w-full justify-start !text-white hover:!bg-white/10 {{ $activeDash ? '!bg-[#132A6E] !text-white [&_svg]:!text-white' : '' }}"
    icon="home"
>
    {{ __('doctor.nav.dashboard') }}
</flux:button>
<flux:button
    :href="route('doctor.appointments')"
    wire:navigate
    variant="ghost"
    class="w-full justify-start !text-white hover:!bg-white/10 {{ $activeAppt ? '!bg-[#132A6E] !text-white [&_svg]:!text-white' : '' }}"
    icon="calendar-days"
>
    {{ __('doctor.nav.appointments') }}
</flux:button>
<flux:button
    :href="route('doctor.ratings')"
    wire:navigate
    variant="ghost"
    class="w-full justify-start !text-white hover:!bg-white/10 {{ $activeRatings ? '!bg-[#132A6E] !text-white [&_svg]:!text-white' : '' }}"
    icon="star"
>
    {{ __('doctor.nav.ratings') }}
</flux:button>
<flux:button
    :href="route('doctor.settings')"
    wire:navigate
    variant="ghost"
    class="w-full justify-start !text-white hover:!bg-white/10 {{ $activeSettings ? '!bg-[#132A6E] !text-white [&_svg]:!text-white' : '' }}"
    icon="cog-6-tooth"
>
    {{ __('doctor.nav.menu') }}
</flux:button>
