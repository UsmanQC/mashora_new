        @if ($favoriteSpecialists === [])
            <section
                class="relative mt-6 overflow-hidden rounded-3xl border border-slate-100/80 bg-white px-6 py-16 text-center shadow-[0_8px_32px_0_rgba(0,0,0,0.04)] sm:mt-10"
                aria-labelledby="patient-favorites-empty-heading"
                data-test="patient-favorites-empty"
            >
                <div class="pointer-events-none absolute -end-8 -top-8 size-32 rounded-full bg-emerald-100/40 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-10 -start-6 size-28 rounded-full bg-rose-100/30 blur-2xl"></div>

                <div class="relative mx-auto mb-4 flex size-20 items-center justify-center rounded-full bg-gradient-to-br from-rose-50 to-emerald-50 ring-1 ring-white shadow-inner">
                    <flux:icon name="heart" variant="outline" class="size-9 text-[#10B981]" />
                </div>

                <flux:heading id="patient-favorites-empty-heading" level="2" size="lg" class="relative font-semibold text-slate-900">
                    {{ __('patient.menu.favorites_empty_title') }}
                </flux:heading>

                <p class="relative mx-auto mt-3 max-w-sm text-sm leading-relaxed text-slate-500">
                    {{ __('patient.menu.favorites_empty_hint') }}
                </p>

                <a
                    href="{{ route('patient.schedule.filter') }}"
                    wire:navigate
                    class="relative mt-8 inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#10B981] to-[#059669] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-900/15 transition hover:brightness-[0.98]"
                >
                    <flux:icon name="magnifying-glass" variant="mini" class="size-4" />
                    {{ __('patient.menu.favorites_browse') }}
                </a>
            </section>
        @else
            <div class="hidden items-end justify-between gap-4 sm:mt-8 sm:flex">
                <div>
                    <flux:heading size="xl" class="font-semibold text-[#10B981]">{{ __('patient.menu.favorites') }}</flux:heading>
                    <flux:text class="mt-1 text-zinc-600">{{ __('patient.menu.favorites_sub') }}</flux:text>
                </div>

                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-4 py-2 text-sm font-semibold text-[#047857] shadow-sm">
                    <flux:icon name="heart" variant="solid" class="size-4 text-[#10B981]" />
                    {{ trans_choice('patient.menu.favorites_count', count($favoriteSpecialists), ['count' => count($favoriteSpecialists)]) }}
                </span>
            </div>

            <p class="mt-4 text-xs font-medium text-zinc-500 sm:hidden">
                {{ trans_choice('patient.menu.favorites_count', count($favoriteSpecialists), ['count' => count($favoriteSpecialists)]) }}
            </p>

            <div class="mt-4 space-y-5 sm:mt-6" data-test="patient-favorites-list">
                @foreach ($favoriteSpecialists as $specialist)
                    @include('partials.patient-favorite-doctor-card', [
                        'specialist' => $specialist,
                        'likes' => $likeCounts[$specialist['id']] ?? (int) ($specialist['likes'] ?? 0),
                        'likedByUser' => true,
                    ])
                @endforeach
            </div>

            <div class="mt-8 flex justify-center pb-2 sm:pb-0">
                <a
                    href="{{ route('patient.schedule.filter') }}"
                    wire:navigate
                    class="inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-white px-5 py-2.5 text-sm font-semibold text-[#059669] shadow-sm transition hover:border-emerald-200 hover:bg-emerald-50/50"
                >
                    <flux:icon name="plus" variant="mini" class="size-4" />
                    {{ __('patient.menu.favorites_browse') }}
                </a>
            </div>
        @endif