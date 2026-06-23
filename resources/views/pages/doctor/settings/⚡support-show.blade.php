<?php

use App\Models\Doctor;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Support ticket')] class extends Component
{
    public Ticket $ticket;

    public function mount(Ticket $ticket): void
    {
        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor || ! app(TicketService::class)->creatorCanView($doctor, $ticket)) {
            abort(403);
        }

        $this->ticket = $ticket->load(['category', 'replies.author']);
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
}; ?>

<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <flux:button :href="route('doctor.settings.support')" wire:navigate variant="ghost" size="sm" icon="arrow-left" class="mb-3">
            {{ __('tickets.title') }}
        </flux:button>
        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ $ticket->ticket_number }}</p>
        <flux:heading size="lg" class="mt-1 font-semibold text-zinc-900">{{ $ticket->subject }}</flux:heading>
        <flux:text class="mt-1 text-sm text-zinc-600">
            {{ $ticket->category?->displayName() }} · {{ $this->statusLabel((string) $ticket->status) }}
        </flux:text>
    </div>

    <section class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('tickets.initial_message') }}</p>
        <p class="mt-3 whitespace-pre-wrap text-sm text-zinc-800">{{ $ticket->message }}</p>
        <p class="mt-3 text-xs text-zinc-400">{{ $ticket->created_at?->translatedFormat('d M Y, H:i') }}</p>
    </section>

    <section class="space-y-3">
        <flux:heading size="sm" class="font-semibold text-zinc-900">{{ __('tickets.replies') }}</flux:heading>

        @forelse ($ticket->replies as $reply)
            <article @class([
                'rounded-2xl border p-4 shadow-sm',
                'border-[#047857]/20 bg-[#047857]/5' => $reply->isFromAdmin(),
                'border-zinc-200/90 bg-white' => ! $reply->isFromAdmin(),
            ])>
                <p class="text-xs font-semibold text-zinc-600">{{ $reply->authorDisplayName() }}</p>
                <p class="mt-2 whitespace-pre-wrap text-sm text-zinc-800">{{ $reply->message }}</p>
                <p class="mt-2 text-xs text-zinc-400">{{ $reply->created_at?->translatedFormat('d M Y, H:i') }}</p>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50 px-4 py-8 text-center">
                <flux:text class="text-sm text-zinc-500">{{ __('tickets.no_replies') }}</flux:text>
            </div>
        @endforelse
    </section>
</div>
