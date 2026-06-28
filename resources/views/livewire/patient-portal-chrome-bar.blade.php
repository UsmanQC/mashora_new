<div @if ($user) wire:poll.60s @endif>
    @if ($user)
        <header
            class="sticky top-0 z-30 border-b border-zinc-200/80 bg-white/95 px-4 py-4 backdrop-blur sm:px-6 lg:static lg:z-auto"
        >
            <div class="flex w-full flex-wrap items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-zinc-900">
                        {{ __('patient.header.welcome', ['name' => $user->name]) }}
                    </p>
                    <time class="text-sm text-zinc-500" datetime="{{ now()->toIso8601String() }}">
                        {{ now()->locale(app()->getLocale())->isoFormat('LL') }}
                    </time>
                </div>

                <div
                    class="flex shrink-0 items-center gap-2"
                    role="toolbar"
                    aria-label="{{ __('patient.portal_toolbar_aria') }}"
                >
                    <div class="hidden sm:block">
                        @include('partials.patient-language-switch', ['variant' => 'chrome'])
                    </div>

                    <flux:button
                        type="button"
                        variant="ghost"
                        size="sm"
                        wire:click="openMoodPicker"
                        :aria-label="$this->todayMoodKey
                            ? __('patient.mood_selector_options.'.$this->todayMoodKey)
                            : __('patient.mood_check_in_aria')"
                        :title="$this->todayMoodKey
                            ? __('patient.mood_selector_options.'.$this->todayMoodKey)
                            : __('patient.mood_check_in_aria')"
                        class="inline-flex size-9! shrink-0 rounded-full! border border-zinc-200/90! bg-zinc-50! p-0! text-zinc-700! shadow-none! hover:bg-zinc-100!"
                    >
                        @if ($this->todayMoodImageUrl)
                            <img
                                src="{{ $this->todayMoodImageUrl }}"
                                alt=""
                                class="size-6 object-contain"
                                decoding="async"
                            />
                        @else
                            <flux:icon name="face-smile" variant="mini" class="size-5 text-[#10B981]" />
                        @endif
                    </flux:button>

                    <flux:dropdown position="bottom" align="end">
                        <div class="relative">
                            <flux:button
                                type="button"
                                variant="ghost"
                                size="sm"
                                icon="bell"
                                class="size-9! rounded-full! border border-zinc-200/90! bg-zinc-50! p-0! text-zinc-600! shadow-none! hover:border-zinc-200! hover:bg-zinc-100! hover:text-zinc-700! [&_[data-slot=icon]]:!size-5 [&_[data-slot=icon]]:!text-current"
                                :aria-label="__('patient.notifications_aria')"
                            ></flux:button>
                            @if ($this->unreadNotificationCount > 0)
                                <span
                                    class="pointer-events-none absolute -end-1 -top-1 inline-flex min-w-4 items-center justify-center rounded-full bg-[#10B981] px-1 text-[10px] font-semibold leading-4 text-white"
                                    aria-hidden="true"
                                >
                                    {{ $this->unreadNotificationCount > 9 ? '9+' : $this->unreadNotificationCount }}
                                </span>
                            @endif
                        </div>

                        <flux:menu class="min-w-[18rem] max-w-sm !border-zinc-200 !bg-white">
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
                                                <span class="mt-1 size-2 shrink-0 rounded-full bg-[#10B981]" aria-hidden="true"></span>
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

                    <div class="hidden sm:block">
                        @include('partials.patient-user-account-menu', ['density' => 'chrome'])
                    </div>
                </div>
            </div>
        </header>
    @endif
</div>
