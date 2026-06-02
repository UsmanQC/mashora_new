<?php

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient')] #[Title('Notifications')] class extends Component
{
    /**
     * @return Collection<int, Notification>
     */
    public function getNotificationsProperty(): Collection
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return collect();
        }

        return Notification::query()
            ->where('userable_type', User::class)
            ->where('userable_id', $user->id)
            ->latest()
            ->limit(50)
            ->get();
    }

    public function markRead(int $notificationId): void
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return;
        }

        Notification::query()
            ->whereKey($notificationId)
            ->where('userable_type', User::class)
            ->where('userable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}; ?>

<div class="mx-auto max-w-2xl space-y-6 px-4 py-8">
    <div>
        <flux:heading size="xl" class="font-semibold text-[#193ADB]">{{ __('patient.menu.notifications') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-600">{{ __('patient.notifications.subtitle') }}</flux:text>
    </div>

    @if ($this->notifications->isEmpty())
        <div class="rounded-2xl border border-zinc-200/90 bg-white px-6 py-12 text-center shadow-sm">
            <flux:text class="text-zinc-600">{{ __('patient.notifications.empty') }}</flux:text>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($this->notifications as $notification)
                <article @class([
                    'rounded-2xl border bg-white p-4 shadow-sm transition',
                    'border-[#193ADB]/30 bg-blue-50/40' => $notification->read_at === null,
                    'border-zinc-200/90' => $notification->read_at !== null,
                ])>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <flux:heading size="sm" class="font-semibold text-zinc-900">{{ $notification->title }}</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-600">{{ $notification->message }}</flux:text>
                            <flux:text class="mt-2 text-xs text-zinc-400">{{ $notification->created_at?->diffForHumans() }}</flux:text>
                        </div>
                        @if ($notification->read_at === null)
                            <flux:badge color="sky" size="sm">{{ __('patient.notifications.unread') }}</flux:badge>
                        @endif
                    </div>

                    @if (filled($notification->action))
                        <div class="mt-4">
                            <flux:button
                                :href="$notification->action"
                                wire:navigate
                                size="sm"
                                variant="primary"
                                class="!bg-[#193ADB] !text-white hover:!brightness-95"
                                wire:click="markRead({{ $notification->id }})"
                            >
                                {{ __('patient.notifications.open') }}
                            </flux:button>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</div>
