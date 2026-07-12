<div
    class="doctor-luxury-support relative flex h-[100dvh] flex-col overflow-hidden bg-slate-50 lg:hidden"
    data-test="doctor-luxury-support"
>
    <header class="shrink-0 bg-gradient-to-b from-white to-slate-50 px-5 pb-4 pt-[max(2.25rem,env(safe-area-inset-top))]">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('doctor.menu') }}"
                wire:navigate
                class="flex size-10 shrink-0 items-center justify-center rounded-full border border-slate-100 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50"
                aria-label="{{ __('Back') }}"
            >
                <flux:icon name="chevron-left" variant="mini" class="size-5 rtl:rotate-180" />
            </a>
            <h1 class="min-w-0 flex-1 text-xl font-bold tracking-tight text-slate-900">
                {{ __('tickets.title') }}
            </h1>
        </div>
    </header>

    <main class="doctor-luxury-scroll mx-auto flex min-h-0 w-full max-w-md flex-1 flex-col gap-4 overflow-y-auto overscroll-contain px-5 pb-[calc(5.5rem+env(safe-area-inset-bottom))] pt-1">
        <a
            href="{{ route('doctor.settings.support.create') }}"
            wire:navigate
            class="flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-[#047857] text-sm font-bold text-white shadow-sm transition active:scale-[0.98]"
        >
            <flux:icon name="plus" variant="mini" class="size-4" />
            {{ __('tickets.new_ticket') }}
        </a>

        @if ($this->tickets->isEmpty())
            <div class="rounded-3xl border border-slate-100 bg-white px-6 py-14 text-center shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <flux:icon name="lifebuoy" variant="outline" class="size-7" />
                </div>
                <p class="text-sm leading-relaxed text-slate-500">{{ __('tickets.empty') }}</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($this->tickets as $ticket)
                    <a
                        href="{{ route('doctor.settings.support.show', $ticket) }}"
                        wire:navigate
                        wire:key="doctor-ticket-{{ $ticket->id }}"
                        class="block rounded-2xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] transition active:scale-[0.99]"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[0.625rem] font-bold uppercase tracking-wide text-slate-400">{{ $ticket->ticket_number }}</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">{{ $ticket->subject }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $ticket->category?->displayName() }}</p>
                                <p class="mt-2 text-[0.625rem] font-medium text-slate-400">{{ $ticket->created_at?->diffForHumans() }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-[0.625rem] font-bold {{ $this->statusClasses((string) $ticket->status) }}">
                                {{ $this->statusLabel((string) $ticket->status) }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </main>
</div>
