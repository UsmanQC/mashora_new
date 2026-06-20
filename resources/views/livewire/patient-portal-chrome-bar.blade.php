@php
    use Illuminate\Support\Str;
@endphp

<div @if ($user) wire:poll.60s @endif>
    @if ($user)
        <div
            class="sticky top-0 z-30 border-b border-zinc-200/90 bg-zinc-50 px-4 py-3 text-[#1565c0] shadow-sm lg:static lg:z-auto lg:border-zinc-200/80 lg:shadow-none xl:px-6"
        >
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4">
                <p class="min-w-0 truncate text-base font-semibold lg:text-lg">
                    {{ __('patient.portal_greeting', ['name' => Str::trim(Str::before($user->name, ' ') ?: $user->name)]) }}
                </p>

                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    @include('partials.patient-language-switch', ['variant' => 'chrome'])

                    <flux:button
                        type="button"
                        variant="filled"
                        size="sm"
                        wire:click="openMoodPicker"
                        class="rounded-full! border-[#1565c0]/25! bg-white! px-4! py-2! font-semibold text-[#1565c0]! hover:bg-[#1565c0]/5!"
                    >
                        {{ __('patient.mood_feeling_cta') }}
                    </flux:button>

                    <flux:dropdown position="bottom" align="end">
                        <div class="relative">
                            <flux:button
                                type="button"
                                variant="ghost"
                                icon="bell"
                                class="text-[#1565c0]! [&_[data-slot=icon]]:!text-current"
                                :aria-label="__('patient.notifications_aria')"
                            ></flux:button>
                            @if ($this->unreadNotificationCount > 0)
                                <span
                                    class="pointer-events-none absolute -end-0.5 -top-1 flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[0.65rem] leading-none font-semibold text-white ring-2 ring-zinc-50"
                                    aria-hidden="true"
                                >
                                    {{ $this->unreadNotificationCount > 99 ? '99+' : $this->unreadNotificationCount }}
                                </span>
                            @endif
                        </div>

                        <flux:menu class="min-w-[18rem] max-w-sm">
                            <div class="border-b border-zinc-100 px-3 py-2">
                                <p class="text-sm font-semibold text-zinc-900">{{ __('patient.menu.notifications') }}</p>
                                @if ($this->unreadNotificationCount > 0)
                                    <p class="text-xs text-zinc-500">
                                        {{ trans_choice('patient.notifications.unread_count', $this->unreadNotificationCount, ['count' => $this->unreadNotificationCount]) }}
                                    </p>
                                @endif
                            </div>

                            @forelse ($this->recentNotifications as $notification)
                                <flux:menu.item
                                    as="button"
                                    type="button"
                                    wire:click="openNotification({{ $notification->id }})"
                                    class="!items-start !whitespace-normal !py-3 text-start"
                                >
                                    <span class="block w-full">
                                        <span class="flex items-start justify-between gap-2">
                                            <span class="text-sm font-semibold text-zinc-900">{{ $notification->title }}</span>
                                            @if ($notification->read_at === null)
                                                <span class="mt-1 size-2 shrink-0 rounded-full bg-sky-500" aria-hidden="true"></span>
                                            @endif
                                        </span>
                                        <span class="mt-1 block line-clamp-2 text-xs text-zinc-600">{{ $notification->message }}</span>
                                        <span class="mt-1 block text-[0.65rem] text-zinc-400">{{ $notification->created_at?->diffForHumans() }}</span>
                                    </span>
                                </flux:menu.item>
                            @empty
                                <div class="px-3 py-4 text-center text-sm text-zinc-500">
                                    {{ __('patient.notifications.empty') }}
                                </div>
                            @endforelse

                            <flux:menu.separator />

                            <flux:menu.item :href="route('patient.notifications')" icon="bell" wire:navigate>
                                {{ __('patient.notifications.view_all') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>

                    @include('partials.patient-user-account-menu', ['density' => 'chrome'])
                </div>
            </div>
        </div>
    @endif
</div>
