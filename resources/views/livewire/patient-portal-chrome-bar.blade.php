<div @if ($user) wire:poll.60s @endif>
    @if ($user)
        @php
            $firstName = \Illuminate\Support\Str::trim(\Illuminate\Support\Str::before($user->name, ' ') ?: $user->name);
        @endphp

        <header
            class="sticky top-0 z-30 border-b border-slate-200/90 bg-white/95 backdrop-blur-md supports-[backdrop-filter]:bg-white/85 lg:static lg:z-auto lg:shadow-none"
        >
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 xl:px-6">
                <div class="min-w-0 flex-1">
                    <p class="text-[0.6875rem] font-semibold uppercase tracking-[0.14em] text-slate-500">
                        {{ __('patient.portal_greeting_label') }}
                    </p>
                    <p class="truncate text-base font-semibold text-slate-900 lg:text-[1.0625rem]">
                        {{ $firstName }}
                    </p>
                </div>

                <div
                    class="flex shrink-0 items-center gap-1.5 sm:gap-2"
                    role="toolbar"
                    aria-label="{{ __('patient.portal_toolbar_aria') }}"
                >
                    @include('partials.patient-language-switch', ['variant' => 'chrome'])

                    <span class="mx-0.5 hidden h-7 w-px bg-slate-200 sm:inline" aria-hidden="true"></span>

                    <flux:button
                        type="button"
                        variant="ghost"
                        size="sm"
                        icon="heart"
                        wire:click="openMoodPicker"
                        :aria-label="__('patient.mood_check_in_aria')"
                        class="hidden rounded-lg! border border-slate-200/90 bg-slate-50/90! px-2.5! py-2! text-slate-700! shadow-none! hover:border-slate-300! hover:bg-white! sm:inline-flex sm:px-3!"
                    >
                        <span class="hidden md:inline">{{ __('patient.mood_feeling_cta') }}</span>
                    </flux:button>

                    <flux:button
                        type="button"
                        variant="ghost"
                        size="sm"
                        icon="heart"
                        wire:click="openMoodPicker"
                        :aria-label="__('patient.mood_check_in_aria')"
                        class="inline-flex size-9! rounded-lg! border border-slate-200/90 bg-slate-50/90! p-0! text-slate-700! shadow-none! hover:border-slate-300! hover:bg-white! sm:hidden"
                    ></flux:button>

                    <span class="mx-0.5 hidden h-7 w-px bg-slate-200 sm:inline" aria-hidden="true"></span>

                    <flux:dropdown position="bottom" align="end">
                        <div class="relative">
                            <flux:button
                                type="button"
                                variant="ghost"
                                size="sm"
                                icon="bell"
                                class="size-9! rounded-lg! border border-transparent! p-0! text-slate-600! hover:border-slate-200/90! hover:bg-slate-50! hover:text-slate-900! [&_[data-slot=icon]]:!size-[1.125rem] [&_[data-slot=icon]]:!text-current"
                                :aria-label="__('patient.notifications_aria')"
                            ></flux:button>
                            @if ($this->unreadNotificationCount > 0)
                                <span
                                    class="pointer-events-none absolute end-0.5 top-0.5 flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-rose-600 px-1 text-[0.625rem] leading-none font-semibold text-white ring-2 ring-white"
                                    aria-hidden="true"
                                >
                                    {{ $this->unreadNotificationCount > 99 ? '99+' : $this->unreadNotificationCount }}
                                </span>
                            @endif
                        </div>

                        <flux:menu class="min-w-[18rem] max-w-sm">
                            <div class="border-b border-zinc-100 px-3 py-2.5">
                                <p class="text-sm font-semibold text-zinc-900">{{ __('patient.menu.notifications') }}</p>
                                @if ($this->unreadNotificationCount > 0)
                                    <p class="mt-0.5 text-xs text-zinc-500">
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
                                            <span class="text-sm font-semibold text-zinc-900">{{ $notification->displayTitle() }}</span>
                                            @if ($notification->read_at === null)
                                                <span class="mt-1 size-2 shrink-0 rounded-full bg-sky-500" aria-hidden="true"></span>
                                            @endif
                                        </span>
                                        <span class="mt-1 block line-clamp-2 text-xs text-zinc-600">{{ $notification->displayMessage() }}</span>
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
        </header>
    @endif
</div>
