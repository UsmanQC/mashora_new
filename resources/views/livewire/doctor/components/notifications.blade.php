<div wire:poll.15s>
    <flux:dropdown position="bottom" align="end">
        <div class="relative">
            <flux:button
                type="button"
                variant="ghost"
                size="sm"
                icon="bell"
                class="size-9! rounded-full! border border-zinc-200/90! bg-zinc-50! p-0! text-zinc-600! shadow-none! hover:border-[#10B981]/30! hover:bg-emerald-50/80! hover:text-[#047857]! [&_[data-slot=icon]]:!size-5 [&_[data-slot=icon]]:!text-current"
                :aria-label="__('doctor.notifications.aria')"
            ></flux:button>
            @if ($this->unreadCount > 0)
                <span
                    class="pointer-events-none absolute -end-1 -top-1 inline-flex min-w-4 items-center justify-center rounded-full bg-[#10B981] px-1 text-[10px] font-semibold leading-4 text-white shadow-sm shadow-emerald-900/20"
                    aria-hidden="true"
                >
                    {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
                </span>
            @endif
        </div>

        <flux:menu class="min-w-[20rem] max-w-sm overflow-hidden rounded-xl! border border-zinc-200/90! p-0! shadow-xl!">
            <div class="flex items-center justify-between gap-2 border-b border-zinc-100 bg-gradient-to-r from-emerald-50/80 to-white px-3 py-2.5">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-zinc-900">{{ __('doctor.notifications.title') }}</p>
                    @if ($this->unreadCount > 0)
                        <p class="mt-0.5 text-xs text-zinc-500">
                            {{ trans_choice('doctor.notifications.unread_count', $this->unreadCount, ['count' => $this->unreadCount]) }}
                        </p>
                    @endif
                </div>
                @if ($this->unreadCount > 0)
                    <button
                        type="button"
                        wire:click="readNotification"
                        class="shrink-0 text-xs font-semibold text-[#10B981] hover:text-[#047857]"
                    >
                        {{ __('doctor.notifications.mark_all_read') }}
                    </button>
                @endif
            </div>

            <div class="max-h-80 overflow-y-auto p-1.5">
                @forelse ($this->notifications as $notification)
                    <flux:menu.item
                        as="button"
                        type="button"
                        wire:click="openNotification({{ $notification->id }})"
                        class="!mx-0 !items-start !rounded-lg !whitespace-normal !py-3 text-start {{ $notification->read_at === null ? '!bg-emerald-50/50' : '' }}"
                    >
                        <span class="block w-full">
                            <span class="flex items-start justify-between gap-2">
                                <span class="text-sm font-semibold text-zinc-900">{{ $notification->displayTitle() }}</span>
                                @if ($notification->read_at === null)
                                    <span class="mt-1 size-2 shrink-0 rounded-full bg-[#10B981]" aria-hidden="true"></span>
                                @endif
                            </span>
                            <span class="mt-1 block line-clamp-2 text-xs leading-relaxed text-zinc-600">{{ $notification->displayMessage() }}</span>
                            <span class="mt-1.5 block text-[0.65rem] font-medium text-zinc-400">{{ $notification->created_at?->diffForHumans() }}</span>
                        </span>
                    </flux:menu.item>
                @empty
                    <div class="px-3 py-6 text-center">
                        <flux:icon name="bell" variant="outline" class="mx-auto size-8 text-zinc-300" />
                        <p class="mt-2 text-sm text-zinc-500">{{ __('doctor.notifications.empty') }}</p>
                    </div>
                @endforelse
            </div>

            <flux:menu.separator />

            <flux:menu.item :href="route('doctor.settings.notifications')" icon="bell" wire:navigate>
                {{ __('doctor.notifications.view_all') }}
            </flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</div>
