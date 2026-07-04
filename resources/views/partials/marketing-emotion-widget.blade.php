<div class="marketing-emotion-widget relative z-10">
    <div class="bg-white/85 backdrop-blur-3xl border border-white/70 p-6 sm:p-8 lg:p-10 rounded-[2rem] lg:rounded-[2.5rem] shadow-premium">
        <div class="mb-8 lg:mb-10">
            <h2 class="text-2xl sm:text-3xl font-display font-bold text-ink mb-3 tracking-tight">{{ __('marketing.emotion.heading') }}</h2>
            <p class="text-sm sm:text-base text-ink-muted">{{ __('marketing.emotion.subtitle') }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:gap-5 mb-8 lg:mb-10">
            <button type="button" class="emotion-card bg-surface-subtle/50 border border-slate-100 p-5 lg:p-6 rounded-[1.5rem] flex items-start gap-4 lg:gap-5 text-start cursor-pointer group outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                <div class="icon-box w-11 h-11 lg:w-12 lg:h-12 shrink-0 rounded-2xl bg-white shadow-sm border border-slate-100 flex items-center justify-center text-primary group-hover:scale-105 group-hover:bg-primary/5 transition-all duration-500">
                    <i data-lucide="brain" class="w-5 h-5 lg:w-6 lg:h-6"></i>
                </div>
                <div class="pt-0.5 min-w-0">
                    <h3 class="font-display font-bold text-ink mb-1 group-hover:text-primary transition-colors text-base lg:text-lg">{{ __('marketing.emotion.anxiety_title') }}</h3>
                    <p class="text-sm text-ink-muted leading-relaxed">{{ __('marketing.emotion.anxiety_note') }}</p>
                </div>
            </button>

            <button type="button" class="emotion-card bg-surface-subtle/50 border border-slate-100 p-5 lg:p-6 rounded-[1.5rem] flex items-start gap-4 lg:gap-5 text-start cursor-pointer group outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                <div class="icon-box w-11 h-11 lg:w-12 lg:h-12 shrink-0 rounded-2xl bg-white shadow-sm border border-slate-100 flex items-center justify-center text-primary group-hover:scale-105 group-hover:bg-primary/5 transition-all duration-500">
                    <i data-lucide="cloud-rain" class="w-5 h-5 lg:w-6 lg:h-6"></i>
                </div>
                <div class="pt-0.5 min-w-0">
                    <h3 class="font-display font-bold text-ink mb-1 group-hover:text-primary transition-colors text-base lg:text-lg">{{ __('marketing.emotion.depression_title') }}</h3>
                    <p class="text-sm text-ink-muted leading-relaxed">{{ __('marketing.emotion.depression_note') }}</p>
                </div>
            </button>

            <button type="button" class="emotion-card bg-surface-subtle/50 border border-slate-100 p-5 lg:p-6 rounded-[1.5rem] flex items-start gap-4 lg:gap-5 text-start cursor-pointer group outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                <div class="icon-box w-11 h-11 lg:w-12 lg:h-12 shrink-0 rounded-2xl bg-white shadow-sm border border-slate-100 flex items-center justify-center text-primary group-hover:scale-105 group-hover:bg-primary/5 transition-all duration-500">
                    <i data-lucide="users" class="w-5 h-5 lg:w-6 lg:h-6"></i>
                </div>
                <div class="pt-0.5 min-w-0">
                    <h3 class="font-display font-bold text-ink mb-1 group-hover:text-primary transition-colors text-base lg:text-lg">{{ __('marketing.emotion.family_title') }}</h3>
                    <p class="text-sm text-ink-muted leading-relaxed">{{ __('marketing.emotion.family_note') }}</p>
                </div>
            </button>

            <button type="button" class="emotion-card bg-surface-subtle/50 border border-slate-100 p-5 lg:p-6 rounded-[1.5rem] flex items-start gap-4 lg:gap-5 text-start cursor-pointer group outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                <div class="icon-box w-11 h-11 lg:w-12 lg:h-12 shrink-0 rounded-2xl bg-white shadow-sm border border-slate-100 flex items-center justify-center text-primary group-hover:scale-105 group-hover:bg-primary/5 transition-all duration-500">
                    <i data-lucide="compass" class="w-5 h-5 lg:w-6 lg:h-6"></i>
                </div>
                <div class="pt-0.5 min-w-0">
                    <h3 class="font-display font-bold text-ink mb-1 group-hover:text-primary transition-colors text-base lg:text-lg">{{ __('marketing.emotion.growth_title') }}</h3>
                    <p class="text-sm text-ink-muted leading-relaxed">{{ __('marketing.emotion.growth_note') }}</p>
                </div>
            </button>
        </div>

        <div class="bg-primary-50/50 rounded-2xl p-5 lg:p-6 flex flex-col sm:flex-row items-center justify-between gap-5 border border-primary-100/50">
            <div class="flex items-center gap-4 lg:gap-5 w-full sm:w-auto">
                <div class="relative flex -space-x-3 rtl:space-x-reverse shrink-0">
                    @foreach (array_slice($featuredDoctors, 0, 2) as $previewDoctor)
                        @php
                            $previewPhoto = $previewDoctor['photo_url'] ?? null;
                            $previewAvatar = 'https://ui-avatars.com/api/?name='.urlencode((string) ($previewDoctor['name'] ?? 'Awaan')).'&background=10B981&color=fff&size=100';
                        @endphp
                        <img
                            class="w-10 h-10 lg:w-11 lg:h-11 rounded-full border-2 border-white object-cover shadow-sm"
                            src="{{ filled($previewPhoto) ? $previewPhoto : $previewAvatar }}"
                            alt="{{ $previewDoctor['name'] }}"
                            loading="lazy"
                        >
                    @endforeach
                    @if (($doctorStats['total'] ?? 0) > 2)
                        <div class="w-10 h-10 lg:w-11 lg:h-11 rounded-full border-2 border-white bg-primary text-white flex items-center justify-center text-xs font-bold shadow-sm">
                            +{{ ($doctorStats['total'] ?? 0) - min(2, count($featuredDoctors)) }}
                        </div>
                    @endif
                </div>
                <div class="min-w-0 text-start">
                    @if (($doctorStats['online'] ?? 0) > 0)
                        <p class="text-sm font-bold text-ink mb-0.5">{{ __('marketing.emotion.online_now', ['count' => $doctorStats['online']]) }}</p>
                    @else
                        <p class="text-sm font-bold text-ink mb-0.5">{{ __('marketing.emotion.licensed_total', ['count' => $doctorStats['total'] ?? 0]) }}</p>
                    @endif
                    <p class="text-xs text-primary-700 font-medium">{{ __('marketing.emotion.wait_time') }}</p>
                </div>
            </div>
            <a href="{{ route('patient.schedule.filter') }}" class="w-full sm:w-auto inline-flex justify-center bg-primary hover:bg-primary-600 text-white px-8 py-3.5 rounded-xl font-bold transition-all shadow-lg shadow-primary-500/20 hover:shadow-primary-500/40 hover:-translate-y-0.5">
                {{ __('marketing.emotion.start_session') }}
            </a>
        </div>
    </div>

    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0 hidden lg:block" aria-hidden="true">
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-amber-100/60 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-14 -left-10 w-48 h-48 bg-primary-200/50 rounded-full blur-3xl"></div>
    </div>
</div>
