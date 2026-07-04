<?php

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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

    public function unreadCount(): int
    {
        return $this->notifications->whereNull('read_at')->count();
    }

    public function headerSubtitle(): string
    {
        $unread = $this->unreadCount();

        if ($unread > 0) {
            return trans_choice('patient.notifications.unread_count', $unread, ['count' => $unread]);
        }

        return (string) __('patient.notifications.subtitle');
    }

    public function profilePhotoUrl(): ?string
    {
        $user = Auth::user();

        if ($user === null || ! filled($user->profile_photo_path)) {
            return null;
        }

        return Storage::disk('public')->url((string) $user->profile_photo_path);
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

<div class="patient-luxury-notifications bg-slate-50 pb-[calc(4.75rem+env(safe-area-inset-bottom))] sm:bg-transparent sm:pb-12" data-test="patient-luxury-notifications">
    <div class="sm:hidden">
        @include('partials.patient-luxury-page-header', [
            'title' => __('patient.menu.notifications'),
            'subtitle' => $this->headerSubtitle(),
            'profilePhotoUrl' => $this->profilePhotoUrl(),
            'userName' => auth()->user()?->name,
            'testId' => 'patient-notifications-header',
        ])

        @include('partials.patient-luxury-notifications-mobile')
    </div>

    <div class="mx-auto hidden w-full max-w-2xl space-y-6 px-6 py-4 sm:block sm:px-0 sm:py-0">
        <header class="mb-8">
            <nav class="mb-3 text-sm text-zinc-600" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-2">
                    <li>
                        <flux:link :href="route('patient.home')" wire:navigate class="font-medium text-[#10B981] hover:text-[#064e3b]">
                            {{ __('patient.nav.home') }}
                        </flux:link>
                    </li>
                    <li aria-hidden="true" class="text-zinc-400">/</li>
                    <li class="font-semibold text-zinc-900">{{ __('patient.menu.notifications') }}</li>
                </ol>
            </nav>
            <flux:heading size="xl" class="font-semibold text-zinc-900">
                {{ __('patient.menu.notifications') }}
            </flux:heading>
            <flux:text class="mt-1 text-zinc-600">{{ $this->headerSubtitle() }}</flux:text>
        </header>

        @if ($this->notifications->isEmpty())
            <div class="rounded-3xl border border-slate-100/80 bg-white px-6 py-14 text-center shadow-[0_8px_32px_0_rgba(0,0,0,0.03)]">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-emerald-50 text-[#10B981]">
                    <flux:icon name="bell" variant="outline" class="size-7" />
                </div>
                <flux:text class="font-semibold text-slate-900">{{ __('patient.notifications.empty') }}</flux:text>
                <flux:text class="mx-auto mt-2 max-w-sm text-sm text-slate-500">{{ __('patient.notifications.empty_hint') }}</flux:text>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($this->notifications as $notification)
                    <article @class([
                        'rounded-3xl border bg-white p-5 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] transition',
                        'border-[#10B981]/25 bg-emerald-50/40' => $notification->read_at === null,
                        'border-slate-100/80' => $notification->read_at !== null,
                    ])>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <flux:heading size="sm" class="font-semibold text-slate-900">{{ $notification->displayTitle() }}</flux:heading>
                                <flux:text class="mt-1 text-sm text-slate-600">{{ $notification->displayMessage() }}</flux:text>
                                <flux:text class="mt-2 text-xs text-slate-400">{{ $notification->created_at?->diffForHumans() }}</flux:text>
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
                                    class="!rounded-xl !border-[#10B981] !bg-[#10B981] !text-white hover:!brightness-[0.97]"
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
</div>
