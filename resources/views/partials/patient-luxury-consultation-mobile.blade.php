@php
    $doctorName = $appointment->doctor?->displayName() ?: __('patient.appointments.specialist_label');
    $doctorPhoto = $appointment->doctor?->profilePhotoUrl();
@endphp

<div
    class="patient-luxury-consultation-mobile relative flex h-svh max-h-svh min-h-0 flex-col overflow-hidden bg-slate-50 sm:hidden"
    data-test="patient-luxury-consultation-mobile"
    wire:key="patient-consultation-mobile-{{ $appointment->id }}"
    x-data="{ chatOpen: false, callActive: false, chatCardMinimized: false, hasNewMessage: false }"
    x-bind:class="{
        'patient-consultation--call-active': callActive,
        'patient-consultation--chat-open': chatOpen,
    }"
    x-on:patient-consultation-call-active.window="callActive = true; chatOpen = false"
    x-on:patient-consultation-call-ended.window="callActive = false; chatOpen = false"
    x-on:patient-chat-message-received.window="if (chatCardMinimized) hasNewMessage = true"
>
    <header class="shrink-0 border-b border-slate-100 bg-white px-4 pb-3 pt-[max(0.75rem,env(safe-area-inset-top))]">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('patient.appointments') }}"
                wire:navigate
                class="flex size-10 shrink-0 items-center justify-center rounded-full border border-slate-100 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50"
                aria-label="{{ __('patient.appointments.title') }}"
            >
                <flux:icon name="chevron-left" variant="mini" class="size-5 rtl:rotate-180" />
            </a>
            <div class="min-w-0 flex-1 text-center">
                <h1 class="truncate text-base font-bold tracking-tight text-slate-900">{{ $doctorName }}</h1>
                <p class="truncate text-xs text-slate-500">{{ $this->conversationHeaderSubtitle() }}</p>
            </div>
            <div class="size-10 shrink-0" aria-hidden="true"></div>
        </div>
    </header>

    <div class="patient-luxury-scroll min-h-0 flex-1 overflow-y-auto overscroll-y-auto pb-2">
        <div class="space-y-4 px-4 pt-4">
            @if ($this->canResolveMissed())
                <div class="rounded-2xl border border-orange-200/90 bg-gradient-to-r from-orange-50 to-amber-50/80 px-4 py-4 shadow-sm">
                    @include('partials.patient-luxury-missed-resolution', ['appointment' => $appointment])
                </div>
            @endif

            @if ($appointment->isSessionStartRequestPending())
                <div class="rounded-2xl border border-amber-200/90 bg-amber-50 px-4 py-4 shadow-sm">
                    <p class="text-sm font-bold text-amber-950">{{ __('patient.appointments.session_start_request_pending') }}</p>
                    <p class="mt-1 text-xs leading-relaxed text-amber-900">{{ __('patient.appointments.session_start_request_banner') }}</p>
                    <div class="mt-3 flex flex-col gap-2">
                        <flux:button type="button" variant="primary" class="w-full" wire:click="approveSessionStart" wire:loading.attr="disabled">
                            {{ __('patient.appointments.session_start_request_approve') }}
                        </flux:button>
                        <flux:button type="button" variant="outline" class="w-full !text-slate-900" wire:click="declineSessionStart" wire:loading.attr="disabled">
                            {{ __('patient.appointments.session_start_request_decline') }}
                        </flux:button>
                    </div>
                </div>
            @endif

            <div class="flex items-center gap-3">
                @if ($doctorPhoto)
                    <img src="{{ $doctorPhoto }}" alt="" class="size-12 shrink-0 rounded-full object-cover ring-2 ring-white shadow-sm" />
                @else
                    <flux:avatar :name="$doctorName" circle size="md" />
                @endif
                <div class="min-w-0 flex-1">
                    <p class="truncate text-base font-bold text-slate-900">{{ $doctorName }}</p>
                    <p class="truncate text-xs font-medium text-[#059669]">{{ $this->doctorSpecialtyLabel() }}</p>
                </div>
                @if ($appointment->status === 'in_process')
                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[0.625rem] font-bold text-emerald-700">
                        <span class="size-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                        {{ __('patient.appointments.luxury.live_session') }}
                    </span>
                @endif
            </div>

            @if ($this->appointmentDateLabel() || $this->appointmentTimeRangeLabel())
                <div class="rounded-2xl border border-slate-100 bg-slate-50/90 px-3.5 py-3">
                    <div class="flex items-start gap-2.5">
                        <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl bg-white text-[#10B981] shadow-sm ring-1 ring-slate-100">
                            <flux:icon name="clock" variant="mini" class="size-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            @if ($this->appointmentDateLabel() && $this->appointmentTimeRangeLabel())
                                <p class="text-xs font-semibold text-slate-500">{{ __('patient.appointments.luxury.scheduled_for') }}</p>
                                <p class="mt-0.5 text-sm font-bold text-slate-900">
                                    {{ __('patient.appointments.luxury.session_slot', [
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
                                        {{ __('patient.appointments.luxury.ends_at', ['time' => $this->appointmentEndsAtLabel()]) }}
                                    </p>
                                    @if ($appointment->extend_at)
                                        <p class="inline-flex items-center gap-1.5 text-xs font-bold text-[#10B981]">
                                            <span>{{ __('patient.appointments.luxury.ends_in') }}</span>
                                            <span id="patient-timer-session-remaining-mobile" class="font-mono tabular-nums">--:--</span>
                                        </p>
                                    @endif
                                </div>
                            @elseif ($this->appointmentEndsAtLabel())
                                <p class="mt-1 text-xs font-medium text-slate-500">
                                    {{ __('patient.appointments.luxury.ends_at', ['time' => $this->appointmentEndsAtLabel()]) }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if ($appointment->allowsPatientCalls())
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        id="patient-call-started-chip"
                        class="hidden inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700"
                    >
                        <span id="patient-call-chip-label">{{ __('patient.appointments.call_in_progress') }}</span>
                        <span id="patient-call-chip-duration" class="font-mono tabular-nums">00:00</span>
                    </span>
                    <span
                        id="patient-waiting-for-call-chip"
                        @class([
                            'inline-flex rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-600 shadow-sm',
                            'hidden' => $appointment->status !== 'in_process',
                        ])
                    >
                        {{ __('patient.appointments.waiting_for_specialist_call') }}
                    </span>
                </div>
            @endif

            @if (in_array($appointment->status, ['new', 'rescheduled'], true) && ! $appointment->isChatOpen())
                <div id="patient-chat-locked-callout" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs leading-relaxed text-slate-600 shadow-sm">
                    {{ __('patient.appointments.chat_locked_until_one_hour') }}
                </div>
            @elseif ($appointment->status === 'completed' && ! $appointment->isChatOpen())
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs text-slate-600 shadow-sm">
                    {{ __('patient.appointments.session_closed') }}
                </div>
            @endif

            @if ($appointment->status === 'in_process' && ! $this->sessionTimeExpired())
                <div
                    id="patient-consultation-inline-video"
                    class="patient-consultation-inline-video relative aspect-[4/3] overflow-hidden rounded-3xl bg-gradient-to-br from-[#064e3b] via-[#047857] to-[#059669] shadow-[0_8px_30px_-4px_rgba(16,185,129,0.35)]"
                    data-test="patient-consultation-inline-video"
                >
                    <div
                        id="patient-consultation-video-idle"
                        class="patient-consultation-video-idle absolute inset-0 flex flex-col items-center justify-center gap-3 px-4 text-center"
                    >
                        @if ($doctorPhoto)
                            <img src="{{ $doctorPhoto }}" alt="" class="size-20 rounded-full object-cover ring-4 ring-white/10" />
                        @else
                            <div class="flex size-20 items-center justify-center rounded-full bg-[#10B981]/30 text-2xl font-bold text-white ring-4 ring-white/10">
                                {{ $this->doctorInitials() }}
                            </div>
                        @endif
                        <p class="text-sm font-medium text-white/90">{{ $doctorName }}</p>
                        <p class="max-w-[16rem] text-xs leading-relaxed text-white/70">{{ $this->liveSessionIdleMessage() }}</p>
                    </div>

                    <div id="patient-agora-remote-mobile" class="patient-consultation-remote absolute inset-0 z-0 h-full w-full"></div>

                    <div class="doctor-consultation-local-preview pointer-events-none absolute end-3 bottom-[4.75rem] z-10 w-[4.25rem] overflow-hidden rounded-2xl border border-white/20 bg-zinc-900/90 shadow-xl ring-1 ring-white/10">
                        <div id="patient-agora-local-mobile" class="aspect-[3/4] w-full bg-zinc-800"></div>
                        <p class="px-1 py-0.5 text-center text-[0.5625rem] font-semibold text-zinc-300">{{ __('patient.appointments.you') }}</p>
                    </div>

                    <div
                    id="patient-consultation-call-controls-wrap"
                    class="pointer-events-none absolute inset-x-0 bottom-0 z-20 hidden flex justify-center px-4 pb-4 pt-8"
                    >
                        <div
                            class="doctor-consultation-call-controls pointer-events-auto flex items-center justify-center gap-3 rounded-full border border-white/10 bg-zinc-900/90 px-3 py-2 shadow-xl backdrop-blur-md"
                            role="toolbar"
                            aria-label="{{ __('patient.appointments.call_in_progress') }}"
                        >
                            <button
                                type="button"
                                id="patient-agora-toggle-mic-mobile"
                                data-label-on="{{ __('patient.appointments.mic') }}"
                                data-label-off="{{ __('patient.appointments.mic_muted') }}"
                                aria-pressed="false"
                                disabled
                                class="doctor-consultation-call-controls__btn video-call-control hidden"
                                title="{{ __('patient.appointments.mic') }}"
                            >
                                <flux:icon name="microphone" variant="mini" class="size-5 shrink-0" />
                            </button>
                            <button
                                type="button"
                                id="patient-agora-leave-mobile"
                                class="doctor-consultation-call-controls__btn doctor-consultation-call-controls__btn--leave hidden"
                                aria-label="{{ __('patient.appointments.end_call') }}"
                            >
                                <flux:icon name="phone-x-mark" variant="mini" class="size-5 shrink-0" />
                            </button>
                            <button
                                type="button"
                                id="patient-agora-toggle-chat-mobile"
                                x-on:click="chatOpen = !chatOpen"
                                x-bind:aria-pressed="chatOpen"
                                x-bind:title="chatOpen ? @js(__('patient.appointments.luxury.close_chat')) : @js(__('patient.appointments.luxury.open_chat'))"
                                x-bind:class="chatOpen ? 'doctor-consultation-call-controls__btn--active' : ''"
                                class="doctor-consultation-call-controls__btn hidden"
                                data-test="patient-consultation-chat-toggle"
                            >
                                <flux:icon name="chat-bubble-left-right" variant="mini" class="size-5 shrink-0" />
                            </button>
                            <button
                                type="button"
                                id="patient-agora-toggle-video-mobile"
                                data-label-on="{{ __('patient.appointments.camera') }}"
                                data-label-off="{{ __('patient.appointments.camera_off') }}"
                                aria-pressed="false"
                                disabled
                                class="doctor-consultation-call-controls__btn video-call-control hidden"
                                title="{{ __('patient.appointments.camera') }}"
                            >
                                <flux:icon name="video-camera" variant="mini" class="size-5 shrink-0" />
                            </button>
                        </div>
                    </div>
                </div>
            @elseif ($appointment->status === 'in_process')
                <div class="rounded-3xl border border-slate-200/90 bg-white p-5 shadow-sm" data-test="patient-consultation-session-ended">
                    <div class="flex flex-col items-center text-center">
                        <span class="flex size-16 items-center justify-center rounded-full bg-slate-100 text-slate-500 ring-4 ring-slate-50">
                            <flux:icon name="clock" variant="mini" class="size-8" />
                        </span>
                        <p class="mt-3 text-sm font-bold text-slate-900">{{ __('patient.appointments.luxury.session_time_ended_title') }}</p>
                        <p class="mt-2 max-w-[18rem] text-xs leading-relaxed text-slate-500">
                            {{ __('patient.appointments.luxury.session_time_ended_hint') }}
                        </p>
                    </div>
                </div>
            @else
                <div class="rounded-3xl border border-slate-200/90 bg-white p-5 shadow-sm" data-test="patient-consultation-presession">
                    <div class="flex flex-col items-center text-center">
                        <span class="flex size-16 items-center justify-center rounded-full bg-slate-100 text-slate-500 ring-4 ring-slate-50">
                            <flux:icon name="calendar-days" variant="mini" class="size-8" />
                        </span>
                        <p class="mt-3 text-sm font-bold text-slate-900">{{ __('patient.appointments.luxury.session_not_started') }}</p>
                        <p class="mt-2 max-w-[18rem] text-xs leading-relaxed text-slate-500">
                            {{ $this->preSessionStatusMessage() }}
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div
        id="incoming-call-banner"
        class="hidden shrink-0 border-t border-emerald-200/80 bg-gradient-to-r from-emerald-50 to-white px-4 py-3 shadow-[0_-4px_20px_-8px_rgba(16,185,129,0.35)]"
        role="alert"
        data-test="patient-incoming-call-banner"
    >
        <div class="flex flex-col gap-3">
            <div class="flex min-w-0 items-start gap-2.5">
                <span class="relative mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-[#10B981] text-white shadow-sm">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                    <flux:icon name="video-camera" variant="mini" class="relative size-4" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-emerald-950">{{ __('patient.appointments.incoming_call_title') }}</p>
                    <p id="incoming-call-label" class="mt-0.5 text-xs text-emerald-800"></p>
                </div>
            </div>
            <div class="flex gap-2">
                <button
                    type="button"
                    id="incoming-call-accept"
                    class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-2xl bg-[#10B981] px-4 text-sm font-bold text-white shadow-md transition hover:bg-[#059669]"
                >
                    <flux:icon name="video-camera" variant="mini" class="size-4" />
                    {{ __('patient.appointments.join_call') }}
                </button>
                <button
                    type="button"
                    id="incoming-call-dismiss"
                    class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-emerald-300 bg-white px-4 text-sm font-semibold text-emerald-800"
                >
                    {{ __('patient.appointments.dismiss_call') }}
                </button>
            </div>
        </div>
    </div>

    <section
        id="patient-chat-panel-mobile"
        class="patient-consultation-chat shrink-0 border-t border-slate-200/80 bg-white shadow-[0_-8px_30px_-12px_rgba(15,23,42,0.12)]"
        data-appointment-id="{{ $appointment->id }}"
        data-notify-url="{{ route('patient.appointments.realtime.notify-call', $appointment) }}"
        data-pending-call-url="{{ route('patient.appointments.realtime.pending-call', $appointment) }}"
        data-end-call-url="{{ route('patient.appointments.realtime.end-call', $appointment) }}"
        data-token-url="{{ route('patient.appointments.realtime.agora-token', $appointment) }}"
        data-csrf="{{ csrf_token() }}"
    >
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-2.5">
            <div class="flex items-center gap-2">
                <span class="flex size-8 items-center justify-center rounded-full bg-[#10B981]/10 text-[#10B981]">
                    <flux:icon name="chat-bubble-left-right" variant="mini" class="size-4" />
                </span>
                <div>
                    <p class="text-sm font-bold text-slate-900">{{ __('patient.appointments.luxury.session_chat') }}</p>
                    <p class="text-[0.625rem] text-slate-500">{{ __('patient.appointments.luxury.chat_subtitle') }}</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                @if ($appointment->status === 'in_process')
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[0.625rem] font-bold text-emerald-700">
                        <span class="size-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                        {{ __('patient.appointments.luxury.live_session') }}
                    </span>
                @endif
                <button
                    type="button"
                    x-show="callActive && chatOpen"
                    x-cloak
                    x-on:click="chatOpen = false"
                    class="flex size-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm"
                    aria-label="{{ __('patient.appointments.luxury.close_chat') }}"
                >
                    <flux:icon name="x-mark" variant="mini" class="size-4" />
                </button>
                <button
                    type="button"
                    x-on:click="chatCardMinimized = !chatCardMinimized; hasNewMessage = false"
                    class="relative flex size-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm"
                    x-bind:aria-label="chatCardMinimized ? @js(__('patient.appointments.luxury.chat_expand')) : @js(__('patient.appointments.luxury.chat_minimize'))"
                    data-test="patient-chat-card-minimize-toggle-mobile"
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
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#10B981]"
            >
                <span x-show="hasNewMessage" x-cloak class="flex size-1.5 shrink-0 rounded-full bg-rose-500"></span>
                <span x-text="hasNewMessage ? @js(__('patient.appointments.luxury.chat_new_message_hint')) : @js(__('patient.appointments.luxury.chat_minimized_hint'))"></span>
            </button>
        </div>

        <div
            x-show="!chatCardMinimized"
            id="patient-chat-messages-mobile"
            class="patient-consultation-chat-messages max-h-44 space-y-3 overflow-y-auto px-4 py-3"
            wire:ignore.self
        >
            @forelse ($messages as $msg)
                <div
                    wire:key="patient-chat-mobile-{{ $msg['id'] }}"
                    @class(['flex gap-2', 'flex-row-reverse' => $msg['send_by'] === 'patient'])
                >
                    @if ($msg['send_by'] !== 'patient')
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-slate-200 text-[0.625rem] font-bold text-slate-600">
                            {{ $this->doctorInitials() }}
                        </span>
                    @endif
                    <div @class(['min-w-0 max-w-[78%]', 'text-end' => $msg['send_by'] === 'patient'])>
                        <div
                            @class([
                                'inline-block rounded-2xl px-3.5 py-2 text-sm shadow-sm',
                                'rounded-br-md bg-[#10B981] text-white' => $msg['send_by'] === 'patient',
                                'rounded-bl-md border border-slate-100 bg-slate-50 text-slate-800' => $msg['send_by'] !== 'patient',
                            ])
                        >
                            <p class="whitespace-pre-wrap break-words text-start">{{ $msg['body'] }}</p>
                        </div>
                        @if ($msg['created_at'])
                            <time @class(['mt-1 block text-[0.625rem] text-slate-400', 'text-end' => $msg['send_by'] === 'patient'])>
                                {{ \Illuminate\Support\Carbon::parse($msg['created_at'])->timezone(config('app.timezone'))->format('H:i') }}
                            </time>
                        @endif
                    </div>
                </div>
            @empty
                <div class="patient-consultation-chat-empty flex flex-col items-center justify-center py-6 text-center">
                    <div class="mb-2 flex size-12 items-center justify-center rounded-2xl bg-slate-100">
                        <flux:icon name="chat-bubble-left-ellipsis" class="size-6 text-slate-400" />
                    </div>
                    <p class="text-sm font-medium text-slate-600">{{ __('patient.appointments.luxury.chat_empty_title') }}</p>
                    <p class="mt-0.5 text-xs text-slate-400">{{ __('patient.appointments.empty_chat') }}</p>
                </div>
            @endforelse
        </div>

        <div x-show="!chatCardMinimized" class="border-t border-slate-100 bg-slate-50/80 px-4 py-3 pb-[calc(5.25rem+env(safe-area-inset-bottom))]">
            <form
                wire:submit="sendMessage"
                class="patient-consultation-chat-form flex items-center gap-2 rounded-full border border-slate-200 bg-white py-1 pe-1 ps-4 shadow-sm @if (! $appointment->isChatOpen()) pointer-events-none opacity-55 @endif"
            >
                <input
                    wire:model="draft"
                    type="text"
                    placeholder="{{ __('patient.appointments.type_message') }}"
                    @disabled(! $appointment->isChatOpen())
                    class="min-w-0 flex-1 border-0 bg-transparent text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                />
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    @disabled(! $appointment->isChatOpen())
                    class="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#10B981] text-white shadow-md transition hover:bg-[#059669] disabled:opacity-50"
                    aria-label="{{ __('patient.appointments.send') }}"
                >
                    <flux:icon name="paper-airplane" variant="mini" class="size-4 rtl:-scale-x-100" />
                </button>
            </form>
        </div>
    </section>
</div>
