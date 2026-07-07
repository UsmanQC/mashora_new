@php
    $sessions = app(\App\Services\AppointmentSessionService::class);
    $diagnosis = $appointment->diagnosis;
    $medicationLabels = $this->medicationLabels;
    $priorSummary = $this->priorVisitSummary;
    $medicalHistories = $this->patientMedicalHistories;
@endphp

<div
    class="doctor-luxury-consultation relative flex h-svh max-h-svh min-h-0 flex-col overflow-hidden bg-slate-50 lg:hidden"
    data-test="doctor-luxury-consultation"
    wire:key="doctor-consultation-mobile-{{ $appointment->id }}"
    x-data="{ tab: 'summary', chatOpen: false, callActive: false, chatCardMinimized: false, hasNewMessage: false }"
    x-bind:class="{
        'doctor-consultation--call-active': callActive,
        'doctor-consultation--chat-open': chatOpen,
    }"
    x-on:consultation-call-active.window="callActive = true; chatOpen = false"
    x-on:consultation-call-ended.window="callActive = false; chatOpen = false"
    x-on:doctor-chat-message-received.window="if (chatCardMinimized) hasNewMessage = true"
>
    <header class="shrink-0 border-b border-slate-100 bg-white px-4 pb-3 pt-[max(2rem,env(safe-area-inset-top))]">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('doctor.appointments') }}"
                wire:navigate
                class="flex size-10 shrink-0 items-center justify-center rounded-full border border-slate-100 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50"
                aria-label="{{ __('doctor.consultation.back') }}"
            >
                <flux:icon name="chevron-left" variant="mini" class="size-5 rtl:rotate-180" />
            </a>
            <h1 class="min-w-0 flex-1 text-center text-base font-bold tracking-tight text-slate-900">
                {{ __('doctor.consultation.title') }}
            </h1>
            <div class="size-10 shrink-0" aria-hidden="true"></div>
        </div>
    </header>

    <div class="doctor-luxury-scroll min-h-0 flex-1 overflow-y-auto overscroll-y-auto pb-2">
        <div class="px-4 pt-4">
            @if (in_array($appointment->status, ['new', 'rescheduled'], true))
                @if ($appointment->isSessionStartRequestPending())
                    <flux:button type="button" variant="primary" icon="clock" class="doctor-luxury-btn-primary mb-3 w-full cursor-not-allowed !bg-[#047857] !text-white opacity-70" disabled>
                        {{ __('doctor.conversation.start_session_pending') }}
                    </flux:button>
                @elseif ($this->canPressStartSession($sessions))
                    <flux:button type="button" variant="primary" icon="play" class="doctor-luxury-btn-primary mb-3 w-full !bg-[#047857] !text-white" wire:click="startSession" wire:loading.attr="disabled">
                        {{ __('doctor.conversation.start_session') }}
                    </flux:button>
                @endif
            @endif

            @if ($appointment->status === 'in_process' && ! $this->sessionTimeExpired())
                <div class="mb-3 flex gap-2">
                    <button
                        type="button"
                        id="btn-agora-video-mobile"
                        onclick="window.mashoraDoctorStartVideoCall?.(event)"
                        @disabled($agoraAppId === '')
                        class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900 shadow-sm transition hover:border-[#047857]/30 disabled:opacity-45"
                    >
                        <flux:icon name="video-camera" variant="mini" class="size-5" />
                        <span class="btn-label">{{ __('doctor.conversation.video') }}</span>
                    </button>
                    <button
                        type="button"
                        id="btn-agora-audio-mobile"
                        onclick="window.mashoraDoctorStartAudioCall?.(event)"
                        @disabled($agoraAppId === '')
                        class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900 shadow-sm transition hover:border-[#047857]/30 disabled:opacity-45"
                    >
                        <flux:icon name="phone" variant="mini" class="size-5" />
                        <span class="btn-label">{{ __('doctor.conversation.voice') }}</span>
                    </button>
                </div>
            @endif

            <div class="flex items-center gap-3">
                <flux:avatar :name="$appointment->patient_name" circle size="md" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-base font-bold text-slate-900">{{ $appointment->patient_name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $this->patientMetaLine() }}</p>
                </div>
            </div>

            @if ($appointment->status === 'in_process' && ! $this->sessionTimeExpired())
            <div
                id="doctor-consultation-inline-video"
                class="doctor-consultation-inline-video relative mt-3 aspect-[4/3] overflow-hidden rounded-3xl bg-gradient-to-br from-[#064e3b] via-[#047857] to-[#059669] shadow-[0_8px_30px_-4px_rgba(4,120,87,0.35)]"
                data-test="doctor-consultation-inline-video"
            >
                <div
                    id="doctor-consultation-video-idle"
                    class="doctor-consultation-video-idle absolute inset-0 flex flex-col items-center justify-center gap-3 px-4 text-center"
                >
                    <div class="flex size-20 items-center justify-center rounded-full bg-[#10B981]/30 text-2xl font-bold text-white ring-4 ring-white/10">
                        {{ $this->patientInitials() }}
                    </div>
                    <p class="text-sm font-medium text-white/90">{{ $appointment->patient_name }}</p>
                    <p class="max-w-[14rem] text-xs leading-relaxed text-white/70">{{ __('doctor.consultation.waiting_for_call') }}</p>
                </div>

                <div id="agora-remote-player-mobile" class="doctor-consultation-remote absolute inset-0 z-0 h-full w-full"></div>

                <div class="pointer-events-none absolute start-3 top-3 z-10">
                    <span
                        id="doctor-consultation-call-quality"
                        class="hidden items-center gap-1.5 rounded-full bg-black/35 px-2.5 py-1 text-[0.625rem] font-semibold text-white backdrop-blur-sm"
                    >
                        <span class="size-1.5 rounded-full bg-emerald-400"></span>
                        {{ __('doctor.consultation.call_quality') }}
                    </span>
                </div>

                <div class="doctor-consultation-local-preview pointer-events-none absolute end-3 bottom-[4.75rem] z-10 w-[4.25rem] overflow-hidden rounded-2xl border border-white/20 bg-zinc-900/90 shadow-xl ring-1 ring-white/10">
                    <div id="agora-local-player-mobile" class="aspect-[3/4] w-full bg-zinc-800"></div>
                    <p class="px-1 py-0.5 text-center text-[0.5625rem] font-semibold text-zinc-300">{{ __('doctor.conversation.you') }}</p>
                </div>

                <div
                    id="doctor-consultation-call-controls-wrap"
                    class="pointer-events-none absolute inset-x-0 bottom-0 z-20 hidden flex justify-center px-4 pb-4 pt-8"
                >
                    <div
                        class="doctor-consultation-call-controls pointer-events-auto flex items-center justify-center gap-3 rounded-full border border-white/10 bg-zinc-900/90 px-3 py-2 shadow-xl backdrop-blur-md"
                        role="toolbar"
                        aria-label="{{ __('doctor.conversation.call_in_progress') }}"
                    >
                        <button
                            type="button"
                            id="agora-toggle-mic-mobile"
                            data-label-on="{{ __('doctor.conversation.mic') }}"
                            data-label-off="{{ __('doctor.conversation.mic_muted') }}"
                            aria-pressed="false"
                            disabled
                            class="doctor-consultation-call-controls__btn video-call-control hidden"
                            title="{{ __('doctor.conversation.mic') }}"
                        >
                            <flux:icon name="microphone" variant="mini" class="size-5 shrink-0" />
                        </button>
                        <button
                            type="button"
                            id="agora-leave-btn-mobile"
                            class="doctor-consultation-call-controls__btn doctor-consultation-call-controls__btn--leave hidden"
                            aria-label="{{ __('doctor.conversation.end_call') }}"
                        >
                            <flux:icon name="phone-x-mark" variant="mini" class="size-5 shrink-0" />
                        </button>
                        <button
                            type="button"
                            id="agora-toggle-chat-mobile"
                            x-on:click="chatOpen = !chatOpen"
                            x-bind:aria-pressed="chatOpen"
                            x-bind:title="chatOpen ? @js(__('doctor.consultation.close_chat')) : @js(__('doctor.consultation.open_chat'))"
                            x-bind:class="chatOpen ? 'doctor-consultation-call-controls__btn--active' : ''"
                            class="doctor-consultation-call-controls__btn hidden"
                            data-test="doctor-consultation-chat-toggle"
                        >
                            <flux:icon name="chat-bubble-left-right" variant="mini" class="size-5 shrink-0" />
                        </button>
                        <button
                            type="button"
                            id="agora-toggle-video-mobile"
                            data-label-on="{{ __('doctor.conversation.camera') }}"
                            data-label-off="{{ __('doctor.conversation.camera_off') }}"
                            aria-pressed="false"
                            disabled
                            class="doctor-consultation-call-controls__btn video-call-control hidden"
                            title="{{ __('doctor.conversation.camera') }}"
                        >
                            <flux:icon name="video-camera" variant="mini" class="size-5 shrink-0" />
                        </button>
                    </div>
                </div>
            </div>
            @elseif ($appointment->status === 'in_process')
                <div
                    class="doctor-consultation-session-ended mt-3 rounded-3xl border border-slate-200/90 bg-white p-5 shadow-sm"
                    data-test="doctor-consultation-session-ended"
                >
                    <div class="flex flex-col items-center text-center">
                        <span class="flex size-16 items-center justify-center rounded-full bg-slate-100 text-slate-500 ring-4 ring-slate-50">
                            <flux:icon name="clock" variant="mini" class="size-8" />
                        </span>
                        <p class="mt-3 text-sm font-bold text-slate-900">{{ __('doctor.consultation.session_time_ended_title') }}</p>
                        <p class="mt-2 max-w-[18rem] text-xs leading-relaxed text-slate-500">
                            {{ __('doctor.consultation.session_time_ended_hint') }}
                        </p>
                    </div>
                </div>
            @else
                <div
                    class="doctor-consultation-presession mt-3 rounded-3xl border border-slate-200/90 bg-white p-5 shadow-sm"
                    data-test="doctor-consultation-presession"
                >
                    <div class="flex flex-col items-center text-center">
                        <span class="flex size-16 items-center justify-center rounded-full bg-slate-100 text-slate-500 ring-4 ring-slate-50">
                            <flux:icon name="calendar-days" variant="mini" class="size-8" />
                        </span>
                        <p class="mt-3 text-sm font-bold text-slate-900">{{ __('doctor.consultation.session_not_started') }}</p>
                        <p class="mt-2 max-w-[18rem] text-xs leading-relaxed text-slate-500">
                            {{ $this->preSessionStatusMessage($sessions) }}
                        </p>
                        @if (in_array($appointment->status, ['new', 'rescheduled'], true))
                            <p class="mt-2 text-[0.6875rem] font-medium text-slate-400">
                                {{ __('doctor.consultation.review_while_waiting') }}
                            </p>
                        @endif
                    </div>
                </div>
            @endif

            @if ($this->appointmentDateLabel() || $this->appointmentTimeRangeLabel())
                <div class="mt-3 rounded-2xl border border-slate-100 bg-slate-50/90 px-3.5 py-3">
                    <div class="flex items-start gap-2.5">
                        <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl bg-white text-[#047857] shadow-sm ring-1 ring-slate-100">
                            <flux:icon name="clock" variant="mini" class="size-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            @if ($this->appointmentDateLabel() && $this->appointmentTimeRangeLabel())
                                <p class="text-xs font-semibold text-slate-500">{{ __('doctor.consultation.scheduled_for') }}</p>
                                <p class="mt-0.5 text-sm font-bold text-slate-900">
                                    {{ __('doctor.consultation.session_slot', [
                                        'date' => $this->appointmentDateLabel(),
                                        'time' => $this->appointmentTimeRangeLabel(),
                                    ]) }}
                                </p>
                            @elseif ($this->appointmentTimeRangeLabel())
                                <p class="text-sm font-bold text-slate-900">{{ $this->appointmentTimeRangeLabel() }}</p>
                            @endif

                            @if ($appointment->status === 'in_process' && $this->appointmentEndsAtLabel())
                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-slate-200/80 pt-2">
                                    <p class="text-xs text-slate-500">
                                        {{ __('doctor.consultation.ends_at', ['time' => $this->appointmentEndsAtLabel()]) }}
                                    </p>
                                    @if ($appointment->extend_at)
                                        <p class="inline-flex items-center gap-1.5 text-xs font-bold text-[#047857]">
                                            <span>{{ __('doctor.consultation.ends_in') }}</span>
                                            <span id="timer-session-remaining-mobile" class="font-mono tabular-nums">--:--</span>
                                        </p>
                                    @endif
                                </div>
                            @elseif ($this->appointmentEndsAtLabel())
                                <p class="mt-1 text-xs font-medium text-slate-500">
                                    {{ __('doctor.consultation.ends_at', ['time' => $this->appointmentEndsAtLabel()]) }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-4 rounded-2xl bg-slate-100/80 p-1">
                <div class="grid grid-cols-3 gap-1">
                    @foreach ([
                        'summary' => __('doctor.consultation.tab_summary'),
                        'history' => __('doctor.consultation.tab_history'),
                        'notes' => __('doctor.consultation.tab_notes'),
                    ] as $key => $label)
                        <button
                            type="button"
                            @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}'
                                ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80'
                                : 'text-slate-900 hover:text-slate-900'"
                            class="rounded-xl px-2 py-2.5 text-xs font-bold transition"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="mt-3 rounded-3xl border border-slate-100 bg-white p-4 shadow-sm">
                <div x-show="tab === 'summary'" x-cloak>
                    @if ($priorSummary)
                        <div class="flex gap-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[#10B981]/10 text-[#047857]">
                                <flux:icon name="sparkles" variant="mini" class="size-4" />
                            </span>
                            <p class="text-sm leading-relaxed text-slate-700">{{ $priorSummary }}</p>
                        </div>
                    @else
                        <p class="text-sm text-slate-500">{{ __('doctor.consultation.summary_empty') }}</p>
                    @endif
                </div>

                <div x-show="tab === 'history'" x-cloak class="space-y-3">
                    @forelse ($medicalHistories as $entry)
                        <div
                            wire:key="consultation-history-{{ $entry->id }}"
                            class="rounded-2xl border border-slate-100 bg-slate-50/80 p-3"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-xs font-bold text-slate-900">
                                    @if (filled($entry->appointment_number))
                                        #{{ $entry->appointment_number }}
                                    @else
                                        {{ __('doctor.consultation.history_visit') }}
                                    @endif
                                </p>
                                <time class="shrink-0 text-[0.625rem] font-medium text-slate-400">
                                    {{ optional($entry->scheduled_at)->locale(app()->getLocale())->translatedFormat('d M Y') }}
                                </time>
                            </div>
                            @if ($entry->diagnosis?->diagnosis_name)
                                <p class="mt-1.5 text-sm font-semibold text-[#047857]">{{ $entry->diagnosis->diagnosis_name }}</p>
                            @endif
                            @if ($entry->diagnosis?->medical_history)
                                <p class="mt-1 line-clamp-3 text-sm leading-relaxed text-slate-600">{{ $entry->diagnosis->medical_history }}</p>
                            @endif
                            @if ($entry->medications_count > 0)
                                <p class="mt-2 text-[0.625rem] font-semibold uppercase tracking-wide text-slate-400">
                                    {{ trans_choice('doctor.consultation.history_meds_count', $entry->medications_count, ['count' => $entry->medications_count]) }}
                                </p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('doctor.consultation.history_empty') }}</p>
                    @endforelse
                    @if ($medicalHistories->isNotEmpty())
                        <a
                            href="{{ route('doctor.appointments.medical-history', $appointment) }}"
                            wire:navigate
                            class="inline-flex items-center gap-1 text-sm font-semibold text-[#047857]"
                        >
                            {{ __('doctor.consultation.open_history') }}
                            <flux:icon name="arrow-right" variant="mini" class="size-4 rtl:rotate-180" />
                        </a>
                    @endif
                </div>

                <div x-show="tab === 'notes'" x-cloak>
                    <p class="whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $diagnosis?->doctor_notes ?: __('doctor.consultation.notes_empty') }}</p>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <a
                    href="{{ route('doctor.appointments.prescription', $appointment) }}"
                    wire:navigate
                    class="inline-flex min-h-12 flex-1 items-center justify-center gap-2 rounded-2xl bg-[#047857] px-4 text-sm font-bold text-white shadow-md transition hover:bg-[#059669]"
                >
                    <flux:icon name="beaker" variant="mini" class="size-5" />
                    {{ __('doctor.consultation.prescribe') }}
                </a>
                <flux:dropdown position="top">
                    <flux:button variant="outline" icon="ellipsis-horizontal" class="doctor-luxury-btn-muted !min-h-12 !min-w-12 !rounded-2xl !text-slate-900" />
                    <flux:menu>
                        <flux:menu.item :href="route('doctor.appointments.diagnosis', $appointment)" wire:navigate icon="document-text">
                            {{ __('doctor.workspace.tab_diagnosis') }}
                        </flux:menu.item>
                        @if ($appointment->status === 'in_process')
                            <flux:menu.item wire:click="requestCompleteAppointment" icon="check-circle">
                                {{ __('doctor.card.mark_complete') }}
                            </flux:menu.item>
                        @endif
                    </flux:menu>
                </flux:dropdown>
            </div>

            @if ($medicationLabels !== [])
                <div class="mt-4">
                    <p class="mb-2 text-[0.6875rem] font-bold uppercase tracking-wider text-slate-400">
                        {{ __('doctor.consultation.current_medications') }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($medicationLabels as $label)
                            <span class="inline-flex rounded-full bg-[#10B981]/10 px-3 py-1 text-xs font-semibold text-[#047857]">
                                {{ $label }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <section
        id="doctor-chat-panel-mobile"
        class="doctor-consultation-chat shrink-0 border-t border-slate-200/80 bg-white shadow-[0_-8px_30px_-12px_rgba(15,23,42,0.12)]"
        data-appointment-id="{{ $appointment->id }}"
        data-notify-url="{{ route('doctor.appointments.realtime.notify-call', $appointment) }}"
        data-end-call-url="{{ route('doctor.appointments.realtime.end-call', $appointment) }}"
        data-token-url="{{ route('doctor.appointments.realtime.agora-token', $appointment) }}"
        data-csrf="{{ csrf_token() }}"
    >
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-2.5">
            <div class="flex items-center gap-2">
                <span class="flex size-8 items-center justify-center rounded-full bg-[#10B981]/10 text-[#047857]">
                    <flux:icon name="chat-bubble-left-right" variant="mini" class="size-4" />
                </span>
                <div>
                    <p class="text-sm font-bold text-slate-900">{{ __('doctor.consultation.session_chat') }}</p>
                    <p class="text-[0.625rem] text-slate-500">{{ __('doctor.consultation.chat_subtitle') }}</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                @if ($appointment->status === 'in_process')
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[0.625rem] font-bold text-emerald-700">
                        <span class="size-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                        {{ __('doctor.conversation.live') }}
                    </span>
                @endif
                <button
                    type="button"
                    x-show="callActive && chatOpen"
                    x-cloak
                    x-on:click="chatOpen = false"
                    class="flex size-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50"
                    aria-label="{{ __('doctor.consultation.close_chat') }}"
                >
                    <flux:icon name="x-mark" variant="mini" class="size-4" />
                </button>
                <button
                    type="button"
                    x-on:click="chatCardMinimized = !chatCardMinimized; hasNewMessage = false"
                    class="relative flex size-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50"
                    x-bind:aria-label="chatCardMinimized ? @js(__('doctor.consultation.chat_expand')) : @js(__('doctor.consultation.chat_minimize'))"
                    data-test="doctor-chat-card-minimize-toggle-mobile"
                >
                    <flux:icon x-show="!chatCardMinimized" name="chevron-down" variant="mini" class="size-4" />
                    <flux:icon x-show="chatCardMinimized" x-cloak name="chevron-up" variant="mini" class="size-4" />
                    <span
                        x-show="hasNewMessage"
                        x-cloak
                        class="absolute -top-0.5 -end-0.5 flex size-2.5 rounded-full bg-rose-500 ring-2 ring-white"
                        aria-hidden="true"
                    ></span>
                </button>
            </div>
        </div>

        <div
            x-show="chatCardMinimized"
            x-cloak
            x-bind:class="callActive ? 'pb-[max(0.75rem,env(safe-area-inset-bottom))]' : 'pb-[calc(5.25rem+env(safe-area-inset-bottom))]'"
            class="px-4 pt-3 text-center"
        >
            <button
                type="button"
                x-on:click="chatCardMinimized = false; hasNewMessage = false"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#047857]"
            >
                <span x-show="hasNewMessage" x-cloak class="flex size-1.5 shrink-0 rounded-full bg-rose-500"></span>
                <span x-text="hasNewMessage ? @js(__('doctor.consultation.chat_new_message_hint')) : @js(__('doctor.consultation.chat_minimized_hint'))"></span>
            </button>
        </div>

        <div
            x-show="!chatCardMinimized"
            id="doctor-chat-messages-mobile"
            class="doctor-consultation-chat-messages max-h-44 space-y-3 overflow-y-auto px-4 py-3"
            wire:ignore.self
        >
            @forelse ($messages as $msg)
                <div
                    wire:key="doc-chat-mobile-{{ $msg['id'] }}"
                    @class([
                        'flex gap-2',
                        'flex-row-reverse' => $msg['send_by'] === 'doctor',
                    ])
                >
                    @if ($msg['send_by'] !== 'doctor')
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-slate-200 text-[0.625rem] font-bold text-slate-600">
                            {{ $this->patientInitials() }}
                        </span>
                    @endif
                    <div @class(['min-w-0 max-w-[78%]', 'text-end' => $msg['send_by'] === 'doctor'])>
                        <p @class([
                            'mb-0.5 text-[0.625rem] font-semibold uppercase tracking-wide text-slate-400',
                            'text-end' => $msg['send_by'] === 'doctor',
                        ])>
                            {{ $msg['send_by'] === 'doctor' ? __('doctor.consultation.chat_you') : $appointment->patient_name }}
                        </p>
                        <div
                            @class([
                                'inline-block rounded-2xl px-3.5 py-2 text-sm shadow-sm',
                                'rounded-br-md bg-[#047857] text-white' => $msg['send_by'] === 'doctor',
                                'rounded-bl-md border border-slate-100 bg-slate-50 text-slate-800' => $msg['send_by'] !== 'doctor',
                            ])
                        >
                            <p class="whitespace-pre-wrap break-words text-start">{{ $msg['body'] }}</p>
                        </div>
                        @if ($msg['created_at'])
                            <time @class(['mt-1 block text-[0.625rem] text-slate-400', 'text-end' => $msg['send_by'] === 'doctor'])>
                                {{ \Illuminate\Support\Carbon::parse($msg['created_at'])->timezone(config('app.timezone'))->format('H:i') }}
                            </time>
                        @endif
                    </div>
                </div>
            @empty
                <div class="doctor-consultation-chat-empty flex flex-col items-center justify-center py-6 text-center">
                    <div class="mb-2 flex size-12 items-center justify-center rounded-2xl bg-slate-100">
                        <flux:icon name="chat-bubble-left-ellipsis" class="size-6 text-slate-400" />
                    </div>
                    <p class="text-sm font-medium text-slate-600">{{ __('doctor.consultation.chat_empty_title') }}</p>
                    <p class="mt-0.5 text-xs text-slate-400">{{ __('doctor.conversation.empty_chat') }}</p>
                </div>
            @endforelse
        </div>

        <div x-show="!chatCardMinimized" class="border-t border-slate-100 bg-slate-50/80 px-4 py-3 pb-[calc(5.25rem+env(safe-area-inset-bottom))]">
            <form
                wire:submit="sendMessage"
                class="doctor-consultation-chat-form flex items-center gap-2 rounded-full border border-slate-200 bg-white py-1 pe-1 ps-4 shadow-sm @if (! $appointment->isDoctorChatOpen()) pointer-events-none opacity-55 @endif"
            >
                <input
                    wire:model="draft"
                    type="text"
                    placeholder="{{ __('doctor.consultation.chat_placeholder') }}"
                    @disabled(! $appointment->isDoctorChatOpen())
                    class="min-w-0 flex-1 border-0 bg-transparent text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                />
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    @disabled(! $appointment->isDoctorChatOpen())
                    class="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#047857] text-white shadow-md transition hover:bg-[#059669] disabled:opacity-50"
                    aria-label="{{ __('doctor.conversation.send') }}"
                >
                    <flux:icon name="paper-airplane" variant="mini" class="size-4 rtl:-scale-x-100" />
                </button>
            </form>
        </div>
    </section>

    <div class="pointer-events-none absolute -left-[9999px] top-0 h-0 w-0 overflow-hidden" aria-hidden="true">
        <div id="timer-session-elapsed-mobile">00:00</div>
        <div id="wrap-session-remaining-mobile"></div>
        <div id="call-status-chip-mobile" class="hidden"></div>
        <div id="call-type-label-mobile"></div>
        <div id="call-duration-display-mobile"></div>
    </div>
</div>
