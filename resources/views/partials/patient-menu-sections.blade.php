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
                <p class="text-sm font-bold text-slate-900">
                    {{ $section['heading'] }}
                </p>
            </div>

            <div
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                @foreach ($section['items'] as $item)
                    @if ($item['available'])
                        @php($isActive = PatientMenu::isRouteActive($item['route']))
                        <a
                            href="{{ $item['href'] }}"
                            wire:navigate
                            class="group flex min-h-[8.5rem] flex-col rounded-3xl border border-slate-100/80 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] transition hover:border-emerald-200 hover:shadow-[0_8px_32px_0_rgba(0,0,0,0.04)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#10B981]/30 {{ $isActive ? 'border-[#10B981]/30 ring-1 ring-[#10B981]/15' : '' }}"
                        >
                            <span class="inline-flex size-11 items-center justify-center rounded-xl {{ $isActive ? 'bg-[#10B981] text-white' : 'bg-emerald-50 text-[#059669] transition group-hover:bg-emerald-100' }}">
                                <flux:icon :name="$item['icon']" variant="outline" class="size-5" />
                            </span>
                            <span class="mt-3 block text-sm font-semibold leading-snug text-slate-900 group-hover:text-[#059669]">
                                {{ $item['label'] }}
                            </span>
                            @if (isset($item['sub']) && filled($item['sub']))
                                <span class="mt-1 line-clamp-2 text-xs leading-relaxed text-slate-500">
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
