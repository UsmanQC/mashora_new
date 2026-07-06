@php
    /** @var \App\Models\Doctor|null $doc */
    $doc = $this->currentDoctor;
    $nextUp = $this->nextUpAppointment;
    $notificationsUrl = route('doctor.settings.notifications');
    $appointmentsUrl = route('doctor.appointments');
    $walletUrl = route('doctor.settings.wallet');
    $invoicesUrl = route('doctor.settings.invoices');
    $minutesUntil = $this->nextUpMinutesUntil;
@endphp

<div
    class="doctor-luxury-home relative flex h-[100dvh] flex-col overflow-hidden bg-slate-50 lg:hidden"
    data-test="doctor-luxury-home"
>
    <header class="shrink-0 bg-gradient-to-b from-white to-slate-50 px-5 pb-3 pt-[max(2.25rem,env(safe-area-inset-top))]">
        <div class="flex items-center justify-between gap-3">
            @if ($doc)
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-[#10B981]/10 ring-2 ring-white">
                        @if ($doc->profilePhotoUrl())
                            <img
                                src="{{ $doc->profilePhotoUrl() }}"
                                alt=""
                                class="size-full object-cover"
                            />
                        @else
                            <span class="text-sm font-bold text-[#047857]">{{ $this->initialsFor($doc->displayName()) }}</span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h1 class="truncate text-base font-bold tracking-tight text-slate-900">{{ $doc->displayName() }}</h1>
                        <p class="flex items-center gap-1.5 truncate text-[0.6875rem] text-slate-500">
                            <span class="size-1.5 shrink-0 rounded-full bg-[#10B981]" aria-hidden="true"></span>
                            <span>
                                {{ $doc->is_online ? __('doctor.mobile.available') : __('doctor.mobile.offline') }}
                                @if ($this->specialityLabel !== '')
                                    · {{ $this->specialityLabel }}
                                @endif
                            </span>
                        </p>
                    </div>
                </div>
            @endif

            <a
                href="{{ $notificationsUrl }}"
                wire:navigate
                class="relative flex size-10 shrink-0 items-center justify-center rounded-full border border-slate-100 bg-white text-[#047857] shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] transition-colors hover:text-[#059669]"
                aria-label="{{ __('doctor.mobile.notifications_aria') }}"
            >
                <flux:icon name="bell" variant="outline" class="size-[1.125rem]" />
                @if ($this->unreadNotificationCount > 0)
                    <span class="absolute top-2 end-2 size-1.5 rounded-full border-2 border-white bg-red-500" aria-hidden="true"></span>
                @endif
            </a>
        </div>
    </header>

    <main class="mx-auto flex min-h-0 w-full max-w-md flex-1 flex-col space-y-5 overflow-y-auto overscroll-contain px-5 pb-[calc(4.75rem+env(safe-area-inset-bottom))]">
        @if ($doc && $doc->status !== 'approved')
            <section class="rounded-3xl border border-slate-100 bg-white px-4 py-10 text-center shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                @include('partials.patient-empty-record-illustration')
                <p class="mt-4 text-base font-semibold text-[#10B981]">
                    {{ $doc->status === 'rejected' ? __('doctor.dashboard.verification_rejected_title') : __('doctor.dashboard.verification_pending_title') }}
                </p>
                <p class="mx-auto mt-3 max-w-xs text-sm text-slate-600">
                    {!! __('doctor.dashboard.verification_body_html', ['email' => 'contact@awaan.io']) !!}
                </p>
            </section>
        @elseif ($doc && $doc->status === 'approved')
            {{-- Up next --}}
            @if ($nextUp)
                @php
                    $upNextLabel = $minutesUntil !== null && $minutesUntil <= 0
                        ? __('doctor.mobile.up_next_now')
                        : ($minutesUntil !== null
                            ? __('doctor.mobile.up_next_in', ['minutes' => max(1, $minutesUntil)])
                            : __('doctor.mobile.up_next_soon'));
                @endphp
                <section
                    class="shrink-0 overflow-hidden rounded-3xl bg-gradient-to-br from-[#047857] via-[#059669] to-[#10B981] p-5 text-white shadow-[0_8px_30px_-4px_rgba(4,120,87,0.35)]"
                    data-test="doctor-luxury-up-next"
                >
                    <p class="mb-4 flex items-center gap-1.5 text-[0.625rem] font-semibold uppercase tracking-wider text-white/80">
                        <span class="size-1.5 rounded-full bg-white/90" aria-hidden="true"></span>
                        {{ $upNextLabel }}
                    </p>

                    <div class="mb-5 flex items-center gap-3">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-sm font-bold text-white backdrop-blur-sm">
                            {{ $this->initialsFor($nextUp->patient_name) }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-lg font-bold">{{ $nextUp->patient_name }}</p>
                            <p class="truncate text-xs text-white/75">
                                {{ __('doctor.mobile.session_line', ['duration' => $nextUp->duration]) }}
                            </p>
                        </div>
                    </div>

                    <dl class="mb-5 grid grid-cols-3 gap-2 text-center">
                        <div>
                            <dd class="text-lg font-bold tabular-nums">{{ $nextUp->formattedSessionStart() ?: '—' }}</dd>
                            <dt class="text-[0.625rem] text-white/70">{{ __('doctor.mobile.start') }}</dt>
                        </div>
                        <div>
                            <dd class="text-lg font-bold tabular-nums">{{ __('doctor.dashboard.minutes_label', ['m' => $nextUp->duration]) }}</dd>
                            <dt class="text-[0.625rem] text-white/70">{{ __('doctor.mobile.duration') }}</dt>
                        </div>
                        <div>
                            <dd class="text-lg font-bold tabular-nums">{{ $this->patientVisitNumber($nextUp) }}</dd>
                            <dt class="text-[0.625rem] text-white/70">{{ __('doctor.mobile.visit') }}</dt>
                        </div>
                    </dl>

                    <a
                        href="{{ route('doctor.appointments.conversation', $nextUp) }}"
                        wire:navigate
                        class="flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-white px-4 text-sm font-bold text-[#047857] shadow-sm transition hover:bg-white/95 active:scale-[0.98]"
                    >
                        <flux:icon name="video-camera" variant="mini" class="size-5" />
                        {{ __('doctor.mobile.open_consultation') }}
                    </a>
                </section>
            @endif

            {{-- Today at a glance --}}
            <section>
                <h2 class="mb-3 text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500">
                    {{ __('doctor.mobile.today_glance') }}
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-3xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                        <div class="mb-3 flex size-9 items-center justify-center rounded-xl bg-[#10B981]/10 text-[#047857]">
                            <flux:icon name="banknotes" variant="outline" class="size-[1.125rem]" />
                        </div>
                        <p class="text-xl font-bold tabular-nums text-slate-900">
                            {{ $this->todayEarningsFormatted }}
                            <span class="text-sm font-semibold text-[#10B981]">{{ __('doctor.dashboard.sar_suffix') }}</span>
                        </p>
                        <p class="mt-0.5 text-[0.6875rem] text-slate-500">{{ __('doctor.mobile.todays_earnings') }}</p>
                    </div>

                    <div class="rounded-3xl border border-slate-100 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                        <div class="mb-3 flex size-9 items-center justify-center rounded-xl bg-[#10B981]/10 text-[#047857]">
                            <flux:icon name="calendar-days" variant="outline" class="size-[1.125rem]" />
                        </div>
                        <p class="text-xl font-bold tabular-nums text-slate-900">
                            {{ $this->todayCompletedAppointmentsCount }}
                            <span class="text-sm font-semibold text-slate-400">/</span>
                            {{ $this->todayTotalAppointmentsCount }}
                        </p>
                        <p class="mt-0.5 text-[0.6875rem] text-slate-500">{{ __('doctor.dashboard.stat_appointments') }}</p>
                        <p class="mt-1 text-[0.625rem] font-medium text-slate-400">
                            {{ __('doctor.mobile.remaining', ['count' => $this->todayRemainingAppointmentsCount]) }}
                        </p>
                    </div>
                </div>
            </section>

            {{-- Quick actions --}}
            <section>
                <h2 class="mb-3 text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500">
                    {{ __('doctor.mobile.quick_actions') }}
                </h2>
                <div class="grid grid-cols-4 gap-2">
                    @foreach ([
                        ['href' => $nextUp ? route('doctor.appointments.conversation', $nextUp) : $appointmentsUrl, 'icon' => 'video-camera', 'label' => __('doctor.mobile.action_consult')],
                        ['href' => $appointmentsUrl, 'icon' => 'clipboard-document-list', 'label' => __('doctor.mobile.action_prescribe')],
                        ['href' => $walletUrl, 'icon' => 'credit-card', 'label' => __('doctor.mobile.action_wallet')],
                        ['href' => $invoicesUrl, 'icon' => 'document-text', 'label' => __('doctor.mobile.action_invoice')],
                    ] as $action)
                        <a
                            href="{{ $action['href'] }}"
                            wire:navigate
                            class="flex flex-col items-center gap-2 rounded-2xl border border-slate-100 bg-white px-1 py-3 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] transition active:scale-[0.97]"
                        >
                            <span class="flex size-10 items-center justify-center rounded-xl bg-[#10B981]/10 text-[#047857]">
                                <flux:icon :name="$action['icon']" variant="outline" class="size-5" />
                            </span>
                            <span class="text-center text-[0.625rem] font-semibold leading-tight text-slate-900">{{ $action['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- Needs your attention --}}
            <section>
                <h2 class="mb-3 text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500">
                    {{ __('doctor.mobile.needs_attention') }}
                </h2>
                <div class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                    @foreach ([
                        [
                            'href' => $appointmentsUrl,
                            'icon' => 'clipboard-document-list',
                            'icon_bg' => 'bg-teal-50 text-teal-600',
                            'title' => __('doctor.mobile.prescription_requests'),
                            'subtitle' => __('doctor.mobile.prescription_requests_sub'),
                            'count' => $this->prescriptionPendingCount,
                        ],
                        [
                            'href' => route('doctor.dashboard', ['upcoming' => 'follow_up']),
                            'icon' => 'arrow-path',
                            'icon_bg' => 'bg-orange-50 text-orange-600',
                            'title' => __('doctor.mobile.follow_ups_due'),
                            'subtitle' => __('doctor.mobile.follow_ups_due_sub', ['count' => $this->upcomingFollowUpCount]),
                            'count' => $this->upcomingFollowUpCount,
                        ],
                        [
                            'href' => $notificationsUrl,
                            'icon' => 'chat-bubble-left-right',
                            'icon_bg' => 'bg-sky-50 text-sky-600',
                            'title' => __('doctor.mobile.unread_messages'),
                            'subtitle' => __('doctor.mobile.unread_messages_sub'),
                            'count' => $this->unreadNotificationCount,
                        ],
                        [
                            'href' => $appointmentsUrl,
                            'icon' => 'check-circle',
                            'icon_bg' => 'bg-violet-50 text-violet-600',
                            'title' => __('doctor.mobile.tasks'),
                            'subtitle' => __('doctor.mobile.tasks_sub'),
                            'count' => $this->activeTasksCount,
                        ],
                    ] as $index => $item)
                        <a
                            href="{{ $item['href'] }}"
                            wire:navigate
                            @class([
                                'flex items-center gap-3 px-4 py-3.5 transition hover:bg-slate-50/80',
                                'border-b border-slate-100' => $index < 3,
                            ])
                        >
                            <span @class(['flex size-10 shrink-0 items-center justify-center rounded-xl', $item['icon_bg']])>
                                <flux:icon :name="$item['icon']" variant="outline" class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $item['title'] }}</p>
                                <p class="truncate text-[0.6875rem] text-slate-500">{{ $item['subtitle'] }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <span class="text-base font-bold tabular-nums text-slate-900">{{ $item['count'] }}</span>
                                <flux:icon name="chevron-right" variant="mini" class="size-4 text-slate-300 rtl:rotate-180" />
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- Recent activity --}}
            @if ($this->recentNotifications->isNotEmpty())
                <section>
                    <h2 class="mb-3 text-[0.6875rem] font-bold uppercase tracking-wider text-slate-500">
                        {{ __('doctor.mobile.recent_activity') }}
                    </h2>
                    <div class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)]">
                        @foreach ($this->recentNotifications->take(3) as $notification)
                            <a
                                href="{{ filled($notification->action) ? $notification->action : $notificationsUrl }}"
                                wire:navigate
                                @class([
                                    'flex items-start gap-3 px-4 py-3.5 transition hover:bg-slate-50/80',
                                    'border-b border-slate-100' => ! $loop->last,
                                ])
                            >
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                    <flux:icon name="bell" variant="outline" class="size-5" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="line-clamp-2 text-sm text-slate-700">
                                        <span class="font-semibold text-slate-900">{{ $notification->displayTitle() }}</span>
                                        {{ $notification->displayMessage() !== $notification->displayTitle() ? ' — '.$notification->displayMessage() : '' }}
                                    </p>
                                </div>
                                <time class="shrink-0 text-[0.625rem] font-medium text-slate-400" datetime="{{ $notification->created_at?->toIso8601String() }}">
                                    {{ $notification->created_at?->diffForHumans(short: true) }}
                                </time>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
    </main>
</div>
