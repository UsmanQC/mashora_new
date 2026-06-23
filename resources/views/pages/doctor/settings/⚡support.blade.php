<?php

use App\Models\Doctor;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Support')] class extends Component
{
    protected function doctor(): Doctor
    {
        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        return $doctor;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTicketsProperty(): Collection
    {
        return Ticket::query()
            ->with('category')
            ->forCreator($this->doctor())
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
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" class="font-semibold text-zinc-900">{{ __('tickets.title') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-600">{{ __('tickets.subtitle') }}</flux:text>
        </div>
        <flux:button :href="route('doctor.settings')" wire:navigate variant="ghost" size="sm" icon="arrow-left">{{ __('Back') }}</flux:button>
    </div>

    <flux:button :href="route('doctor.settings.support.create')" wire:navigate variant="primary" class="!bg-[#047857] !text-white">
        {{ __('tickets.new_ticket') }}
    </flux:button>

    @if ($this->tickets->isEmpty())
        <div class="rounded-2xl border border-zinc-200/90 bg-white px-6 py-12 text-center shadow-sm">
            <flux:text class="text-zinc-600">{{ __('tickets.empty') }}</flux:text>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($this->tickets as $ticket)
                <a
                    href="{{ route('doctor.settings.support.show', $ticket) }}"
                    wire:navigate
                    class="block rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm transition hover:border-[#10B981]/35 hover:shadow-md"
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
