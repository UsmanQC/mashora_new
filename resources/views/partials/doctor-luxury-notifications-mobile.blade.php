<div
    class="doctor-luxury-notifications relative flex h-[100dvh] flex-col overflow-hidden bg-slate-50 lg:hidden"
    data-test="doctor-luxury-notifications"
>
    <header class="shrink-0 bg-gradient-to-b from-white to-slate-50 px-5 pb-4 pt-[max(2.25rem,env(safe-area-inset-top))]">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('doctor.menu') }}"
                wire:navigate
                class="flex size-10 shrink-0 items-center justify-center rounded-full border border-slate-100 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50"
                aria-label="{{ __('doctor.auth.back') }}"
            >
                <flux:icon name="chevron-left" variant="mini" class="size-5 rtl:rotate-180" />
            </a>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-bold tracking-tight text-slate-900">{{ __('doctor.notifications.title') }}</h1>
                @if ($this->unreadCount > 0)
                    <p class="mt-0.5 text-xs font-semibold text-[#047857]">
                        {{ trans_choice('doctor.notifications.unread_count', $this->unreadCount, ['count' => $this->unreadCount]) }}
                    </p>
                @endif
            </div>
            @if ($this->unreadCount > 0)
                <button
                    type="button"
                    wire:click="markAllRead"
                    class="shrink-0 rounded-full bg-[#10B981]/10 px-3 py-2 text-xs font-bold text-[#047857]"
                >
                    {{ __('doctor.notifications.mark_all_read') }}
                </button>
            @endif
        </div>
    </header>

    <main class="doctor-luxury-scroll mx-auto flex min-h-0 w-full max-w-md flex-1 flex-col overflow-y-auto overscroll-contain px-5 pb-[calc(5.5rem+env(safe-area-inset-bottom))] pt-1">
        @if ($this->notifications->isEmpty())
            <div class="rounded-3xl border border-slate-100 bg-white px-6 py-14 text-center shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <flux:icon name="bell" variant="outline" class="size-7" />
                </div>
                <p class="text-sm leading-relaxed text-slate-500">{{ __('doctor.notifications.empty') }}</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($this->notifications as $notification)
                    @php $isUnread = $notification->read_at === null; @endphp
                    <article
                        wire:key="doctor-notif-{{ $notification->id }}"
                        @class([
                            'rounded-2xl border p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] transition',
                            'border-transparent bg-[#10B981]/10' => $isUnread,
                            'border-slate-100 bg-white' => ! $isUnread,
                        ])
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-slate-900">{{ $notification->displayTitle() }}</p>
                                <p class="mt-1 text-xs leading-relaxed text-slate-600">{{ $notification->displayMessage() }}</p>
                                <p class="mt-2 text-[0.625rem] font-medium text-slate-400">{{ $notification->created_at?->diffForHumans() }}</p>
                            </div>
                            @if ($isUnread)
                                <span class="mt-1 size-2 shrink-0 rounded-full bg-[#047857]" aria-hidden="true"></span>
                            @endif
                        </div>

                        @if (filled($notification->action))
                            <a
                                href="{{ $notification->action }}"
                                wire:navigate
                                wire:click="markRead({{ $notification->id }})"
                                class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-[#047857] px-3 py-1.5 text-xs font-bold text-white"
                            >
                                {{ __('doctor.notifications.open') }}
                            </a>
                        @endif
                    </article>
                @endforeach
            </div>

            @if ($this->notifications->count() < $this->totalNotificationsCount)
                <div class="flex justify-center py-4" x-intersect="$wire.loadMore()">
                    <div class="size-6 animate-spin rounded-full border-2 border-slate-200 border-t-[#047857]" aria-hidden="true"></div>
                </div>
            @endif
        @endif
    </main>
</div>
