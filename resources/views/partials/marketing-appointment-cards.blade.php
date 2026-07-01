@php
    $isAuthenticated = auth()->check();
    $scheduledUrl = route('patient.schedule.filter');
    $instantUrl = $isAuthenticated ? route('patient.schedule.filter') : route('patient.phone');
    $ongoingUrl = $isAuthenticated ? route('patient.appointments') : route('patient.phone');

    $cards = [
        [
            'url' => $scheduledUrl,
            'title' => __('patient.book_title'),
            'note' => __('patient.book_note'),
            'icon' => 'calendar-days',
            'delay' => '0.05s',
            'bar' => 'bg-orange-500',
            'iconBg' => 'from-orange-500 to-orange-600 shadow-orange-500/30',
            'hover' => 'hover:border-orange-200/90 hover:shadow-orange-500/10',
            'chevron' => 'text-orange-500',
            'ring' => 'focus-visible:ring-orange-400/40',
        ],
        [
            'url' => $instantUrl,
            'title' => __('patient.instant_title'),
            'note' => __('patient.instant_note'),
            'icon' => 'zap',
            'delay' => '0.12s',
            'bar' => 'bg-primary',
            'iconBg' => 'from-[#10B981] to-[#047857] shadow-primary/30',
            'hover' => 'hover:border-emerald-200/90 hover:shadow-primary/10',
            'chevron' => 'text-primary',
            'ring' => 'focus-visible:ring-primary/40',
        ],
        [
            'url' => $ongoingUrl,
            'title' => __('patient.ongoing_title'),
            'note' => __('patient.ongoing_note'),
            'icon' => 'clipboard-check',
            'delay' => '0.19s',
            'bar' => 'bg-sky-500',
            'iconBg' => 'from-sky-500 to-sky-600 shadow-sky-500/30',
            'hover' => 'hover:border-sky-200/90 hover:shadow-sky-500/10',
            'chevron' => 'text-sky-500',
            'ring' => 'focus-visible:ring-sky-400/40',
        ],
    ];
@endphp

<section class="marketing-appointments w-full">
    <div class="mb-4 text-right sm:mb-6">
        <h2 class="font-display text-xl font-bold tracking-tight text-ink sm:text-2xl">
            {{ __('patient.nav.appointments') }}
        </h2>
        <p class="mt-1 text-sm text-ink-muted sm:mt-1.5 sm:text-base">
            اختر نوع الجلسة التي تناسبك.
        </p>
    </div>

    <div class="flex flex-col gap-2.5 sm:grid sm:grid-cols-3 sm:gap-4">
        @foreach ($cards as $card)
            <a
                href="{{ $card['url'] }}"
                class="marketing-appt-card group relative flex items-center gap-3 overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-3.5 shadow-[0_4px_24px_-12px_rgba(15,23,42,0.14)] ring-1 ring-slate-900/[0.02] transition-all duration-300 {{ $card['hover'] }} hover:-translate-y-0.5 hover:shadow-[0_12px_32px_-14px_rgba(15,23,42,0.18)] active:scale-[0.985] focus:outline-none focus-visible:ring-2 {{ $card['ring'] }} opacity-0 animate-fade-in-up sm:min-h-[10.5rem] sm:flex-col sm:items-start sm:p-4"
                style="animation-delay: {{ $card['delay'] }};"
            >
                <span class="absolute inset-y-2.5 start-0 w-1 rounded-full {{ $card['bar'] }} sm:hidden" aria-hidden="true"></span>

                <div class="ms-1 flex size-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br {{ $card['iconBg'] }} text-white shadow-md transition duration-300 group-hover:scale-105 sm:ms-0 sm:mb-3 sm:size-11">
                    <i data-lucide="{{ $card['icon'] }}" class="size-5 sm:size-[1.35rem]"></i>
                </div>

                <div class="min-w-0 flex-1 text-right">
                    <h3 class="font-display text-[0.9375rem] font-bold leading-snug text-ink sm:text-base">{{ $card['title'] }}</h3>
                    <p class="mt-0.5 line-clamp-2 text-xs leading-relaxed text-ink-muted sm:text-sm">{{ $card['note'] }}</p>
                </div>

                <div class="flex shrink-0 items-center {{ $card['chevron'] }} transition duration-300 group-hover:-translate-x-0.5 sm:hidden">
                    <i data-lucide="chevron-left" class="size-5"></i>
                </div>

                <div class="mt-auto hidden items-center pt-1 {{ $card['chevron'] }} transition duration-300 group-hover:-translate-x-0.5 sm:flex">
                    <i data-lucide="chevron-left" class="size-5"></i>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Online specialists strip — hidden for now
    @if (($doctorStats['online'] ?? 0) > 0)
        <div
            class="mt-4 flex items-center justify-between gap-3 rounded-2xl border border-primary/10 bg-gradient-to-l from-primary-50/70 to-white px-4 py-3 opacity-0 animate-fade-in-up sm:mt-5 sm:px-5 sm:py-4"
            style="animation-delay: 0.28s;"
        >
            <div class="flex min-w-0 items-center gap-3">
                <div class="relative flex -space-x-2.5 rtl:space-x-reverse">
                    @foreach (array_slice($featuredDoctors, 0, 2) as $previewDoctor)
                        @php
                            $previewPhoto = $previewDoctor['photo_url'] ?? null;
                            $previewAvatar = 'https://ui-avatars.com/api/?name='.urlencode((string) ($previewDoctor['name'] ?? 'Awaan')).'&background=10B981&color=fff&size=100';
                        @endphp
                        <img
                            class="size-9 rounded-full border-2 border-white object-cover shadow-sm"
                            src="{{ filled($previewPhoto) ? $previewPhoto : $previewAvatar }}"
                            alt="{{ $previewDoctor['name'] }}"
                            loading="lazy"
                        >
                    @endforeach
                </div>
                <div class="min-w-0 text-right">
                    <p class="truncate text-sm font-bold text-ink">متاح {{ $doctorStats['online'] }} مختص الآن</p>
                    <p class="text-xs font-medium text-primary-700">أقل من دقيقة</p>
                </div>
            </div>
            <a
                href="{{ route('patient.phone') }}"
                class="shrink-0 rounded-xl bg-primary px-4 py-2 text-xs font-bold text-white shadow-md shadow-primary/20 transition hover:bg-primary-600 sm:px-5 sm:py-2.5 sm:text-sm"
            >
                ابدأ
            </a>
        </div>
    @endif
    --}}
</section>
