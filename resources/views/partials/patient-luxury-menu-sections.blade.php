@php
    use App\Support\PatientMenu;

    /** @var string|null $ariaLabel */
    $ariaLabel = $ariaLabel ?? __('patient.menu.grid_aria');
    $sections = PatientMenu::sections();
    $chevron = app()->getLocale() === 'ar' ? 'left' : 'right';
@endphp

<nav class="space-y-8 px-6 pb-6" aria-label="{{ $ariaLabel }}" data-test="patient-luxury-menu-sections">
    @foreach ($sections as $section)
        <section class="space-y-3">
            <h2 class="px-1 text-sm font-bold text-slate-900">{{ $section['heading'] }}</h2>

            <div class="space-y-3">
                @foreach ($section['items'] as $item)
                    @if ($item['available'])
                        @php($isActive = PatientMenu::isRouteActive($item['route']))
                        <a
                            href="{{ $item['href'] }}"
                            wire:navigate
                            @class([
                                'patient-luxury-menu-row active-scale flex items-center gap-4 rounded-2xl border bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] transition',
                                'border-[#10B981]/30 ring-1 ring-[#10B981]/15' => $isActive,
                                'border-slate-100 hover:border-emerald-200' => ! $isActive,
                            ])
                        >
                            <span @class([
                                'flex size-11 shrink-0 items-center justify-center rounded-xl',
                                'bg-[#10B981] text-white' => $isActive,
                                'bg-emerald-50 text-[#059669]' => ! $isActive,
                            ])>
                                <flux:icon :name="$item['icon']" variant="outline" class="size-5" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-slate-900">{{ $item['label'] }}</span>
                                @if (isset($item['sub']) && filled($item['sub']))
                                    <span class="mt-0.5 block text-xs text-slate-500">{{ $item['sub'] }}</span>
                                @endif
                            </span>
                            <flux:icon name="chevron-{{ $chevron }}" variant="outline" class="size-4 shrink-0 text-slate-300" />
                        </a>
                    @endif
                @endforeach
            </div>
        </section>
    @endforeach
</nav>
