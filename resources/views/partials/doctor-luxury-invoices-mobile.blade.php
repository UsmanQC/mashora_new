<div
    class="doctor-luxury-invoices relative flex h-[100dvh] flex-col overflow-hidden bg-slate-50 lg:hidden"
    data-test="doctor-luxury-invoices"
>
    <header class="shrink-0 bg-gradient-to-b from-white to-slate-50 px-5 pb-4 pt-[max(2.25rem,env(safe-area-inset-top))]">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('doctor.settings.wallet') }}"
                wire:navigate
                class="flex size-10 shrink-0 items-center justify-center rounded-full border border-slate-100 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50"
                aria-label="{{ __('doctor.invoices.back') }}"
            >
                <flux:icon name="chevron-left" variant="mini" class="size-5 rtl:rotate-180" />
            </a>
            <h1 class="min-w-0 flex-1 text-xl font-bold tracking-tight text-slate-900">
                {{ __('doctor.invoices.title') }}
            </h1>
        </div>
    </header>

    <main class="doctor-luxury-scroll mx-auto flex min-h-0 w-full max-w-md flex-1 flex-col gap-3 overflow-y-auto overscroll-contain px-5 pb-[calc(5.5rem+env(safe-area-inset-bottom))] pt-1">
        @forelse ($this->invoices as $invoice)
            <div
                wire:key="doctor-invoice-{{ $invoice->id }}"
                class="rounded-2xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-bold text-slate-900">
                                {{ $invoice->reference ?: __('doctor.invoices.invoice_number', ['id' => $invoice->id]) }}
                            </p>
                            <span @class([
                                'inline-flex rounded-full px-2 py-0.5 text-[0.625rem] font-bold uppercase tracking-wide',
                                $this->statusBadgeClasses($invoice),
                            ])>
                                {{ $this->statusLabel($invoice) }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ __('doctor.invoices.issued') }}: {{ $invoice->issue_date ? \Carbon\Carbon::parse($invoice->issue_date)->format('Y-m-d') : '—' }}
                        </p>
                        @if ($invoice->from_date && $invoice->to_date)
                            <p class="text-xs text-slate-500">
                                {{ __('doctor.invoices.period') }}: {{ $invoice->from_date->format('Y-m-d') }} → {{ $invoice->to_date->format('Y-m-d') }}
                            </p>
                        @endif
                        <p class="mt-1 text-xs text-slate-500">
                            {{ trans_choice('doctor.invoices.sessions_count', $invoice->appointments_count, ['count' => $invoice->appointments_count]) }}
                        </p>
                    </div>
                    <div class="shrink-0 text-end">
                        <p class="text-[0.625rem] font-bold uppercase tracking-wide text-slate-400">{{ __('doctor.invoices.doctor_share') }}</p>
                        <p class="mt-0.5 text-base font-bold tabular-nums text-[#047857]">
                            {{ number_format((float) $invoice->doctor_share, 2) }}
                            <img src="{{ asset('images/saudi_riyal.svg') }}" alt="" class="inline-block h-3 w-3 align-middle" />
                        </p>
                    </div>
                </div>

                <div class="mt-3 flex gap-2">
                    <a
                        href="{{ route('doctor.settings.invoices.show', $invoice) }}"
                        wire:navigate
                        class="flex flex-1 items-center justify-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700"
                    >
                        <flux:icon name="eye" variant="mini" class="size-3.5" />
                        {{ __('doctor.invoices.view_details') }}
                    </a>
                    <a
                        href="{{ route('doctor.settings.invoices.pdf', $invoice) }}"
                        class="flex flex-1 items-center justify-center gap-1.5 rounded-full bg-[#10B981]/10 px-3 py-2 text-xs font-bold text-[#047857]"
                    >
                        <flux:icon name="arrow-down-tray" variant="mini" class="size-3.5" />
                        {{ __('doctor.invoices.download_pdf') }}
                    </a>
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-slate-100 bg-white px-6 py-14 text-center shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <flux:icon name="document-text" variant="outline" class="size-7" />
                </div>
                <p class="text-sm leading-relaxed text-slate-500">{{ __('doctor.invoices.empty') }}</p>
            </div>
        @endforelse
    </main>
</div>
