@php
    $isAuthenticated = auth()->check();
    $phoneEntry = route('patient.phone');
    $filterUrl = route('patient.schedule.filter');
    $appointmentsUrl = $isAuthenticated ? route('patient.appointments') : $phoneEntry;
    $recordsUrl = $isAuthenticated ? route('patient.appointments', ['tab' => 'completed']) : $phoneEntry;
    $notificationsUrl = $isAuthenticated ? route('patient.notifications') : $phoneEntry;
    $profileUrl = $isAuthenticated ? route('profile.edit') : $phoneEntry;
@endphp

<div class="patient-luxury-home relative flex h-[100dvh] flex-col overflow-hidden bg-slate-50 sm:hidden" data-test="patient-luxury-home">
    <header class="shrink-0 bg-gradient-to-b from-white to-slate-50 px-6 pb-3 pt-[max(2.25rem,env(safe-area-inset-top))]">
        <div class="flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                @if ($isAuthenticated)
                    <a
                        href="{{ $profileUrl }}"
                        wire:navigate
                        class="relative shrink-0"
                        aria-label="{{ __('patient.nav.my_account') }}"
                        data-test="patient-luxury-home-avatar"
                    >
                        @if ($this->profilePhotoUrl !== null)
                            <img
                                src="{{ $this->profilePhotoUrl }}"
                                alt=""
                                class="size-11 rounded-full object-cover shadow-sm ring-2 ring-white"
                            />
                        @else
                            <flux:avatar :name="Auth::user()?->name ?? ''" circle class="size-11 ring-2 ring-white" />
                        @endif
                        <span class="absolute bottom-0 end-0 size-3 rounded-full border-2 border-white bg-[#10B981]" aria-hidden="true"></span>
                    </a>
                    <div class="min-w-0">
                        <p class="mb-0.5 text-[0.6875rem] font-medium text-slate-500">{{ $this->greetingLabel }}</p>
                        <h1 class="truncate text-base font-bold tracking-tight text-slate-900">{{ Auth::user()?->name }}</h1>
                    </div>
                @else
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-[#10B981]/10 ring-2 ring-white">
                        @include('partials.patient-brand-logo', [
                            'svgClass' => 'h-5 w-auto max-w-[4rem] object-contain',
                            'onGreenChrome' => false,
                        ])
                    </div>
                    <div class="min-w-0">
                        <p class="mb-0.5 text-[0.6875rem] font-medium text-slate-500">{{ __('patient.home_luxury.guest_greeting') }}</p>
                        <h1 class="truncate text-base font-bold tracking-tight text-slate-900">{{ __('patient.welcome_guest') }}</h1>
                    </div>
                @endif
            </div>

            <div class="flex shrink-0 items-center gap-2">
                @include('partials.patient-language-switch', ['variant' => 'luxury'])

                @if ($isAuthenticated)
                    <a
                        href="{{ $notificationsUrl }}"
                        wire:navigate
                        class="relative flex size-9 shrink-0 items-center justify-center rounded-full border border-slate-100 bg-white text-slate-600 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] transition-colors hover:text-[#059669]"
                        aria-label="{{ __('patient.home_luxury.notifications_aria') }}"
                    >
                        <flux:icon name="bell" variant="outline" class="size-[1.125rem]" />
                        @if ($this->unreadNotificationCount > 0)
                            <span class="absolute top-2 end-2 size-1.5 rounded-full border-2 border-white bg-red-500" aria-hidden="true"></span>
                        @endif
                    </a>
                @else
                    <a
                        href="{{ $phoneEntry }}"
                        wire:navigate
                        class="shrink-0 rounded-full bg-[#10B981] px-3.5 py-1.5 text-[0.6875rem] font-bold text-white shadow-sm transition-colors hover:bg-[#059669]"
                    >
                        {{ __('patient.home_luxury.sign_in') }}
                    </a>
                @endif
            </div>
        </div>
    </header>

    <main class="mx-auto flex min-h-0 w-full max-w-md flex-1 flex-col space-y-6 overflow-y-auto overscroll-contain px-6 pb-[calc(4.75rem+env(safe-area-inset-bottom))]">
        <section class="shrink-0 rounded-3xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
            <div class="mb-3 flex items-end justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="mb-0.5 text-sm font-bold text-slate-900">{{ __('patient.mood_section') }}</h2>
                    <p class="text-[0.6875rem] leading-snug text-slate-500">{{ __('patient.home_luxury.mood_hint') }}</p>
                </div>
                <flux:icon name="chart-bar" variant="outline" class="size-4 shrink-0 text-[#10B981] opacity-50" />
            </div>

            <div class="patient-luxury-mood-row -mx-1 flex items-start gap-2 overflow-x-auto overscroll-x-contain pb-1">
                @foreach ($this->moodOptions as $mood)
                    @php
                        $isSelected = $isAuthenticated && $this->todayMoodKey === $mood['key'];
                    @endphp
                    <button
                        type="button"
                        wire:click="selectMoodQuick('{{ $mood['key'] }}')"
                        wire:key="luxury-mood-{{ $mood['key'] }}"
                        @class([
                            'patient-luxury-mood-btn shrink-0',
                            'is-active' => $isSelected,
                        ])
                        aria-label="{{ $mood['label'] }}"
                        aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                    >
                        <span class="patient-luxury-mood-icon">
                            @if ($mood['image_url'] !== null)
                                <img
                                    src="{{ $mood['image_url'] }}"
                                    alt=""
                                    @class([
                                        'pointer-events-none size-7 object-contain',
                                        'grayscale-[25%]' => ! $isSelected,
                                    ])
                                    decoding="async"
                                    loading="lazy"
                                />
                            @else
                                <span @class([
                                    'pointer-events-none text-xl leading-none',
                                    'opacity-70' => ! $isSelected,
                                ]) aria-hidden="true">{{ $mood['emoji'] }}</span>
                            @endif
                        </span>
                        <span class="patient-luxury-mood-label">{{ $mood['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        <section class="space-y-4">
            <h2 class="px-1 text-base font-bold text-slate-900">{{ __('patient.home_luxury.actions_heading') }}</h2>

            <a
                href="{{ $isAuthenticated ? $filterUrl : $phoneEntry }}"
                wire:navigate
                class="patient-luxury-action-card group relative block overflow-hidden rounded-3xl bg-[#10B981] p-5 shadow-[0_10px_40px_-10px_rgba(16,185,129,0.35)] transition-colors hover:bg-[#059669]"
            >
                <div class="absolute end-0 top-0 size-32 translate-x-1/2 -translate-y-1/2 rounded-full bg-white/20 blur-3xl" aria-hidden="true"></div>
                <div class="relative z-10 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex size-12 items-center justify-center rounded-2xl border border-white/20 bg-white/15 text-white backdrop-blur-sm">
                            <flux:icon name="bolt" variant="outline" class="size-6 shrink-0" />
                        </div>
                        <div>
                            <h3 class="mb-1 text-sm font-semibold text-white">{{ __('patient.home_luxury.instant_title') }}</h3>
                            <p class="text-xs text-emerald-50/80">{{ __('patient.home_luxury.instant_note') }}</p>
                        </div>
                    </div>
                    <flux:icon
                        name="chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"
                        variant="outline"
                        class="size-5 text-white/60 transition-all duration-300 group-hover:-translate-x-1 group-hover:text-white rtl:group-hover:translate-x-1"
                    />
                </div>
            </a>

            @if ($isAuthenticated && ($activeSession = $this->activeSessionAppointment))
                <a
                    href="{{ route('patient.appointments.conversation', ['appointment' => $activeSession->id]) }}"
                    wire:navigate
                    class="patient-luxury-action-card group block rounded-3xl border border-blue-100 bg-blue-50 p-5 transition-colors hover:bg-blue-100/50"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="relative flex size-12 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm">
                                <span class="absolute end-0 top-0 size-3 animate-ping rounded-full border-2 border-white bg-blue-500" aria-hidden="true"></span>
                                <span class="absolute end-0 top-0 size-3 rounded-full border-2 border-white bg-blue-500" aria-hidden="true"></span>
                                <flux:icon name="video-camera" variant="outline" class="size-6 shrink-0" />
                            </div>
                            <div>
                                <h3 class="mb-1 text-sm font-semibold text-blue-900">{{ __('patient.home_luxury.active_session_title') }}</h3>
                                <p class="text-xs text-blue-600/80">
                                    {{ __('patient.home_luxury.active_session_note', ['doctor' => $activeSession->doctor?->displayName() ?? '']) }}
                                </p>
                            </div>
                        </div>
                        <span class="rounded-full bg-white px-3 py-1.5 text-xs font-bold text-blue-600 shadow-sm">
                            {{ __('patient.home_luxury.active_session_join') }}
                        </span>
                    </div>
                </a>
            @endif

            <a
                href="{{ $isAuthenticated ? $filterUrl : $phoneEntry }}"
                wire:navigate
                class="patient-luxury-action-card group block rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] transition-colors hover:border-emerald-200"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex size-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-600 transition-colors group-hover:bg-emerald-50 group-hover:text-[#059669]">
                            <flux:icon name="calendar-days" variant="outline" class="size-6 shrink-0" />
                        </div>
                        <div>
                            <h3 class="mb-1 text-sm font-semibold text-slate-900">{{ __('patient.book_title') }}</h3>
                            <p class="text-xs text-slate-500">{{ __('patient.book_note') }}</p>
                        </div>
                    </div>
                    <flux:icon
                        name="chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"
                        variant="outline"
                        class="size-5 text-slate-300 transition-all duration-300 group-hover:-translate-x-1 group-hover:text-[#10B981] rtl:group-hover:translate-x-1"
                    />
                </div>
            </a>

            <a
                href="{{ $recordsUrl }}"
                wire:navigate
                class="patient-luxury-action-card group block rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] transition-colors hover:border-slate-300"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex size-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-600">
                            <flux:icon name="folder-open" variant="outline" class="size-6 shrink-0" />
                        </div>
                        <div>
                            <h3 class="mb-1 text-sm font-semibold text-slate-900">{{ __('patient.home_luxury.records_title') }}</h3>
                            <p class="text-xs text-slate-500">{{ __('patient.home_luxury.records_note') }}</p>
                        </div>
                    </div>
                    <flux:icon
                        name="chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}"
                        variant="outline"
                        class="size-5 text-slate-300 transition-all duration-300 group-hover:-translate-x-1 group-hover:text-slate-600 rtl:group-hover:translate-x-1"
                    />
                </div>
            </a>
        </section>
    </main>
</div>
