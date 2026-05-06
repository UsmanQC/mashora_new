<div wire:poll.15s class="relative">
    <details class="relative">
        <summary
            wire:click="readNotification"
            class="inline-flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-full border border-zinc-200/90 bg-zinc-50 text-zinc-600 hover:bg-zinc-100"
            title="{{ __('Notifications') }}"
        >
            <flux:icon name="bell" variant="outline" class="size-5" />
            @if ($this->unreadCount > 0)
                <span class="absolute -right-1 -top-1 inline-flex min-w-4 items-center justify-center rounded-full bg-[#3C5CF7] px-1 text-[10px] font-semibold leading-4 text-white">
                    {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
                </span>
            @endif
        </summary>

        <div class="absolute inset-e-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-zinc-100 px-3 py-2">
                <flux:text class="text-sm font-semibold text-zinc-900">{{ __('Notifications') }}</flux:text>
                <a href="{{ route('doctor.settings.notifications') }}" wire:navigate class="text-xs font-medium text-[#3C5CF7] hover:underline">
                    {{ __('View all') }}
                </a>
            </div>

            <div class="max-h-80 overflow-auto p-2">
                @forelse ($this->notifications as $notification)
                    <div @class([
                        'mb-2 rounded-lg border p-2 last:mb-0',
                        'border-blue-200 bg-blue-50/40' => ! $notification->read_at,
                        'border-zinc-200 bg-white' => $notification->read_at,
                    ])>
                        <flux:text class="text-sm font-medium text-zinc-900">{{ $notification->title ?: __('Notification') }}</flux:text>
                        @if ($notification->message)
                            <flux:text class="mt-0.5 line-clamp-2 text-xs text-zinc-600">{{ $notification->message }}</flux:text>
                        @endif
                        <flux:text class="mt-1 text-[11px] text-zinc-500">{{ $notification->created_at?->diffForHumans() }}</flux:text>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center">
                        <flux:text class="text-sm text-zinc-500">{{ __('No notifications yet.') }}</flux:text>
                    </div>
                @endforelse
            </div>
        </div>
    </details>
</div>
