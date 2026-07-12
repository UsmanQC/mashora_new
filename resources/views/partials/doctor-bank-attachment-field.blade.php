@props([
    'existingPath' => null,
    'existingUrl' => null,
    'existingIsImage' => false,
    'existingFilename' => null,
])

<flux:field>
    <flux:label>
        {{ __('doctor.auth.bank_attachment_label') }}
        <span class="font-normal text-zinc-400">({{ __('doctor.auth.bank_attachment_optional') }})</span>
    </flux:label>
    <flux:text class="mb-2 text-xs text-zinc-500">{{ __('doctor.auth.bank_attachment_hint') }}</flux:text>

    @if ($existingUrl && ! $errors->has('attachment'))
        <div class="mb-3 rounded-xl border border-emerald-100 bg-emerald-50/60 p-3">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800">{{ __('doctor.auth.bank_attachment_current') }}</p>
                    @if ($existingIsImage)
                        <img
                            src="{{ $existingUrl }}"
                            alt="{{ __('doctor.auth.bank_attachment_label') }}"
                            class="mt-2 max-h-28 rounded-lg border border-white/80 bg-white object-contain shadow-sm"
                        >
                    @else
                        <p class="mt-1 truncate text-sm font-medium text-emerald-950">{{ $existingFilename ?: __('doctor.auth.bank_attachment_pdf') }}</p>
                    @endif
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <flux:button
                        :href="$existingUrl"
                        target="_blank"
                        rel="noopener"
                        size="sm"
                        variant="outline"
                        icon="eye"
                        class="!border-emerald-200 !text-emerald-800 hover:!bg-white"
                    >
                        {{ __('doctor.auth.bank_attachment_view') }}
                    </flux:button>
                    <flux:button
                        type="button"
                        size="sm"
                        variant="ghost"
                        icon="trash"
                        class="!text-rose-600 hover:!bg-rose-50"
                        wire:click="removeBankAttachment"
                    >
                        {{ __('doctor.auth.bank_attachment_remove') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    <input
        type="file"
        wire:model="attachment"
        accept=".pdf,image/jpeg,image/png,image/webp,application/pdf"
        class="block w-full rounded-xl border border-zinc-200 bg-white px-3 py-2.5 text-sm text-zinc-700 file:me-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-emerald-800 hover:file:bg-emerald-100"
    />

    <div wire:loading wire:target="attachment" class="mt-2 text-xs font-medium text-emerald-700">
        {{ __('doctor.auth.bank_attachment_uploading') }}
    </div>

    <flux:error name="attachment" />
</flux:field>
