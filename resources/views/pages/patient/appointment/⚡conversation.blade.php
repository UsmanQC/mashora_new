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
        if (! in_array($this->appointment->status, ['new', 'in_process'], true)) {
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

<div class="mx-auto w-full max-w-6xl space-y-5 px-4 py-5 pb-24 sm:px-6 sm:pb-10">
    <header class="relative overflow-hidden rounded-2xl border border-zinc-200/80 bg-gradient-to-br from-white via-white to-[#f7f9ff] p-4 shadow-sm shadow-zinc-200/60 ring-1 ring-zinc-100 sm:p-5">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#2f49ca] via-[#3C5CF7] to-[#6f86ff] opacity-85"></div>
        <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('patient.appointments') }}"
                wire:navigate
                class="inline-flex size-10 items-center justify-center rounded-full border border-zinc-200 bg-white text-[#1565c0] shadow-sm transition hover:bg-zinc-50"
            >
                <flux:icon name="chevron-left" variant="outline" class="size-6 rtl:rotate-180" />
            </a>
            <div>
                <h1 class="text-lg font-semibold tracking-tight text-zinc-900">{{ $appointment->doctor?->displayName() ?: __('patient.appointments.title') }}</h1>
                <p class="mt-0.5 text-xs text-zinc-500">{{ __('patient.appointments.status_'.$appointment->status) }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span
                id="patient-call-started-chip"
                class="hidden rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700"
            >
                {{ __('patient.appointments.call_in_progress') }}
            </span>
            <button
                type="button"
                id="btn-patient-video"
                class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-zinc-800 shadow-sm transition hover:border-[#1565c0]/35 hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-45"
                @disabled($agoraAppId === '' || $appointment->status !== 'in_process')
            >
                <flux:icon name="video-camera" variant="mini" class="size-4 text-zinc-600" />
                {{ __('patient.appointments.video_call') }}
            </button>
            <button
                type="button"
                id="btn-patient-audio"
                class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-zinc-800 shadow-sm transition hover:border-[#1565c0]/35 hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-45"
                @disabled($agoraAppId === '' || $appointment->status !== 'in_process')
            >
                <flux:icon name="phone" variant="mini" class="size-4 text-zinc-600" />
                {{ __('patient.appointments.voice_call') }}
            </button>
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
        data-session-started-banner="{{ __('patient.appointments.session_started_join_now') }}"
    ></div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:max-w-xl">
        <div class="rounded-xl border border-zinc-200/80 bg-gradient-to-br from-white to-zinc-50 px-3 py-2 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">{{ __('patient.appointments.session_elapsed_label') }}</p>
            <p id="patient-timer-session-elapsed" class="mt-0.5 font-mono text-lg font-semibold tabular-nums text-zinc-900">{{ $this->formattedAppointmentTime() }}</p>
        </div>
        <div id="patient-wrap-session-remaining" class="rounded-xl border border-zinc-200/80 bg-gradient-to-br from-white to-zinc-50 px-3 py-2 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">{{ __('patient.appointments.session_remaining_label') }}</p>
            <p id="patient-timer-session-remaining" class="mt-0.5 font-mono text-lg font-semibold tabular-nums text-[#132A6E]">--:--</p>
        </div>
    </div>

    <div id="incoming-call-banner" class="hidden rounded-xl border border-emerald-200 bg-emerald-50 p-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p id="incoming-call-label" class="text-sm font-medium text-emerald-900"></p>
            <div class="flex items-center gap-2">
                <button type="button" id="incoming-call-accept" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white">{{ __('patient.appointments.accept_call') }}</button>
                <button type="button" id="incoming-call-dismiss" class="rounded-lg border border-emerald-300 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-800">{{ __('patient.appointments.dismiss_call') }}</button>
            </div>
        </div>
    </div>

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
                <div id="patient-chat-messages" class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-gradient-to-b from-zinc-50/90 via-zinc-50/70 to-zinc-100/70 px-4 py-4 sm:px-5">
            @forelse ($messages as $msg)
                <div @class(['flex', 'justify-end' => $msg['send_by'] === 'patient', 'justify-start' => $msg['send_by'] !== 'patient']) wire:key="patient-chat-{{ $msg['id'] }}">
                    <div @class([
                        'max-w-[min(86%,30rem)] rounded-2xl px-3.5 py-2.5 text-sm shadow-sm ring-1',
                        'bg-gradient-to-br from-[#1565c0] to-[#0f4fa4] text-white shadow-[#1565c0]/30 ring-[#1565c0]/20' => $msg['send_by'] === 'patient',
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
                    :disabled="! in_array($appointment->status, ['new', 'in_process'], true)"
                />
                <flux:button type="submit" variant="primary" icon="paper-airplane" class="shadow-md shadow-[#132A6E]/25" wire:loading.attr="disabled" :disabled="! in_array($appointment->status, ['new', 'in_process'], true)">
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

    <div id="patient-agora-overlay" class="fixed bottom-4 inset-e-4 z-200 hidden w-[min(94vw,28rem)] overflow-hidden rounded-2xl border border-zinc-700/80 bg-zinc-950 text-white shadow-2xl shadow-black/45 ring-1 ring-white/10">
        <div class="flex items-center justify-between gap-3 border-b border-white/10 bg-zinc-900/90 px-3 py-2.5">
            <p id="patient-agora-title" class="text-sm font-semibold">{{ __('patient.appointments.call_in_progress') }}</p>
            <button type="button" id="patient-agora-leave" class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-500">{{ __('patient.appointments.end_call') }}</button>
        </div>
        <div class="relative p-2.5">
            <div class="relative h-56 overflow-hidden rounded-xl bg-black ring-1 ring-white/10 sm:h-64">
                <div id="patient-agora-remote" class="h-full w-full"></div>
                <div class="absolute bottom-2 inset-e-2 w-28 overflow-hidden rounded-lg bg-zinc-900/80 ring-1 ring-white/20 sm:w-32">
                    <div id="patient-agora-local" class="aspect-video w-full bg-zinc-800"></div>
                    <p class="px-2 py-1 text-center text-[10px] font-medium text-zinc-300">You</p>
                </div>
            </div>
        </div>
        <div class="flex justify-center gap-2 border-t border-white/10 bg-zinc-900/60 px-3 py-2.5">
            <button type="button" id="patient-agora-toggle-mic" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-xs font-medium hover:bg-white/15">
                <flux:icon name="microphone" variant="mini" class="size-4" />
                {{ __('patient.appointments.mic') }}
            </button>
            <button type="button" id="patient-agora-toggle-video" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-xs font-medium hover:bg-white/15">
                <flux:icon name="video-camera" variant="mini" class="size-4" />
                {{ __('patient.appointments.camera') }}
            </button>
        </div>
    </div>

    <div
        id="patient-conversation-bootstrap"
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
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.23.0.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const boot = document.getElementById('patient-conversation-bootstrap');
            const panel = document.getElementById('patient-chat-panel');
            if (!boot || !panel) return;

            const appointmentId = Number(boot.dataset.appointmentId || 0);
            const patientId = Number(boot.dataset.patientId || 0);
            const csrf = panel.dataset.csrf || '';
            const notifyUrl = panel.dataset.notifyUrl || '';
            const tokenUrl = panel.dataset.tokenUrl || '';
            const pusherKey = boot.dataset.pusherKey || '';
            const pusherCluster = boot.dataset.pusherCluster || 'mt1';
            const callEnabled = boot.dataset.agoraReady === '1';
            let appointmentStatus = boot.dataset.appointmentStatus || 'new';
            const messagesWrap = document.getElementById('patient-chat-messages');
            const seen = new Set();
            const metrics = document.getElementById('patient-conversation-metrics');
            let sessionTimerId = null;

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
                    ? 'max-w-[min(86%,30rem)] rounded-2xl bg-[#1565c0] px-3.5 py-2.5 text-sm text-white shadow-sm'
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
            const leaveBtn = document.getElementById('patient-agora-leave');
            const videoBtn = document.getElementById('btn-patient-video');
            const audioBtn = document.getElementById('btn-patient-audio');
            const chip = document.getElementById('patient-call-started-chip');
            const toggleMicBtn = document.getElementById('patient-agora-toggle-mic');
            const toggleVideoBtn = document.getElementById('patient-agora-toggle-video');

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

            function refreshCallButtonsState() {
                const enabled = callEnabled && appointmentStatus === 'in_process';
                if (videoBtn && !activeMode) videoBtn.disabled = !enabled;
                if (audioBtn && !activeMode) audioBtn.disabled = !enabled;
                if (chip) {
                    chip.classList.toggle('hidden', !activeMode);
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
                    remainingEl.classList.toggle('text-[#132A6E]', left > 300);
                }
            }

            function startSessionTimers() {
                if (sessionTimerId) clearInterval(sessionTimerId);
                tickSessionTimers();
                sessionTimerId = setInterval(tickSessionTimers, 1000);
            }

            function showOverlay(show) {
                if (!overlay) return;
                overlay.classList.toggle('hidden', !show);
            }

            async function leaveCall() {
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
                refreshCallButtonsState();
            }

            async function refreshConfig() {
                const res = await fetch(tokenUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                });
                if (!res.ok) return null;
                return res.json();
            }

            async function notifyDoctor(type, cfg) {
                const res = await fetch(notifyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        agora_app_id: cfg.agora_app_id,
                        agora_token: cfg.agora_token,
                        agora_channel: cfg.agora_channel,
                        call_type: type,
                    }),
                });

                if (!res.ok) return null;
                return res.json();
            }

            async function joinCall(mode, payload = null, shouldNotify = false) {
                if (!callEnabled || activeMode || !window.AgoraRTC) return;

                const cfg = payload ?? await refreshConfig();
                if (!cfg) return;

                if (shouldNotify) {
                    const notify = await notifyDoctor(mode, cfg);
                    if (notify && metrics) {
                        appointmentStatus = notify.status || 'in_process';
                        metrics.dataset.status = notify.status || 'in_process';
                        metrics.dataset.sessionStart = notify.actual_start_at || '';
                        metrics.dataset.sessionEnd = notify.extend_at || '';
                        startSessionTimers();
                        refreshCallButtonsState();
                    }
                }

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
                if (videoBtn) videoBtn.disabled = true;
                if (audioBtn) audioBtn.disabled = true;
                showOverlay(true);
            }

            videoBtn?.addEventListener('click', () => joinCall('video', null, true));
            audioBtn?.addEventListener('click', () => joinCall('audio', null, true));
            leaveBtn?.addEventListener('click', () => leaveCall().catch(() => {}));
            toggleMicBtn?.addEventListener('click', () => {
                if (localAudio) {
                    localAudio.setEnabled(!localAudio.enabled);
                }
            });
            toggleVideoBtn?.addEventListener('click', () => {
                if (localVideo) {
                    localVideo.setEnabled(!localVideo.enabled);
                }
            });

            incomingAccept?.addEventListener('click', () => {
                if (!incomingPayload) return;
                incomingBanner?.classList.add('hidden');
                joinCall(incomingPayload.call_type === 'video' ? 'video' : 'audio', incomingPayload, false);
                incomingPayload = null;
            });

            incomingDismiss?.addEventListener('click', () => {
                incomingPayload = null;
                incomingBanner?.classList.add('hidden');
            });

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

                const channel = pusher.subscribe('private-appointment.' + appointmentId);
                channel.bind('message.created', (data) => appendMessageRow(data));
                channel.bind('session.started', (data) => {
                    appointmentStatus = data.status || 'in_process';
                    boot.dataset.appointmentStatus = appointmentStatus;
                    if (metrics) {
                        metrics.dataset.status = data.status || 'in_process';
                        metrics.dataset.sessionStart = data.actual_start_at || '';
                        metrics.dataset.sessionEnd = data.extend_at || '';
                    }
                    if (incomingLabel) {
                        incomingLabel.textContent = metrics?.dataset.sessionStartedBanner || 'Session started. Join now.';
                    }
                    incomingBanner?.classList.remove('hidden');
                    startSessionTimers();
                    refreshCallButtonsState();
                });
                channel.bind('call.incoming', (data) => {
                    incomingPayload = data;
                    if (incomingLabel) {
                        incomingLabel.textContent = data.call_type === 'video'
                            ? @js(__('patient.appointments.incoming_video'))
                            : @js(__('patient.appointments.incoming_voice'));
                    }
                    incomingBanner?.classList.remove('hidden');
                });
            }

            startSessionTimers();
            refreshCallButtonsState();
        });
    </script>
@endpush
