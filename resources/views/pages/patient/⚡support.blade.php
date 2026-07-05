<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient')] #[Title('Support')] class extends Component
{
    protected function patient(): User
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTicketsProperty(): Collection
    {
        return Ticket::query()
            ->with('category')
            ->forCreator($this->patient())
            ->latest()
            ->limit(50)
            ->get();
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            Ticket::STATUS_OPEN => __('tickets.status_open'),
            Ticket::STATUS_ANSWERED => __('tickets.status_answered'),
            Ticket::STATUS_CLOSED => __('tickets.status_closed'),
            default => $status,
        };
    }

    public function statusClasses(string $status): string
    {
        return match ($status) {
            Ticket::STATUS_OPEN => 'bg-amber-100 text-amber-800',
            Ticket::STATUS_ANSWERED => 'bg-sky-100 text-sky-800',
            Ticket::STATUS_CLOSED => 'bg-zinc-100 text-zinc-600',
            default => 'bg-zinc-100 text-zinc-700',
        };
    }

    public function profilePhotoUrl(): ?string
    {
        $user = Auth::user();

        if ($user === null || ! filled($user->profile_photo_path)) {
            return null;
        }

        return Storage::disk('public')->url((string) $user->profile_photo_path);
    }
}; ?>

<div class="patient-luxury-support bg-slate-50 pb-[calc(4.75rem+env(safe-area-inset-bottom))] sm:bg-transparent sm:pb-12" data-test="patient-luxury-support">
    <div class="sm:hidden">
        @include('partials.patient-luxury-page-header', [
            'title' => __('patient.menu.support'),
            'subtitle' => __('patient.menu.support_sub'),
            'profilePhotoUrl' => $this->profilePhotoUrl(),
            'userName' => auth()->user()?->name,
            'backUrl' => route('patient.menu'),
            'backLabel' => __('patient.nav.menu'),
            'testId' => 'patient-support-header',
        ])
    </div>

    <div class="mx-auto max-w-2xl space-y-5 px-6 pt-5 sm:space-y-6 sm:px-4 sm:py-8">
        <div class="hidden items-start justify-between gap-4 sm:flex">
            <div>
                <flux:heading size="xl" class="font-semibold text-[#10B981]">{{ __('tickets.title') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-600">{{ __('tickets.subtitle') }}</flux:text>
            </div>
            <flux:button :href="route('patient.menu')" wire:navigate variant="ghost" size="sm" icon="arrow-left">
                {{ __('patient.empty_state.menu_crumb') }}
            </flux:button>
        </div>

        <flux:button :href="route('patient.support.create')" wire:navigate variant="primary" class="w-full !bg-[#10B981] !text-white sm:w-auto">
            {{ __('tickets.new_ticket') }}
        </flux:button>

        @if ($this->tickets->isEmpty())
            <div class="rounded-3xl border border-slate-100/80 bg-white px-6 py-12 text-center shadow-[0_8px_32px_0_rgba(0,0,0,0.03)] sm:rounded-2xl sm:border-zinc-200/90 sm:shadow-sm">
                <flux:text class="text-zinc-600">{{ __('tickets.empty') }}</flux:text>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($this->tickets as $ticket)
                    <a
                        href="{{ route('patient.support.show', $ticket) }}"
                        wire:navigate
                        class="block rounded-3xl border border-slate-100/80 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] transition hover:border-[#10B981]/30 hover:shadow-md sm:rounded-2xl sm:border-zinc-200/90 sm:shadow-sm"
                    >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ $ticket->ticket_number }}</p>
                            <flux:heading size="sm" class="mt-1 font-semibold text-zinc-900">{{ $ticket->subject }}</flux:heading>
                            <flux:text class="mt-1 text-sm text-zinc-600">{{ $ticket->category?->displayName() }}</flux:text>
                            <flux:text class="mt-2 text-xs text-zinc-400">{{ $ticket->created_at?->diffForHumans() }}</flux:text>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $this->statusClasses((string) $ticket->status) }}">
                            {{ $this->statusLabel((string) $ticket->status) }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
    </div>
</div>
