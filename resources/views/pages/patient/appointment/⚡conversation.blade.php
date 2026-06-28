<?php

use App\Events\AppointmentChatMessageSent;
use App\Models\Appointment;
use App\Models\ChMessage;
use App\Models\User;
use App\Support\DoctorAgoraChannel;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient')] #[Title('Session conversation')] class extends Component
{
    public Appointment $appointment;

    /**
     * @var list<array{id: string, body: string|null, send_by: string, created_at: string|null, from_id: int|null}>
     */
    public array $messages = [];

    public string $draft = '';

    public string $agoraAppId = '';

    public string $agoraToken = '';

    public string $agoraChannel = '';

    public function mount(Appointment $appointment): void
    {
        abort_unless((int) $appointment->user_id === (int) auth()->id(), 403);

        $this->appointment = $appointment;
        $this->refreshAgoraCredentials();
        $this->loadMessages();
    }

    public function sendMessage(): void
    {
        if (! $this->appointment->isChatOpen()) {
            return;
        }

        $this->validate([
            'draft' => ['required', 'string', 'max:5000'],
        ], [], ['draft' => __('patient.appointments.message_field')]);

        /** @var User|null $patient */
        $patient = auth()->user();
        if (! $patient instanceof User) {
            return;
        }

        $message = ChMessage::query()->create([
            'from_id' => $patient->id,
            'to_id' => (int) $this->appointment->doctor_id,
            'appointment_id' => $this->appointment->id,
            'body' => $this->draft,
            'send_by' => 'patient',
            'seen' => false,
        ]);

        broadcast(new AppointmentChatMessageSent($message));

        $this->reset('draft');
        $this->loadMessages();
    }

    public function loadMessages(): void
    {
        $this->messages = $this->appointment->chMessages()
            ->orderBy('created_at')
            ->get()
            ->map(fn (ChMessage $m) => [
                'id' => $m->id,
                'body' => $m->body,
                'send_by' => (string) $m->send_by,
                'created_at' => $m->created_at?->toIso8601String(),
                'from_id' => $m->from_id !== null ? (int) $m->from_id : null,
            ])
            ->all();
    }

    protected function refreshAgoraCredentials(): void
    {
        $appId = (string) config('agora.AGORA_APP_ID');
        $certificate = (string) config('agora.AGORA_APP_CERTIFICATE');

        if ($appId === '' || $certificate === '') {
            $this->agoraAppId = '';
            $this->agoraToken = '';
            $this->agoraChannel = '';

            return;
        }

        $this->agoraAppId = $appId;
        $this->agoraChannel = DoctorAgoraChannel::channelName($this->appointment);
        $this->agoraToken = DoctorAgoraChannel::buildRtcToken($this->agoraChannel);
    }

    public function refreshAppointmentSession(): void
    {
        if (! in_array((string) $this->appointment->status, ['new', 'rescheduled'], true)) {
            return;
        }

        $this->appointment->refresh();
    }

    public function formattedAppointmentTime(): string
    {
        $raw = (string) $this->appointment->start_time;
        if ($raw === '') {
            return '--:--';
        }

        try {
            return \Illuminate\Support\Carbon::createFromFormat('H:i:s', $raw)->format('h:i A');
        } catch (\Throwable) {
            try {
                return \Illuminate\Support\Carbon::parse($raw)->format('h:i A');
            } catch (\Throwable) {
                return $raw;
            }
        }
    }
}; ?>

<div
    class="space-y-5"
    @if (in_array($appointment->status, ['new', 'rescheduled'], true)) wire:poll.5s="refreshAppointmentSession" @endif
>
    <header class="relative overflow-hidden rounded-2xl border border-zinc-200/80 bg-gradient-to-br from-white via-white to-[#f7f9ff] p-4 shadow-sm shadow-zinc-200/60 ring-1 ring-zinc-100 sm:p-5">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#10B981] via-[#34d399] to-[#059669] opacity-85"></div>
        <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('patient.appointments') }}"
                wire:navigate
                class="inline-flex size-10 items-center justify-center rounded-full border border-zinc-200 bg-white text-[#10B981] shadow-sm transition hover:bg-zinc-50"
            >
                <flux:icon name="chevron-left" variant="outline" class="size-6 rtl:rotate-180" />
            </a>
            <div>
                <h1 class="text-lg font-semibold tracking-tight text-zinc-900">{{ $appointment->doctor?->displayName() ?: __('patient.appointments.title') }}</h1>
                <p class="mt-0.5 text-xs text-zinc-500">{{ __('patient.appointments.status_'.$appointment->status) }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if ($appointment->allowsPatientCalls())
                <span
                    id="patient-call-started-chip"
                    class="hidden rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700"
                >
                    {{ __('patient.appointments.call_in_progress') }}
                </span>
                @if ($appointment->status === 'in_process')
                    <span
                        id="patient-waiting-for-call-chip"
                        class="rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-[11px] font-semibold text-zinc-600"
                    >
                        {{ __('patient.appointments.waiting_for_specialist_call') }}
                    </span>
                @endif
            @endif
        </div>
        </div>
    </header>

    <div
        id="patient-conversation-metrics"
        class="hidden"
        data-status="{{ $appointment->status }}"
        data-session-start="{{ $appointment->actual_start_at?->toIso8601String() }}"
        data-session-end="{{ $appointment->extend_at?->toIso8601String() }}"
        data-session-scheduled-time="{{ filled($appointment->start_time) ? \Illuminate\Support\Carbon::createFromFormat('H:i:s', (string) $appointment->start_time)->format('h:i A') : '--:--' }}"
        data-session-not-started="{{ __('patient.appointments.session_not_started') }}"
        data-session-started-waiting="{{ __('patient.appointments.session_started_waiting') }}"
        data-label-connecting="{{ __('patient.appointments.connecting') }}"
        data-label-call-failed="{{ __('patient.appointments.call_failed') }}"
        data-label-camera-permission="{{ __('patient.appointments.camera_permission_required') }}"
        data-label-agora-sdk-missing="{{ __('patient.appointments.agora_sdk_missing') }}"
        data-label-no-active-call="{{ __('patient.appointments.no_active_call') }}"
        data-session-ended="{{ __('patient.appointments.session_time_ended') }}"
    ></div>

    @if (in_array($appointment->status, ['new', 'rescheduled'], true) && ! $appointment->isChatOpen())
        <flux:callout variant="secondary" icon="clock" class="border-zinc-200">
            {{ __('patient.appointments.chat_locked_until_doctor_starts') }}
        </flux:callout>
    @elseif ($appointment->status === 'completed' && ! $appointment->isChatOpen())
        <flux:callout variant="secondary" icon="check-circle" class="border-zinc-200">
            {{ __('patient.appointments.session_closed') }}
        </flux:callout>
    @elseif ($appointment->status === 'completed' && $appointment->isChatOpen())
        <flux:callout variant="success" icon="chat-bubble-left-right" class="border-emerald-200">
            {{ __('patient.appointments.chat_open_after_completed', [
                'date' => $appointment->chatOpenUntil()->locale(app()->getLocale())->translatedFormat('d M Y'),
            ]) }}
        </flux:callout>
    @endif

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:max-w-xl">
        <div class="rounded-xl border border-zinc-200/80 bg-gradient-to-br from-white to-zinc-50 px-3 py-2 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">{{ __('patient.appointments.session_elapsed_label') }}</p>
            <p id="patient-timer-session-elapsed" class="mt-0.5 font-mono text-lg font-semibold tabular-nums text-zinc-900">{{ $this->formattedAppointmentTime() }}</p>
        </div>
        <div id="patient-wrap-session-remaining" class="rounded-xl border border-zinc-200/80 bg-gradient-to-br from-white to-zinc-50 px-3 py-2 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">{{ __('patient.appointments.session_remaining_label') }}</p>
            <p id="patient-timer-session-remaining" class="mt-0.5 font-mono text-lg font-semibold tabular-nums text-[#047857]">--:--</p>
        </div>
    </div>

    @if (filled(config('broadcasting.connections.pusher.key')) && config('broadcasting.default') !== 'pusher')
        <flux:callout variant="warning" icon="exclamation-triangle" class="border-amber-200">
            {{ __('patient.appointments.realtime_misconfigured') }}
        </flux:callout>
    @endif

    <div
        id="patient-chat-panel"
        class="overflow-hidden rounded-3xl border border-zinc-200/90 bg-white shadow-[0_20px_55px_-32px_rgba(15,23,42,0.35)] ring-1 ring-zinc-100"
        data-appointment-id="{{ $appointment->id }}"
        data-notify-url="{{ route('patient.appointments.realtime.notify-call', $appointment) }}"
        data-token-url="{{ route('patient.appointments.realtime.agora-token', $appointment) }}"
        data-csrf="{{ csrf_token() }}"
    >
        <div class="grid min-h-[34rem] grid-cols-1 lg:grid-cols-12">
            <div class="flex min-h-[30rem] flex-col border-zinc-200 lg:col-span-8 lg:border-e">
                <div
                    id="incoming-call-banner"
                    class="hidden shrink-0 border-b border-emerald-300 bg-gradient-to-r from-emerald-50 via-emerald-50/95 to-white px-4 py-3 shadow-md shadow-emerald-900/10 ring-1 ring-inset ring-emerald-200/80 sm:px-5"
                    role="alert"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-start gap-2.5">
                            <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white shadow-sm shadow-emerald-900/25">
                                <flux:icon name="video-camera" variant="mini" class="size-4" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-emerald-950">{{ __('patient.appointments.incoming_call_title') }}</p>
                                <p id="incoming-call-label" class="mt-0.5 text-sm text-emerald-800"></p>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <button
                                type="button"
                                id="incoming-call-accept"
                                class="inline-flex min-h-10 items-center justify-center rounded-xl bg-[#10B981] px-4 py-2 text-sm font-semibold text-white shadow-md shadow-emerald-900/20 transition hover:brightness-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                            >
                                {{ __('patient.appointments.join_call') }}
                            </button>
                            <button
                                type="button"
                                id="incoming-call-dismiss"
                                class="inline-flex min-h-10 items-center justify-center rounded-xl border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2"
                            >
                                {{ __('patient.appointments.dismiss_call') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div id="patient-chat-messages" class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-gradient-to-b from-zinc-50/90 via-zinc-50/70 to-zinc-100/70 px-4 py-4 sm:px-5">
            @forelse ($messages as $msg)
                <div @class(['flex', 'justify-end' => $msg['send_by'] === 'patient', 'justify-start' => $msg['send_by'] !== 'patient']) wire:key="patient-chat-{{ $msg['id'] }}">
                    <div @class([
                        'max-w-[min(86%,30rem)] rounded-2xl px-3.5 py-2.5 text-sm shadow-sm ring-1',
                        'bg-gradient-to-br from-[#10B981] to-[#059669] text-white shadow-[#10B981]/30 ring-[#10B981]/20' => $msg['send_by'] === 'patient',
                        'border border-zinc-200/90 bg-white text-zinc-800 shadow-zinc-200/30 ring-zinc-100' => $msg['send_by'] !== 'patient',
                    ])>
                        <p class="whitespace-pre-wrap break-words">{{ $msg['body'] }}</p>
                        @if ($msg['created_at'])
                            <p @class(['mt-1 text-[0.65rem]', 'text-white/70' => $msg['send_by'] === 'patient', 'text-zinc-400' => $msg['send_by'] !== 'patient'])>
                                {{ \Illuminate\Support\Carbon::parse($msg['created_at'])->timezone(config('app.timezone'))->format('H:i') }}
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="flex min-h-[18rem] items-center justify-center text-center text-sm text-zinc-500">
                    {{ __('patient.appointments.empty_chat') }}
                </div>
            @endforelse
                </div>

                <div class="border-t border-zinc-200/90 bg-white px-4 py-3 sm:px-5">
                    <form wire:submit="sendMessage" class="flex items-end gap-2 rounded-2xl border border-zinc-200/90 bg-gradient-to-r from-zinc-50/80 to-zinc-100/70 p-1.5 shadow-inner shadow-zinc-200/30 ring-1 ring-zinc-100/80">
                <flux:input
                    wire:model="draft"
                    type="text"
                    :placeholder="__('patient.appointments.type_message')"
                    class="flex-1 !border-0 !bg-transparent !shadow-none"
                    :disabled="! $appointment->isChatOpen()"
                />
                <flux:button type="submit" variant="primary" icon="paper-airplane" class="shadow-md shadow-[#047857]/25" wire:loading.attr="disabled" :disabled="! $appointment->isChatOpen()">
                    {{ __('patient.appointments.send') }}
                </flux:button>
            </form>
                </div>
            </div>

            <aside class="hidden bg-gradient-to-b from-white via-zinc-50/30 to-zinc-50/70 lg:col-span-4 lg:block">
                <div class="border-b border-zinc-200/90 px-5 py-4">
                    <h3 class="text-base font-semibold text-zinc-900">{{ __('patient.appointments.patient_details_title') }}</h3>
                    <p class="mt-0.5 text-xs text-zinc-500">{{ __('patient.appointments.patient_details_sub') }}</p>
                </div>
                <div class="px-5 py-8 text-center">
                    <div class="mx-auto w-fit rounded-2xl bg-white p-1 shadow-lg shadow-zinc-200/40 ring-1 ring-zinc-100">
                        <flux:avatar :name="$appointment->patient_name" circle size="2xl" />
                    </div>
                    <p class="mt-5 text-lg font-semibold tracking-tight text-zinc-900">{{ $appointment->patient_name }}</p>
                    @if ($appointment->patient_phone)
                        <a href="tel:{{ preg_replace('/\s+/', '', (string) $appointment->patient_phone) }}" class="mt-3 inline-flex items-center justify-center gap-2 rounded-full bg-zinc-100 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-200/80">
                            <flux:icon name="phone" variant="mini" class="size-4" />
                            {{ $appointment->patient_phone }}
                        </a>
                    @endif

                    <div class="mt-6 grid gap-2 text-start">
                        <div class="rounded-xl border border-zinc-200/80 bg-white/90 px-3 py-2 shadow-sm">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">{{ __('patient.appointments.status') }}</p>
                            <p class="mt-0.5 text-sm font-semibold text-zinc-800">{{ __('patient.appointments.status_'.$appointment->status) }}</p>
                        </div>
                        <div class="rounded-xl border border-zinc-200/80 bg-white/90 px-3 py-2 shadow-sm">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">{{ __('patient.appointments.session_label') }}</p>
                            <p class="mt-0.5 text-sm font-semibold text-zinc-800">{{ $appointment->appointment_date?->format('d/m/Y') }} · {{ \Illuminate\Support\Carbon::parse((string) $appointment->start_time)->format('h:i A') }}</p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    @include('partials.video-call-overlay', [
        'overlayId' => 'patient-agora-overlay',
        'titleId' => 'patient-agora-title',
        'durationId' => 'patient-overlay-call-duration',
        'durationLabel' => __('patient.appointments.call_duration_label'),
        'leaveBtnId' => 'patient-agora-leave',
        'remoteId' => 'patient-agora-remote',
        'localId' => 'patient-agora-local',
        'toggleMicId' => 'patient-agora-toggle-mic',
        'toggleVideoId' => 'patient-agora-toggle-video',
        'title' => __('patient.appointments.call_in_progress'),
        'youLabel' => __('patient.appointments.you'),
        'endCallLabel' => __('patient.appointments.end_call'),
        'micLabel' => __('patient.appointments.mic'),
        'cameraLabel' => __('patient.appointments.camera'),
        'micMutedLabel' => __('patient.appointments.mic_muted'),
        'cameraOffLabel' => __('patient.appointments.camera_off'),
    ])

    <div
        id="patient-conversation-bootstrap"
        wire:ignore
        class="hidden"
        data-pusher-key="{{ config('broadcasting.connections.pusher.key') }}"
        data-pusher-cluster="{{ config('broadcasting.connections.pusher.options.cluster') }}"
        data-appointment-id="{{ $appointment->id }}"
        data-patient-id="{{ auth()->id() }}"
        data-agora-ready="{{ $agoraAppId !== '' ? '1' : '0' }}"
        data-appointment-status="{{ $appointment->status }}"
    ></div>
</div>

@push('scripts')
    @include('partials.realtime-call-alerts')
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.23.0.js" data-agora-sdk="1"></script>
    <script>
        function initPatientConversationRealtime() {
            const boot = document.getElementById('patient-conversation-bootstrap');
            const panel = document.getElementById('patient-chat-panel');
            if (!boot || !panel) {
                return;
            }

            const appointmentId = Number(boot.dataset.appointmentId || 0);

            if (boot.dataset.initialized === '1' && boot.dataset.boundAppointmentId === String(appointmentId)) {
                boot.__bindCallControlButtons?.();
                boot.__restorePendingCall?.();

                return;
            }

            if (boot.dataset.initialized === '1') {
                boot.__leaveCall?.().catch(() => {});
            }

            boot.dataset.initialized = '1';
            boot.dataset.boundAppointmentId = String(appointmentId);

            const patientId = Number(boot.dataset.patientId || 0);
            const csrf = panel.dataset.csrf || '';
            const tokenUrl = panel.dataset.tokenUrl || '';
            const pusherKey = boot.dataset.pusherKey || '';
            const pusherCluster = boot.dataset.pusherCluster || 'mt1';
            const callEnabled = boot.dataset.agoraReady === '1';
            let appointmentStatus = boot.dataset.appointmentStatus || 'new';
            const messagesWrap = document.getElementById('patient-chat-messages');
            const seen = new Set();
            const metrics = document.getElementById('patient-conversation-metrics');
            let sessionTimerId = null;
            let sessionEndedDisconnectHandled = false;

            document.querySelectorAll('#patient-chat-messages [wire\\:key^="patient-chat-"]').forEach((el) => {
                const key = el.getAttribute('wire:key') || '';
                const id = key.replace('patient-chat-', '');
                if (id) seen.add(id);
            });

            function appendMessageRow(payload) {
                if (!messagesWrap || !payload.id || seen.has(payload.id)) return;
                if (payload.send_by === 'patient' && Number(payload.from_id || 0) === patientId) return;
                seen.add(payload.id);

                const emptyState = messagesWrap.querySelector('.flex.min-h-\\[18rem\\]');
                if (emptyState) emptyState.remove();

                const row = document.createElement('div');
                const mine = payload.send_by === 'patient';
                row.className = mine ? 'flex justify-end' : 'flex justify-start';

                const bubble = document.createElement('div');
                bubble.className = mine
                    ? 'max-w-[min(86%,30rem)] rounded-2xl bg-[#10B981] px-3.5 py-2.5 text-sm text-white shadow-sm'
                    : 'max-w-[min(86%,30rem)] rounded-2xl border border-zinc-200 bg-white px-3.5 py-2.5 text-sm text-zinc-800 shadow-sm';

                const p = document.createElement('p');
                p.className = 'whitespace-pre-wrap break-words';
                p.textContent = payload.body || '';
                bubble.appendChild(p);
                row.appendChild(bubble);
                messagesWrap.appendChild(row);
                messagesWrap.scrollTop = messagesWrap.scrollHeight;
            }

            const incomingBanner = document.getElementById('incoming-call-banner');
            const incomingLabel = document.getElementById('incoming-call-label');
            const incomingAccept = document.getElementById('incoming-call-accept');
            const incomingDismiss = document.getElementById('incoming-call-dismiss');
            let incomingPayload = null;

            let agoraClient = null;
            let localAudio = null;
            let localVideo = null;
            let activeMode = null;

            const overlay = document.getElementById('patient-agora-overlay');
            const remoteWrap = document.getElementById('patient-agora-remote');
            const localWrap = document.getElementById('patient-agora-local');
            const overlayTitle = document.getElementById('patient-agora-title');
            const overlayCallDuration = document.getElementById('patient-overlay-call-duration');
            const chip = document.getElementById('patient-call-started-chip');
            const labelVideo = @js(__('patient.appointments.video_call'));
            const labelVoice = @js(__('patient.appointments.voice_call'));
            const labelConnecting = metrics?.dataset.labelConnecting || 'Connecting…';
            const labelCallFailed = metrics?.dataset.labelCallFailed || 'Could not join the call.';
            const labelNoActiveCall = metrics?.dataset.labelNoActiveCall || 'No active call yet.';
            const waitingChip = document.getElementById('patient-waiting-for-call-chip');

            function showCallToast(text, variant = 'danger') {
                if (window.Flux?.toast) {
                    window.Flux.toast({ text, variant });

                    return;
                }

                console.error(text);
            }

            function callErrorMessage(error, fallback) {
                if (!error) {
                    return fallback;
                }

                const name = error?.name || '';
                const message = error?.message || '';

                if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
                    return metrics?.dataset.labelCameraPermission || fallback;
                }

                if (message) {
                    return message;
                }

                return fallback;
            }

            function mountOverlayToBody() {
                if (overlay && overlay.parentElement !== document.body) {
                    document.body.appendChild(overlay);
                }
            }

            mountOverlayToBody();

            function ensureAgoraSdk(timeoutMs = 12000) {
                if (window.AgoraRTC) {
                    return Promise.resolve(window.AgoraRTC);
                }

                const existing = document.querySelector('script[data-agora-sdk="1"]');
                if (!existing) {
                    return Promise.reject(new Error(metrics?.dataset.labelAgoraSdkMissing || 'Agora SDK missing'));
                }

                return new Promise((resolve, reject) => {
                    const startedAt = Date.now();

                    const tick = () => {
                        if (window.AgoraRTC) {
                            resolve(window.AgoraRTC);

                            return;
                        }

                        if (Date.now() - startedAt >= timeoutMs) {
                            reject(new Error(metrics?.dataset.labelAgoraSdkMissing || 'Agora SDK missing'));

                            return;
                        }

                        window.setTimeout(tick, 50);
                    };

                    tick();
                });
            }

            async function resetPartialAgoraJoin() {
                if (localVideo) {
                    localVideo.stop();
                    localVideo.close();
                    localVideo = null;
                }

                if (localAudio) {
                    localAudio.stop();
                    localAudio.close();
                    localAudio = null;
                }

                if (agoraClient) {
                    try {
                        await agoraClient.leave();
                    } catch {
                        // ignore cleanup errors
                    }

                    agoraClient = null;
                }

                activeMode = null;

                if (remoteWrap) {
                    remoteWrap.innerHTML = '';
                }

                if (localWrap) {
                    localWrap.innerHTML = '';
                }
            }

            function setOverlayConnecting(mode) {
                if (overlayTitle) {
                    overlayTitle.textContent = labelConnecting;
                }

                if (overlayCallDuration) {
                    overlayCallDuration.textContent = '00:00';
                }

                document.getElementById('patient-agora-toggle-video')?.classList.toggle('hidden', mode !== 'video');
            }

            function refreshCallUiState() {
                syncPatientSessionFromDom(boot);

                const sessionActive = callEnabled && appointmentStatus === 'in_process';

                if (waitingChip) {
                    waitingChip.classList.toggle(
                        'hidden',
                        !sessionActive || activeMode || incomingPayload || incomingBanner && !incomingBanner.classList.contains('hidden'),
                    );
                }

                if (chip) {
                    chip.classList.toggle('hidden', !activeMode);
                }
            }

            let callTimerId = null;
            let callStartedAt = null;

            function formatDuration(totalSeconds) {
                const s = Math.max(0, Math.floor(totalSeconds));
                const h = Math.floor(s / 3600);
                const m = Math.floor((s % 3600) / 60);
                const r = s % 60;
                if (h > 0) {
                    return h + ':' + String(m).padStart(2, '0') + ':' + String(r).padStart(2, '0');
                }
                return String(m).padStart(2, '0') + ':' + String(r).padStart(2, '0');
            }

            function tickCallTimer() {
                if (!callStartedAt || !overlayCallDuration) {
                    return;
                }

                const sec = (Date.now() - callStartedAt) / 1000;
                overlayCallDuration.textContent = formatDuration(sec);
            }

            function startCallTimer(mode) {
                if (callTimerId) {
                    clearInterval(callTimerId);
                }

                callStartedAt = Date.now();
                tickCallTimer();
                callTimerId = setInterval(tickCallTimer, 1000);

                if (overlayTitle) {
                    overlayTitle.textContent = mode === 'video' ? labelVideo : labelVoice;
                }
            }

            function stopCallTimer() {
                if (callTimerId) {
                    clearInterval(callTimerId);
                }

                callTimerId = null;
                callStartedAt = null;

                if (overlayCallDuration) {
                    overlayCallDuration.textContent = '00:00';
                }
            }

            function syncPatientSessionFromDom(bootEl) {
                const metricsEl = document.getElementById('patient-conversation-metrics');
                const nextStatus = bootEl?.dataset.appointmentStatus || metricsEl?.dataset.status || appointmentStatus;
                const wasWaiting = appointmentStatus !== 'in_process' && nextStatus === 'in_process';

                appointmentStatus = nextStatus;
                if (bootEl) {
                    bootEl.dataset.appointmentStatus = appointmentStatus;
                }

                if (metricsEl && nextStatus === 'in_process') {
                    metricsEl.dataset.status = nextStatus;
                }

                if (wasWaiting && incomingLabel && incomingBanner) {
                    showCallToast(metricsEl?.dataset.sessionStartedWaiting || 'Session started.', 'success');
                    startSessionTimers();
                    refreshCallUiState();
                }
            }

            function tickSessionTimers() {
                const elapsedEl = document.getElementById('patient-timer-session-elapsed');
                const remainingEl = document.getElementById('patient-timer-session-remaining');
                if (!metrics || !elapsedEl || !remainingEl) return;

                const status = metrics.dataset.status || '';
                const startIso = metrics.dataset.sessionStart || '';
                const endIso = metrics.dataset.sessionEnd || '';
                const scheduledTime = metrics.dataset.sessionScheduledTime || '--:--';

                // Show fixed appointment time, not elapsed stopwatch.
                elapsedEl.textContent = scheduledTime;

                if (status !== 'in_process' || !startIso) {
                    remainingEl.textContent = '--:--';
                    return;
                }

                const now = Date.now();

                if (endIso) {
                    const end = new Date(endIso).getTime();
                    const left = (end - now) / 1000;
                    remainingEl.textContent = formatDuration(left);
                    remainingEl.classList.toggle('text-amber-700', left > 0 && left <= 300);
                    remainingEl.classList.toggle('text-rose-600', left <= 0);
                    remainingEl.classList.toggle('text-[#047857]', left > 300);
                    maybeEndCallWhenSessionExpired(left);
                }
            }

            function startSessionTimers() {
                if (sessionTimerId) clearInterval(sessionTimerId);
                tickSessionTimers();
                sessionTimerId = setInterval(tickSessionTimers, 1000);
            }

            function showOverlay(show) {
                if (!overlay) {
                    return;
                }

                overlay.classList.toggle('hidden', !show);
                overlay.setAttribute('aria-hidden', show ? 'false' : 'true');
            }

            function syncMediaControlUi() {
                const micBtn = document.getElementById('patient-agora-toggle-mic');
                const videoBtn = document.getElementById('patient-agora-toggle-video');

                if (micBtn) {
                    const muted = Boolean(localAudio) && !localAudio.enabled;
                    micBtn.classList.toggle('video-call-control--off', muted);
                    micBtn.setAttribute('aria-pressed', muted ? 'true' : 'false');
                    micBtn.title = muted ? (micBtn.dataset.labelOff || 'Muted') : (micBtn.dataset.labelOn || '');
                    const label = micBtn.querySelector('[data-control-label]');
                    if (label) {
                        label.textContent = muted
                            ? (micBtn.dataset.labelOff || 'Muted')
                            : (micBtn.dataset.labelOn || '');
                    }
                }

                if (videoBtn && !videoBtn.classList.contains('hidden')) {
                    const cameraOff = Boolean(localVideo) && !localVideo.enabled;
                    videoBtn.classList.toggle('video-call-control--off', cameraOff);
                    videoBtn.setAttribute('aria-pressed', cameraOff ? 'true' : 'false');
                    videoBtn.title = cameraOff ? (videoBtn.dataset.labelOff || 'Camera off') : (videoBtn.dataset.labelOn || '');
                    const label = videoBtn.querySelector('[data-control-label]');
                    if (label) {
                        label.textContent = cameraOff
                            ? (videoBtn.dataset.labelOff || 'Camera off')
                            : (videoBtn.dataset.labelOn || '');
                    }
                }
            }

            async function leaveCall() {
                stopCallTimer();
                if (localVideo) {
                    localVideo.stop();
                    localVideo.close();
                    localVideo = null;
                }
                if (localAudio) {
                    localAudio.stop();
                    localAudio.close();
                    localAudio = null;
                }
                if (agoraClient) {
                    await agoraClient.leave();
                    agoraClient = null;
                }
                activeMode = null;
                if (remoteWrap) remoteWrap.innerHTML = '';
                if (localWrap) localWrap.innerHTML = '';
                showOverlay(false);
                syncMediaControlUi();
                refreshCallUiState();
            }

            function maybeEndCallWhenSessionExpired(leftSeconds) {
                if (leftSeconds > 0) {
                    sessionEndedDisconnectHandled = false;

                    return;
                }

                if (!activeMode || sessionEndedDisconnectHandled) {
                    return;
                }

                sessionEndedDisconnectHandled = true;
                const endedMessage = metrics?.dataset.sessionEnded || 'Session time has ended.';

                leaveCall()
                    .then(() => {
                        if (window.Flux?.toast) {
                            window.Flux.toast({ text: endedMessage, variant: 'warning' });
                        }
                    })
                    .catch(() => {});
            }

            async function refreshConfig() {
                const res = await fetch(tokenUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                });
                if (!res.ok) {
                    return null;
                }

                return res.json();
            }

            function notifyIncomingCallAlert(message) {
                window.MashoraRealtimeAlerts?.playIncomingRing();
                window.MashoraRealtimeAlerts?.showDesktopNotification(
                    @js(__('patient.appointments.incoming_call_title')),
                    message,
                );

                if (window.Flux?.toast) {
                    window.Flux.toast({ text: message, variant: 'success' });
                }
            }

            function dismissIncomingAlert() {
                window.MashoraRealtimeAlerts?.stopIncomingRing();
            }

            function resolveAgoraConfig(payload) {
                if (payload && payload.agora_app_id && payload.agora_channel) {
                    return payload;
                }

                return null;
            }

            function showIncomingCallBanner(data) {
                if (
                    incomingPayload?.agora_channel === data?.agora_channel
                    && incomingBanner
                    && !incomingBanner.classList.contains('hidden')
                ) {
                    return;
                }

                incomingPayload = data;

                if (incomingLabel) {
                    incomingLabel.textContent = data.call_type === 'video'
                        ? @js(__('patient.appointments.incoming_video'))
                        : @js(__('patient.appointments.incoming_voice'));
                }

                try {
                    sessionStorage.setItem(
                        'mashora_pending_call_' + appointmentId,
                        JSON.stringify(data),
                    );
                } catch (_) {
                    // ignore storage errors
                }

                notifyIncomingCallAlert(incomingLabel?.textContent || @js(__('patient.appointments.incoming_call_title')));
                incomingBanner?.classList.remove('hidden');
                refreshCallUiState();
            }

            function clearPendingCallStorage() {
                try {
                    sessionStorage.removeItem('mashora_pending_call_' + appointmentId);
                } catch (_) {
                    // ignore storage errors
                }
            }

            function restorePendingCallFromStorage() {
                try {
                    const raw = sessionStorage.getItem('mashora_pending_call_' + appointmentId);
                    if (!raw) {
                        return;
                    }

                    const data = JSON.parse(raw);
                    if (data?.agora_app_id) {
                        showIncomingCallBanner(data);
                    }
                } catch (_) {
                    // ignore parse errors
                }
            }

            async function joinCall(mode, payload = null) {
                if (!callEnabled || activeMode) {
                    return;
                }

                if (appointmentStatus !== 'in_process') {
                    showCallToast(labelNoActiveCall, 'warning');

                    return;
                }

                dismissIncomingAlert();
                incomingBanner?.classList.add('hidden');
                showOverlay(true);
                setOverlayConnecting(mode);

                try {
                    await ensureAgoraSdk();

                    let cfg = resolveAgoraConfig(payload);
                    if (!cfg) {
                        cfg = await refreshConfig();
                    }

                    if (!cfg || !cfg.agora_app_id || !cfg.agora_channel) {
                        throw new Error(labelNoActiveCall);
                    }

                    clearPendingCallStorage();

                    agoraClient = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });
                    agoraClient.on('user-published', async (user, mediaType) => {
                        await agoraClient.subscribe(user, mediaType);
                        if (mediaType === 'video') {
                            user.videoTrack.play('patient-agora-remote');
                        }
                        if (mediaType === 'audio') {
                            user.audioTrack.play();
                        }
                    });

                    if (mode === 'video') {
                        const [, micTrack, camTrack] = await Promise.all([
                            agoraClient.join(cfg.agora_app_id, cfg.agora_channel, cfg.agora_token || null, null),
                            AgoraRTC.createMicrophoneAudioTrack(),
                            AgoraRTC.createCameraVideoTrack(),
                        ]);
                        localAudio = micTrack;
                        localVideo = camTrack;
                        camTrack.play('patient-agora-local');
                        await agoraClient.publish([micTrack, camTrack]);
                    } else {
                        const [, micTrack] = await Promise.all([
                            agoraClient.join(cfg.agora_app_id, cfg.agora_channel, cfg.agora_token || null, null),
                            AgoraRTC.createMicrophoneAudioTrack(),
                        ]);
                        localAudio = micTrack;
                        await agoraClient.publish([micTrack]);
                    }

                    activeMode = mode;
                    incomingPayload = null;
                    startCallTimer(mode);
                    document.getElementById('patient-agora-toggle-video')?.classList.toggle('hidden', mode !== 'video');
                    syncMediaControlUi();
                    refreshCallUiState();
                } catch (e) {
                    console.error(e);
                    await resetPartialAgoraJoin();
                    showOverlay(false);
                    showCallToast(callErrorMessage(e, labelCallFailed));
                    refreshCallUiState();
                }
            }

            boot.__joinCall = (mode, payload = null) => joinCall(mode, payload);
            boot.__leaveCall = () => leaveCall();
            boot.__toggleMic = () => {
                if (!localAudio) {
                    return;
                }

                localAudio.setEnabled(!localAudio.enabled);
                syncMediaControlUi();
            };
            boot.__toggleVideo = () => {
                if (!localVideo) {
                    return;
                }

                localVideo.setEnabled(!localVideo.enabled);
                syncMediaControlUi();
            };
            boot.__acceptIncoming = () => {
                if (!incomingPayload?.agora_app_id) {
                    restorePendingCallFromStorage();
                }

                if (!incomingPayload?.agora_app_id) {
                    showCallToast(labelNoActiveCall, 'warning');
                    dismissIncomingAlert();
                    incomingBanner?.classList.add('hidden');
                    refreshCallUiState();

                    return;
                }

                const mode = incomingPayload.call_type === 'video' ? 'video' : 'audio';
                joinCall(mode, incomingPayload).catch((error) => console.error(error));
            };
            boot.__dismissIncoming = () => {
                dismissIncomingAlert();
                incomingPayload = null;
                clearPendingCallStorage();
                incomingBanner?.classList.add('hidden');
                refreshCallUiState();
            };

            function bindCallControlButtons() {
                if (incomingAccept) {
                    incomingAccept.onclick = (event) => {
                        event.preventDefault();
                        boot.__acceptIncoming?.();
                    };
                }

                if (incomingDismiss) {
                    incomingDismiss.onclick = (event) => {
                        event.preventDefault();
                        boot.__dismissIncoming?.();
                    };
                }

                const leaveBtn = document.getElementById('patient-agora-leave');
                if (leaveBtn) {
                    leaveBtn.onclick = (event) => {
                        event.preventDefault();
                        leaveCall().catch((error) => console.error(error));
                    };
                }

                const micBtn = document.getElementById('patient-agora-toggle-mic');
                if (micBtn) {
                    micBtn.onclick = (event) => {
                        event.preventDefault();
                        boot.__toggleMic?.();
                    };
                }

                const overlayVideoBtn = document.getElementById('patient-agora-toggle-video');
                if (overlayVideoBtn) {
                    overlayVideoBtn.onclick = (event) => {
                        event.preventDefault();
                        boot.__toggleVideo?.();
                    };
                }
            }

            bindCallControlButtons();
            boot.__bindCallControlButtons = bindCallControlButtons;
            boot.__restorePendingCall = restorePendingCallFromStorage;
            restorePendingCallFromStorage();

            if (!window.__patientConversationClickBound) {
                window.__patientConversationClickBound = true;

                document.addEventListener('click', (event) => {
                    const bootEl = document.getElementById('patient-conversation-bootstrap');
                    if (!bootEl) {
                        return;
                    }

                    if (event.target.closest('#incoming-call-accept')) {
                        event.preventDefault();
                        bootEl.__acceptIncoming?.();
                    }

                    if (event.target.closest('#incoming-call-dismiss')) {
                        event.preventDefault();
                        bootEl.__dismissIncoming?.();
                    }

                    if (event.target.closest('#patient-agora-leave')) {
                        event.preventDefault();
                        bootEl.__leaveCall?.().catch((error) => console.error(error));
                    }

                    if (event.target.closest('#patient-agora-toggle-mic')) {
                        event.preventDefault();
                        bootEl.__toggleMic?.();
                    }

                    if (event.target.closest('#patient-agora-toggle-video')) {
                        event.preventDefault();
                        bootEl.__toggleVideo?.();
                    }
                });
            }

            if (!window.__patientConversationIncomingCallHook) {
                window.__patientConversationIncomingCallHook = true;

                window.addEventListener('mashora:incoming-call', (event) => {
                    const data = event.detail;
                    if (Number(data?.appointment_id || 0) === appointmentId) {
                        showIncomingCallBanner(data);
                    }
                });
            }

            if (!window.__patientConversationNavigateHook) {
                window.__patientConversationNavigateHook = true;

                document.addEventListener('livewire:navigating', () => {
                    const bootEl = document.getElementById('patient-conversation-bootstrap');
                    bootEl?.__leaveCall?.().catch(() => {});

                    if (bootEl) {
                        delete bootEl.dataset.initialized;
                        delete bootEl.dataset.boundAppointmentId;
                    }
                });
            }

            if (pusherKey) {
                const pusher = new Pusher(pusherKey, {
                    cluster: pusherCluster,
                    authEndpoint: '/broadcasting/auth',
                    auth: {
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    },
                });

                pusher.connection.bind('error', (error) => {
                    console.error('Pusher connection error', error);
                });

                const channel = pusher.subscribe('private-appointment.' + appointmentId);
                channel.bind('pusher:subscription_error', (error) => {
                    console.error('Pusher appointment channel error', error);
                });
                channel.bind('message.created', (data) => appendMessageRow(data));
                channel.bind('session.started', (data) => {
                    appointmentStatus = data.status || 'in_process';
                    boot.dataset.appointmentStatus = appointmentStatus;
                    if (metrics) {
                        metrics.dataset.status = data.status || 'in_process';
                        metrics.dataset.sessionStart = data.actual_start_at || '';
                        metrics.dataset.sessionEnd = data.extend_at || '';
                    }
                    showCallToast(metrics?.dataset.sessionStartedWaiting || @js(__('patient.appointments.session_started_waiting')), 'success');
                    startSessionTimers();
                    refreshCallUiState();
                });
                channel.bind('call.incoming', (data) => {
                    showIncomingCallBanner(data);
                });

                if (patientId > 0) {
                    const patientChannel = pusher.subscribe('private-patient.' + patientId);
                    patientChannel.bind('pusher:subscription_error', (error) => {
                        console.error('Pusher patient channel error', error);
                    });
                    patientChannel.bind('session.join-requested', (data) => {
                        if (Number(data.appointment_id || 0) !== appointmentId) {
                            return;
                        }

                        showIncomingCallBanner(data);
                    });
                }
            } else {
                console.warn('Pusher is not configured: set PUSHER_APP_KEY and BROADCAST_CONNECTION=pusher');
            }

            boot.__syncCallOverlay = () => {
                if (activeMode) {
                    showOverlay(true);
                }
            };

            function registerPatientConversationMorphHook() {
                if (window.__patientConversationMorphHook) {
                    return;
                }

                window.__patientConversationMorphHook = true;

                const registerHook = () => {
                    Livewire.hook('commit', ({ succeed }) => {
                        succeed(() => {
                            const bootEl = document.getElementById('patient-conversation-bootstrap');
                            bootEl?.__bindCallControlButtons?.();
                            bootEl?.__restorePendingCall?.();
                            bootEl?.__syncCallOverlay?.();
                        });
                    });
                };

                if (window.Livewire) {
                    registerHook();
                } else {
                    document.addEventListener('livewire:init', registerHook);
                }
            }

            registerPatientConversationMorphHook();

            startSessionTimers();
            refreshCallUiState();

            if (boot.dataset.sessionObserved !== '1') {
                boot.dataset.sessionObserved = '1';
                const sessionObserver = new MutationObserver(() => {
                    syncPatientSessionFromDom(boot);
                    refreshCallUiState();
                    startSessionTimers();
                });
                sessionObserver.observe(boot, { attributes: true, attributeFilter: ['data-appointment-status'] });

                if (metrics) {
                    sessionObserver.observe(metrics, { attributes: true, attributeFilter: ['data-status', 'data-session-start', 'data-session-end'] });
                }
            }
        }

        document.addEventListener('DOMContentLoaded', initPatientConversationRealtime);
        document.addEventListener('livewire:navigated', initPatientConversationRealtime);
        initPatientConversationRealtime();
    </script>
@endpush
