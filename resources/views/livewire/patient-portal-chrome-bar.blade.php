@php
    use Illuminate\Support\Str;
@endphp

<div class="sticky top-0 z-30 border-b border-zinc-200/90 bg-zinc-50 px-4 py-3 text-[#1565c0] shadow-sm lg:static lg:z-auto lg:border-zinc-200/80 lg:shadow-none xl:px-6">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4">
        <p class="min-w-0 truncate text-base font-semibold lg:text-lg">
            {{ __('patient.portal_greeting', ['name' => Str::trim(Str::before($user->name, ' ') ?: $user->name)]) }}
        </p>

        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            <flux:button
                type="button"
                variant="filled"
                size="sm"
                wire:click="openMoodPicker"
                class="rounded-full! border-[#1565c0]/25! bg-white! px-4! py-2! font-semibold text-[#1565c0]! hover:bg-[#1565c0]/5!"
            >
                {{ __('patient.mood_feeling_cta') }}
            </flux:button>

            <div class="relative">
                <flux:button
                    type="button"
                    variant="ghost"
                    icon="bell"
                    class="text-[#1565c0]! [&_[data-slot=icon]]:!text-current"
                    :aria-label="__('patient.notifications_aria')"
                ></flux:button>
                @php($notificationBadge = max(0, $notificationCount))
                <span
                    class="pointer-events-none absolute -end-0.5 -top-1 flex size-5 items-center justify-center rounded-full bg-red-500 text-[0.65rem] leading-none font-semibold text-white ring-2 ring-zinc-50"
                    aria-hidden="true"
                >
                    {{ $notificationBadge > 99 ? '99+' : $notificationBadge }}
                </span>
            </div>
        </div>
    </div>
</div>
