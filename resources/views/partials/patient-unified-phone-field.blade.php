@php
    use App\Support\CountryPhoneTerritories;

    /** @var string $countryIso ISO 3166-1 alpha-2 (Livewire), e.g. SA */
    /** @var string $phone National number (Livewire) */
    $rows = CountryPhoneTerritories::sortedRowsForLocale();
    $currentIso = strtoupper($countryIso);
    $current = collect($rows)->firstWhere('iso', $currentIso)
        ?? collect($rows)->firstWhere('iso', 'SA')
        ?? $rows[0];
@endphp

<div>
    <flux:field>
        <flux:label>{{ __('patient_auth.phone_label') }}</flux:label>

        <div
            class="flex w-full overflow-hidden rounded-xl border border-[#3c5cf7]/35 bg-white shadow-sm ring-1 ring-black/[0.04] transition focus-within:border-mashora-brand focus-within:ring-2 focus-within:ring-mashora-brand/25"
            x-data="{ open: false }"
            @keydown.escape.window="open = false"
        >
            <div class="relative shrink-0 border-e border-zinc-200/90 bg-zinc-50/90">
                <button
                    type="button"
                    class="flex h-12 min-w-[5.5rem] items-center gap-1.5 px-3 text-sm font-medium text-zinc-800 outline-none transition hover:bg-zinc-100/90"
                    @click.prevent="open = !open"
                    :aria-expanded="open.toString()"
                    aria-haspopup="listbox"
                >
                    <span class="text-lg leading-none" aria-hidden="true">{{ $current['flag'] }}</span>
                    <svg class="size-4 shrink-0 text-zinc-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div
                    x-show="open"
                    x-cloak
                    x-transition
                    @click.outside="open = false"
                    class="absolute bottom-full start-0 z-50 mb-1.5 max-h-64 w-[min(100vw-2rem,22rem)] overflow-y-auto rounded-xl border border-zinc-200 bg-white py-1 shadow-lg"
                    role="listbox"
                >
                    @foreach ($rows as $row)
                        <button
                            type="button"
                            class="flex w-full items-center gap-2 px-3 py-2.5 text-start text-sm hover:bg-zinc-50 @if ($currentIso === $row['iso']) bg-[#0B163E]/12 font-semibold text-[#0B163E] ring-1 ring-inset ring-[#0B163E]/25 @endif"
                            wire:click="$set('countryIso', '{{ $row['iso'] }}')"
                            @click="open = false"
                            role="option"
                        >
                            <span class="text-base leading-none" aria-hidden="true">{{ $row['flag'] }}</span>
                            <span class="min-w-0 flex-1 truncate font-medium text-zinc-800">{{ $row['label'] }}</span>
                            <span class="shrink-0 tabular-nums text-zinc-500">+{{ $row['dial'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <input
                type="tel"
                wire:model.blur="phone"
                inputmode="tel"
                autocomplete="tel-national"
                placeholder="{{ __('patient_auth.phone_national_placeholder') }}"
                class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-base text-zinc-900 outline-none ring-0 placeholder:text-zinc-400 focus:ring-0"
            />
        </div>
    </flux:field>
</div>
