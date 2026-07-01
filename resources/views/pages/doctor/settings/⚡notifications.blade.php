<?php

use App\Models\Doctor;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Notifications')] class extends Component
{
    public int $limit = 10;

    protected function doctor(): Doctor
    {
        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        return $doctor;
    }

    public function markAllRead(): void
    {
        Notification::query()
            ->where('userable_type', Doctor::class)
            ->where('userable_id', $this->doctor()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function markRead(int $notificationId): void
    {
        Notification::query()
            ->whereKey($notificationId)
            ->where('userable_type', Doctor::class)
            ->where('userable_id', $this->doctor()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function loadMore(): void
    {
        $this->limit += 10;
    }

    public function getNotificationsProperty()
    {
        return Notification::query()
            ->where('userable_type', Doctor::class)
            ->where('userable_id', $this->doctor()->id)
            ->latest()
            ->limit($this->limit)
            ->get();
    }

    public function getTotalNotificationsCountProperty(): int
    {
        return Notification::query()
            ->where('userable_type', Doctor::class)
            ->where('userable_id', $this->doctor()->id)
            ->count();
    }

    public function getUnreadCountProperty(): int
    {
        return Notification::query()
            ->where('userable_type', Doctor::class)
            ->where('userable_id', $this->doctor()->id)
            ->whereNull('read_at')
            ->count();
    }
}; ?>

<div class="mx-auto max-w-2xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading size="xl" class="font-semibold text-[#10B981]">{{ __('doctor.notifications.title') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-600">{{ __('doctor.notifications.subtitle') }}</flux:text>
            @if ($this->unreadCount > 0)
                <flux:text class="mt-1 text-xs font-medium text-[#047857]">
                    {{ trans_choice('doctor.notifications.unread_count', $this->unreadCount, ['count' => $this->unreadCount]) }}
                </flux:text>
            @endif
        </div>
        <div class="flex items-center gap-2">
            @if ($this->unreadCount > 0)
                <flux:button type="button" wire:click="markAllRead" variant="ghost" size="sm">
                    {{ __('doctor.notifications.mark_all_read') }}
                </flux:button>
            @endif
            <flux:button :href="route('doctor.dashboard')" wire:navigate variant="ghost" size="sm" icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
    </div>

    @if ($this->notifications->isEmpty())
        <div class="rounded-2xl border border-zinc-200/90 bg-white px-6 py-12 text-center shadow-sm">
            <flux:icon name="bell" variant="outline" class="mx-auto size-10 text-zinc-300" />
            <flux:text class="mt-3 text-zinc-600">{{ __('doctor.notifications.empty') }}</flux:text>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($this->notifications as $notification)
                <article
                    wire:key="doctor-notification-{{ $notification->id }}"
                    @class([
                        'rounded-2xl border bg-white p-4 shadow-sm transition',
                        'border-[#10B981]/30 bg-emerald-50/40 ring-1 ring-[#10B981]/10' => $notification->read_at === null,
                        'border-zinc-200/90' => $notification->read_at !== null,
                    ])
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <flux:heading size="sm" class="font-semibold text-zinc-900">
                                {{ $notification->displayTitle() }}
                            </flux:heading>
                            <flux:text class="mt-1 text-sm leading-relaxed text-zinc-600">
                                {{ $notification->displayMessage() }}
                            </flux:text>
                            <flux:text class="mt-2 text-xs text-zinc-400">
                                {{ $notification->created_at?->diffForHumans() }}
                            </flux:text>
                        </div>
                        @if ($notification->read_at === null)
                            <flux:badge color="lime" size="sm">{{ __('doctor.notifications.unread') }}</flux:badge>
                        @endif
                    </div>

                    @if (filled($notification->action))
                        <div class="mt-4">
                            <flux:button
                                :href="$notification->action"
                                wire:navigate
                                size="sm"
                                variant="primary"
                                class="!bg-[#10B981] !text-white hover:!brightness-95"
                                wire:click="markRead({{ $notification->id }})"
                            >
                                {{ __('doctor.notifications.open') }}
                            </flux:button>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        @if ($this->notifications->count() < $this->totalNotificationsCount)
            <div class="flex justify-center py-2" x-intersect="$wire.loadMore()">
                <div class="size-6 animate-spin rounded-full border-2 border-zinc-200 border-t-[#10B981]" aria-hidden="true"></div>
            </div>
        @endif
    @endif
</div>
