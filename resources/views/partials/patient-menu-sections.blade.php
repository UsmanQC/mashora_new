@php
    use App\Support\PatientMenu;

    /** @var string|null $ariaLabel */
    $ariaLabel = $ariaLabel ?? __('patient.menu.grid_aria');
    $sections = PatientMenu::sections();
@endphp

<nav class="space-y-8" aria-label="{{ $ariaLabel }}">
    @foreach ($sections as $section)
        <section>
            <div class="mb-3 px-0.5">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">
                    {{ $section['heading'] }}
                </p>
            </div>

            <div
                class="-mx-4 flex gap-3 overflow-x-auto px-4 pb-1 snap-x snap-mandatory scroll-smooth sm:mx-0 sm:grid sm:grid-cols-2 sm:gap-4 sm:overflow-visible sm:px-0 sm:pb-0 lg:grid-cols-3 xl:grid-cols-4"
            >
                @foreach ($section['items'] as $item)
                    @if ($item['available'])
                        @php($isActive = PatientMenu::isRouteActive($item['route']))
                        <a
                            href="{{ $item['href'] }}"
                            wire:navigate
                            class="group flex w-[11.5rem] shrink-0 snap-start flex-col rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm transition hover:border-[#10B981]/35 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#10B981]/30 sm:w-auto sm:min-h-[8.5rem] {{ $isActive ? 'border-[#10B981]/40 ring-1 ring-[#10B981]/20' : '' }}"
                        >
                            <span class="inline-flex size-11 items-center justify-center rounded-xl {{ $isActive ? 'bg-[#047857] text-white' : 'bg-[#10B981]/10 text-[#10B981] transition group-hover:bg-[#10B981]/15' }}">
                                <flux:icon :name="$item['icon']" variant="outline" class="size-5" />
                            </span>
                            <span class="mt-3 block text-sm font-semibold leading-snug text-zinc-900 group-hover:text-[#10B981]">
                                {{ $item['label'] }}
                            </span>
                            @if (isset($item['sub']) && filled($item['sub']))
                                <span class="mt-1 line-clamp-2 text-xs leading-relaxed text-zinc-500">
                                    {{ $item['sub'] }}
                                </span>
                            @endif
                        </a>
                    @endif
                @endforeach
            </div>
        </section>
    @endforeach
</nav>
