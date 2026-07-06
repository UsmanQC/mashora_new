@php
    $sessions = app(\App\Services\AppointmentSessionService::class);
    $diagnosis = $appointment->diagnosis;
    $medicationLabels = $this->medicationLabels;
    $priorSummary = $this->priorVisitSummary;
@endphp

<div
    class="doctor-luxury-consultation relative flex h-svh max-h-svh min-h-0 flex-col overflow-hidden bg-slate-50 lg:hidden"
    data-test="doctor-luxury-consultation"
    x-data="{ tab: 'summary' }"
>
    <header class="shrink-0 border-b border-slate-100 bg-white px-4 pb-3 pt-[max(2rem,env(safe-area-inset-top))]">
        <div class="flex items-center justify-between gap-3">
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
            <div
                id="doctor-consultation-rec-badge"
                @class([
                    'inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[0.625rem] font-bold uppercase tracking-wide',
                    'bg-rose-50 text-rose-700 ring-1 ring-rose-200/80' => $appointment->status === 'in_process',
                    'invisible bg-transparent text-transparent ring-0' => $appointment->status !== 'in_process',
                ])
            >
                <span class="size-1.5 rounded-full bg-rose-500" aria-hidden="true"></span>
                <span>{{ __('doctor.consultation.rec') }}</span>
                <span id="doctor-consultation-rec-timer" class="font-mono tabular-nums">00:00</span>
            </div>
        </div>
    </header>

    <div class="doctor-luxury-scroll min-h-0 flex-1 overflow-y-auto overscroll-y-auto">
        <div
            id="doctor-consultation-inline-video"
            @class([
                'doctor-consultation-inline-video relative mx-4 mt-4 overflow-hidden rounded-3xl bg-gradient-to-br from-[#064e3b] via-[#047857] to-[#059669] shadow-[0_8px_30px_-4px_rgba(4,120,87,0.35)]',
                'aspect-[4/3]' => true,
            ])
            data-test="doctor-consultation-inline-video"
        >
            <div
                id="doctor-consultation-video-idle"
                class="doctor-consultation-video-idle absolute inset-0 flex flex-col items-center justify-center gap-3 px-4 text-center"
            >
                <div class="flex size-24 items-center justify-center rounded-full bg-[#10B981]/30 text-3xl font-bold text-white ring-4 ring-white/10">
                    {{ $this->patientInitials() }}
                </div>
                <p class="text-sm font-medium text-white/80">{{ $appointment->patient_name }}</p>
                @if ($appointment->status === 'in_process')
                    <p class="text-xs text-white/60">{{ __('doctor.conversation.status_in_process') }}</p>
                @endif
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

            <div class="doctor-consultation-local-preview pointer-events-none absolute end-3 top-3 z-10 w-16 overflow-hidden rounded-2xl border border-white/20 bg-zinc-900/90 shadow-xl ring-1 ring-white/10">
                <div id="agora-local-player-mobile" class="aspect-[3/4] w-full bg-zinc-800"></div>
                <p class="px-1 py-0.5 text-center text-[0.5625rem] font-semibold text-zinc-300">{{ __('doctor.conversation.you') }}</p>
            </div>

            <div class="pointer-events-none absolute inset-x-0 bottom-0 z-20 flex justify-center px-4 pb-4 pt-10">
                <div class="pointer-events-auto flex items-center gap-2 rounded-full border border-white/10 bg-zinc-900/85 px-3 py-2 shadow-xl backdrop-blur-md">
                    <button
                        type="button"
                        id="agora-toggle-mic-mobile"
                        data-label-on="{{ __('doctor.conversation.mic') }}"
                        data-label-off="{{ __('doctor.conversation.mic_muted') }}"
                        aria-pressed="false"
                        disabled
                        class="video-call-control hidden size-11 items-center justify-center rounded-full border border-white/15 bg-zinc-800/90 text-white transition hover:bg-zinc-700/90 disabled:opacity-50"
                        title="{{ __('doctor.conversation.mic') }}"
                    >
                        <flux:icon name="microphone" variant="mini" class="size-5" />
                    </button>
                    <button
                        type="button"
                        id="agora-toggle-video-mobile"
                        data-label-on="{{ __('doctor.conversation.camera') }}"
                        data-label-off="{{ __('doctor.conversation.camera_off') }}"
                        aria-pressed="false"
                        disabled
                        class="video-call-control hidden size-11 items-center justify-center rounded-full border border-white/15 bg-zinc-800/90 text-white transition hover:bg-zinc-700/90 disabled:opacity-50"
                        title="{{ __('doctor.conversation.camera') }}"
                    >
                        <flux:icon name="video-camera" variant="mini" class="size-5" />
                    </button>
                    <button
                        type="button"
                        id="agora-leave-btn-mobile"
                        class="hidden size-11 items-center justify-center rounded-full bg-rose-600 text-white shadow-lg transition hover:bg-rose-500"
                        aria-label="{{ __('doctor.conversation.end_call') }}"
                    >
                        <flux:icon name="phone-x-mark" variant="mini" class="size-5" />
                    </button>
                </div>
            </div>
        </div>

        <div class="px-4 pt-4">
            @if (in_array($appointment->status, ['new', 'rescheduled'], true))
                @if ($appointment->isSessionStartRequestPending())
                    <flux:button type="button" variant="primary" icon="clock" class="mb-3 w-full cursor-not-allowed opacity-70" disabled>
                        {{ __('doctor.conversation.start_session_pending') }}
                    </flux:button>
                @elseif ($this->canPressStartSession($sessions))
                    <flux:button type="button" variant="primary" icon="play" class="mb-3 w-full" wire:click="startSession" wire:loading.attr="disabled">
                        {{ __('doctor.conversation.start_session') }}
                    </flux:button>
                @endif
            @endif

            @if ($appointment->status === 'in_process')
                <div class="mb-3 flex gap-2">
                    <button
                        type="button"
                        id="btn-agora-video-mobile"
                        onclick="window.mashoraDoctorStartVideoCall?.(event)"
                        @disabled($agoraAppId === '')
                        class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-[#047857]/30 disabled:opacity-45"
                    >
                        <flux:icon name="video-camera" variant="mini" class="size-5" />
                        <span class="btn-label">{{ __('doctor.conversation.video') }}</span>
                    </button>
                    <button
                        type="button"
                        id="btn-agora-audio-mobile"
                        onclick="window.mashoraDoctorStartAudioCall?.(event)"
                        @disabled($agoraAppId === '')
                        class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-[#047857]/30 disabled:opacity-45"
                    >
                        <flux:icon name="phone" variant="mini" class="size-5" />
                        <span class="btn-label">{{ __('doctor.conversation.voice') }}</span>
                    </button>
                </div>
            @endif

            <div class="flex items-center gap-3">
                <flux:avatar :name="$appointment->patient_name" circle size="md" />
                <div class="min-w-0">
                    <p class="truncate text-base font-bold text-slate-900">{{ $appointment->patient_name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $this->patientMetaLine() }}</p>
                </div>
            </div>

            <div class="mt-3 grid grid-cols-5 gap-2 rounded-2xl border border-slate-100 bg-white p-3 shadow-sm">
                @foreach ([
                    ['label' => __('doctor.consultation.vital_bp'), 'value' => '—'],
                    ['label' => __('doctor.consultation.vital_hr'), 'value' => '—'],
                    ['label' => __('doctor.consultation.vital_spo2'), 'value' => '—'],
                    ['label' => __('doctor.consultation.vital_temp'), 'value' => '—'],
                    ['label' => __('doctor.consultation.vital_bmi'), 'value' => '—'],
                ] as $vital)
                    <div class="text-center">
                        <p class="text-sm font-bold tabular-nums text-slate-900">{{ $vital['value'] }}</p>
                        <p class="mt-0.5 text-[0.5625rem] font-semibold uppercase tracking-wide text-slate-400">{{ $vital['label'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 rounded-2xl bg-slate-100/80 p-1">
                <div class="grid grid-cols-4 gap-1">
                    @foreach ([
                        'summary' => __('doctor.consultation.tab_summary'),
                        'soap' => __('doctor.consultation.tab_soap'),
                        'history' => __('doctor.consultation.tab_history'),
                        'notes' => __('doctor.consultation.tab_notes'),
                    ] as $key => $label)
                        <button
                            type="button"
                            @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}'
                                ? 'bg-white text-[#047857] shadow-sm ring-1 ring-slate-200/80'
                                : 'text-slate-500 hover:text-slate-800'"
                            class="rounded-xl px-2 py-2 text-[0.6875rem] font-bold transition"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="mt-3 min-h-[5.5rem] rounded-3xl border border-slate-100 bg-white p-4 shadow-sm">
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
                <div x-show="tab === 'soap'" x-cloak class="space-y-2 text-sm text-slate-700">
                    <p><span class="font-semibold text-slate-900">{{ __('doctor.consultation.soap_subjective') }}:</span> {{ $diagnosis?->medical_history ?: '—' }}</p>
                    <p><span class="font-semibold text-slate-900">{{ __('doctor.consultation.soap_assessment') }}:</span> {{ $diagnosis?->diagnosis_name ?: '—' }}</p>
                    <p><span class="font-semibold text-slate-900">{{ __('doctor.consultation.soap_plan') }}:</span> {{ $diagnosis?->treatment_plan ?: '—' }}</p>
                </div>
                <div x-show="tab === 'history'" x-cloak>
                    <p class="text-sm text-slate-700">{{ $diagnosis?->medical_history ?: __('doctor.consultation.history_empty') }}</p>
                    <a href="{{ route('doctor.appointments.medical-history', $appointment) }}" wire:navigate class="mt-3 inline-flex text-sm font-semibold text-[#047857]">
                        {{ __('doctor.consultation.open_history') }}
                    </a>
                </div>
                <div x-show="tab === 'notes'" x-cloak>
                    <p class="whitespace-pre-line text-sm text-slate-700">{{ $diagnosis?->doctor_notes ?: __('doctor.consultation.notes_empty') }}</p>
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
                    <flux:button variant="outline" icon="ellipsis-horizontal" class="!min-h-12 !min-w-12 !rounded-2xl" />
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

            <section class="mt-5 pb-2">
                <h2 class="mb-2 text-[0.6875rem] font-bold uppercase tracking-wider text-slate-400">
                    {{ __('doctor.consultation.session_chat') }}
                </h2>
                <div
                    id="doctor-chat-panel-mobile"
                    class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-sm"
                    data-appointment-id="{{ $appointment->id }}"
                    data-notify-url="{{ route('doctor.appointments.realtime.notify-call', $appointment) }}"
                    data-end-call-url="{{ route('doctor.appointments.realtime.end-call', $appointment) }}"
                    data-token-url="{{ route('doctor.appointments.realtime.agora-token', $appointment) }}"
                    data-csrf="{{ csrf_token() }}"
                >
                    <div
                        id="doctor-chat-messages-mobile"
                        class="doctor-consultation-chat-messages max-h-52 space-y-2.5 overflow-y-auto px-3 py-3"
                        wire:ignore.self
                    >
                        @forelse ($messages as $msg)
                            <div
                                wire:key="doc-chat-mobile-{{ $msg['id'] }}"
                                @class([
                                    'flex',
                                    'justify-end' => $msg['send_by'] === 'doctor',
                                    'justify-start' => $msg['send_by'] !== 'doctor',
                                ])
                            >
                                <div
                                    @class([
                                        'max-w-[85%] rounded-2xl px-3 py-2 text-sm shadow-sm',
                                        'bg-[#047857] text-white' => $msg['send_by'] === 'doctor',
                                        'border border-slate-100 bg-slate-50 text-slate-800' => $msg['send_by'] !== 'doctor',
                                    ])
                                >
                                    <p class="whitespace-pre-wrap break-words">{{ $msg['body'] }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="doctor-consultation-chat-empty flex flex-col items-center justify-center py-8 text-center">
                                <flux:icon name="chat-bubble-left-right" class="mb-2 size-6 text-slate-300" />
                                <p class="text-xs text-slate-500">{{ __('doctor.conversation.empty_chat') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="shrink-0 border-t border-slate-100 bg-white px-4 py-3 pb-[calc(5.5rem+env(safe-area-inset-bottom))]">
        <form
            wire:submit="sendMessage"
            class="flex items-end gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-1.5 @if (! $appointment->isDoctorChatOpen()) pointer-events-none opacity-55 @endif"
        >
            <flux:input
                wire:model="draft"
                type="text"
                :placeholder="__('doctor.consultation.chat_placeholder')"
                :disabled="! $appointment->isDoctorChatOpen()"
                class="min-w-0 flex-1 !rounded-xl !border-0 !bg-transparent !shadow-none"
            />
            <flux:button
                type="submit"
                variant="primary"
                icon="paper-airplane"
                class="shrink-0 !rounded-xl"
                wire:loading.attr="disabled"
                :disabled="! $appointment->isDoctorChatOpen()"
            />
        </form>
    </div>

    <div class="pointer-events-none absolute -left-[9999px] top-0 h-0 w-0 overflow-hidden" aria-hidden="true">
        <div id="timer-session-elapsed-mobile">00:00</div>
        <div id="timer-session-remaining-mobile">--:--</div>
        <div id="wrap-session-remaining-mobile"></div>
        <div id="call-status-chip-mobile" class="hidden"></div>
        <div id="call-type-label-mobile"></div>
        <div id="call-duration-display-mobile"></div>
    </div>
</div>
