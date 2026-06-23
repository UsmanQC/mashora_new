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
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
        <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('Notifications') }}</flux:heading>
        <flux:button :href="route('doctor.settings')" wire:navigate variant="ghost" size="sm" icon="arrow-left">{{ __('Back') }}</flux:button>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <flux:text class="text-sm text-zinc-600">{{ __('Latest notifications for your account.') }}</flux:text>
            <flux:button type="button" wire:click="markAllRead" variant="ghost" size="sm">{{ __('Mark all as read') }}</flux:button>
        </div>
        <div class="space-y-3">
            @forelse ($this->notifications as $notification)
                <div @class([
                    'rounded-xl border p-3',
                    'border-zinc-200 bg-white' => $notification->read_at,
                    'border-emerald-200 bg-emerald-50/40' => ! $notification->read_at,
                ])>
                    <div class="flex items-center justify-between gap-3">
                        <flux:text class="font-semibold text-zinc-900">{{ $notification->title ?: __('Notification') }}</flux:text>
                        <flux:text class="text-xs text-zinc-500">{{ $notification->created_at?->diffForHumans() }}</flux:text>
                    </div>
                    @if ($notification->message)
                        <flux:text class="mt-1 text-sm text-zinc-600">{{ $notification->message }}</flux:text>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-5 text-center">
                    <flux:text class="text-sm text-zinc-500">{{ __('No notifications yet.') }}</flux:text>
                </div>
            @endforelse
        </div>

        @if ($this->notifications->count() < $this->totalNotificationsCount)
            <div class="mt-4 flex justify-center" x-intersect="$wire.loadMore()">
                <div class="h-6 w-6 animate-spin rounded-full border-2 border-zinc-300 border-t-[#047857]"></div>
            </div>
        @endif
    </div>
</div>
