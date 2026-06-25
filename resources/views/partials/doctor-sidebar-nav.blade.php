@php
    use App\Support\DoctorMenu;

    $activeDash = request()->routeIs('doctor.dashboard');
    $sections = DoctorMenu::sections();
@endphp

<flux:button
    :href="route('doctor.dashboard')"
    wire:navigate
    variant="ghost"
    class="w-full justify-start !text-white hover:!bg-white/10 {{ $activeDash ? '!bg-[#047857] !text-white [&_svg]:!text-white' : '' }}"
    icon="home"
>
    {{ __('doctor.nav.dashboard') }}
</flux:button>

@foreach ($sections as $section)
    <div class="mt-4 first:mt-2">
        <p class="mb-1.5 px-3 text-[0.65rem] font-semibold uppercase tracking-wider text-white/55">
            {{ $section['heading'] }}
        </p>
        <div class="flex flex-col gap-0.5">
            @foreach ($section['items'] as $item)
                @if ($item['available'])
                    @php($isActive = DoctorMenu::isRouteActive($item['route']))
                    <flux:button
                        :href="route($item['route'])"
                        wire:navigate
                        variant="ghost"
                        class="w-full justify-start !text-white hover:!bg-white/10 {{ $isActive ? '!bg-[#047857] !text-white [&_svg]:!text-white' : '' }}"
                        :icon="$item['icon']"
                    >
                        {{ $item['label'] }}
                    </flux:button>
                @endif
            @endforeach
        </div>
    </div>
@endforeach