<div
    class="doctor-luxury-bank-account relative flex h-[100dvh] flex-col overflow-hidden bg-slate-50 lg:hidden"
    data-test="doctor-luxury-bank-account"
>
    <header class="shrink-0 bg-gradient-to-b from-white to-slate-50 px-5 pb-4 pt-[max(2.25rem,env(safe-area-inset-top))]">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('doctor.menu') }}"
                wire:navigate
                class="flex size-10 shrink-0 items-center justify-center rounded-full border border-slate-100 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50"
                aria-label="{{ __('doctor.auth.back') }}"
            >
                <flux:icon name="chevron-left" variant="mini" class="size-5 rtl:rotate-180" />
            </a>
            <h1 class="min-w-0 flex-1 text-xl font-bold tracking-tight text-slate-900">
                {{ __('doctor.auth.bank_account_title') }}
            </h1>
        </div>
    </header>

    <form wire:submit="save" class="flex min-h-0 flex-1 flex-col">
        <main class="doctor-luxury-scroll mx-auto flex min-h-0 w-full max-w-md flex-1 flex-col gap-5 overflow-y-auto overscroll-contain px-5 pb-4 pt-1">
            <p class="text-sm leading-relaxed text-slate-500">
                {{ __('doctor.auth.bank_account_subtitle') }}
            </p>

            <section class="rounded-3xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-500">
                            {{ __('doctor.auth.bank_account_holder') }}
                        </label>
                        <input
                            type="text"
                            wire:model="account_holder_name"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-[#047857] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#047857]"
                        />
                        <flux:error name="account_holder_name" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-500">
                            {{ __('doctor.auth.bank_account_number') }}
                        </label>
                        <input
                            type="text"
                            dir="ltr"
                            wire:model="account_number"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold text-slate-900 focus:border-[#047857] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#047857]"
                        />
                        <flux:error name="account_number" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-500">
                            {{ __('doctor.auth.bank_iban_number') }}
                        </label>
                        <input
                            type="text"
                            dir="ltr"
                            wire:model="iban_number"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm font-semibold uppercase text-slate-900 focus:border-[#047857] focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#047857]"
                        />
                        <flux:error name="iban_number" />
                    </div>
                </div>
            </section>

            <div>
                <h2 class="mb-1 text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500">
                    {{ __('doctor.auth.bank_attachment_label') }}
                    <span class="normal-case font-medium text-slate-400">({{ __('doctor.auth.bank_attachment_optional') }})</span>
                </h2>
                <p class="mb-3 text-xs leading-relaxed text-slate-500">{{ __('doctor.auth.bank_attachment_hint') }}</p>

                @if ($this->existingAttachmentUrl() && ! $errors->has('attachment'))
                    <div class="mb-3 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-3">
                        <p class="text-[0.625rem] font-bold uppercase tracking-wide text-emerald-800">
                            {{ __('doctor.auth.bank_attachment_current') }}
                        </p>

                        @if ($this->existingAttachmentIsImage())
                            <img
                                src="{{ $this->existingAttachmentUrl() }}"
                                alt="{{ __('doctor.auth.bank_attachment_label') }}"
                                class="mt-2 max-h-28 rounded-lg border border-white/80 bg-white object-contain shadow-sm"
                            >
                        @else
                            <p class="mt-1 truncate text-sm font-semibold text-emerald-950">
                                {{ $this->existingAttachmentFilename() ?: __('doctor.auth.bank_attachment_pdf') }}
                            </p>
                        @endif

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <a
                                href="{{ $this->existingAttachmentUrl() }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-xs font-bold text-emerald-800"
                            >
                                <flux:icon name="eye" variant="mini" class="size-3.5" />
                                {{ __('doctor.auth.bank_attachment_view') }}
                            </a>
                            <button
                                type="button"
                                wire:click="removeBankAttachment"
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold text-rose-600"
                            >
                                <flux:icon name="trash" variant="mini" class="size-3.5" />
                                {{ __('doctor.auth.bank_attachment_remove') }}
                            </button>
                        </div>
                    </div>
                @endif

                <label class="flex cursor-pointer items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-slate-200 bg-white px-4 py-6 text-center">
                    <input
                        type="file"
                        wire:model="attachment"
                        accept=".pdf,image/jpeg,image/png,image/webp,application/pdf"
                        class="hidden"
                    />
                    <flux:icon name="arrow-up-tray" variant="outline" class="size-5 shrink-0 text-slate-400" />
                    <span class="text-sm font-semibold text-slate-600">{{ __('doctor.auth.bank_attachment_label') }}</span>
                </label>

                <div wire:loading wire:target="attachment" class="mt-2 text-xs font-medium text-emerald-700">
                    {{ __('doctor.auth.bank_attachment_uploading') }}
                </div>

                <flux:error name="attachment" />
            </div>

            @if (session('bank_account_saved'))
                <p class="rounded-2xl bg-emerald-50 px-4 py-3 text-center text-sm font-semibold text-emerald-700">
                    {{ session('bank_account_saved') }}
                </p>
            @endif
        </main>

        <div class="shrink-0 border-t border-slate-100 bg-white px-5 pb-[calc(5.5rem+env(safe-area-inset-bottom))] pt-3">
            <button
                type="submit"
                class="flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-[#047857] text-sm font-bold text-white shadow-sm transition active:scale-[0.98]"
            >
                {{ __('doctor.auth.bank_account_save') }}
            </button>
        </div>
    </form>
</div>
