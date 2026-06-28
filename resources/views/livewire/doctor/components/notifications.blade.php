<div wire:poll.15s>
    <flux:dropdown position="bottom" align="end">
        <div class="relative">
            <flux:button
                type="button"
                variant="ghost"
                size="sm"
                icon="bell"
                class="size-9! rounded-full! border border-zinc-200/90! bg-zinc-50! p-0! text-zinc-600! shadow-none! hover:border-zinc-200! hover:bg-zinc-100! hover:text-zinc-700! [&_[data-slot=icon]]:!size-5 [&_[data-slot=icon]]:!text-current"
                :aria-label="__('doctor.notifications.aria')"
            ></flux:button>
            @if ($this->unreadCount > 0)
                <span
                    class="pointer-events-none absolute -end-1 -top-1 inline-flex min-w-4 items-center justify-center rounded-full bg-[#10B981] px-1 text-[10px] font-semibold leading-4 text-white"
                    aria-hidden="true"
                >
                    {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
                </span>
            @endif
        </div>

        <flux:menu class="min-w-[18rem] max-w-sm">
            <div class="border-b border-zinc-100 px-3 py-2.5">
                <p class="text-sm font-semibold text-zinc-900">{{ __('doctor.notifications.title') }}</p>
                @if ($this->unreadCount > 0)
                    <p class="mt-0.5 text-xs text-zinc-500">
                        {{ trans_choice('doctor.notifications.unread_count', $this->unreadCount, ['count' => $this->unreadCount]) }}
                    </p>
                @endif
            </div>

            @forelse ($this->notifications as $notification)
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
                    {{ __('doctor.notifications.empty') }}
                </div>
            @endforelse

            <flux:menu.separator />

            <flux:menu.item :href="route('doctor.settings.notifications')" icon="bell" wire:navigate>
                {{ __('doctor.notifications.view_all') }}
            </flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</div>
