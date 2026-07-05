@php
    $unreadCount = $this->notifications->whereNull('read_at')->count();
@endphp

<main class="space-y-4 px-6 py-6" data-test="patient-notifications-list">
    @if ($this->notifications->isEmpty())
        <div class="rounded-3xl border border-slate-100 bg-white px-6 py-14 text-center shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
            <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-emerald-50 text-[#10B981]">
                <flux:icon name="bell" variant="outline" class="size-7" />
            </div>
            <p class="text-sm font-semibold text-slate-900">{{ __('patient.notifications.empty') }}</p>
            <p class="mx-auto mt-2 max-w-xs text-xs leading-relaxed text-slate-500">{{ __('patient.notifications.empty_hint') }}</p>
        </div>
    @else
        @if ($unreadCount > 0)
            <p class="px-1 text-xs font-semibold text-slate-500">
                {{ trans_choice('patient.notifications.unread_count', $unreadCount, ['count' => $unreadCount]) }}
            </p>
        @endif

        <div class="space-y-3">
            @foreach ($this->notifications as $notification)
                <article @class([
                    'rounded-2xl border bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] transition',
                    'border-[#10B981]/25 bg-emerald-50/40' => $notification->read_at === null,
                    'border-slate-100' => $notification->read_at !== null,
                ])>
                    <div class="flex items-start gap-3">
                        <span @class([
                            'mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl',
                            'bg-[#10B981] text-white' => $notification->read_at === null,
                            'bg-slate-100 text-slate-400' => $notification->read_at !== null,
                        ])>
                            <flux:icon name="bell" variant="outline" class="size-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="text-sm font-semibold text-slate-900">{{ $notification->displayTitle() }}</h3>
                                @if ($notification->read_at === null)
                                    <span class="shrink-0 rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-sky-700">
                                        {{ __('patient.notifications.unread') }}
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $notification->displayMessage() }}</p>
                            <p class="mt-2 text-xs text-slate-400">{{ $notification->created_at?->diffForHumans() }}</p>

                            @if (filled($notification->action))
                                <flux:button
                                    :href="$notification->action"
                                    wire:navigate
                                    size="sm"
                                    variant="primary"
                                    class="mt-3 !rounded-xl !border-[#10B981] !bg-[#10B981] !text-white hover:!brightness-[0.97]"
                                    wire:click="markRead({{ $notification->id }})"
                                >
                                    {{ __('patient.notifications.open') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</main>
