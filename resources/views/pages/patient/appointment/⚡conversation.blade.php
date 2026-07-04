<?php

use App\Events\AppointmentChatMessageSent;
use App\Models\Appointment;
use App\Models\ChMessage;
use App\Models\User;
use App\Support\DoctorAgoraChannel;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
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
        if (in_array((string) $this->appointment->status, ['completed', 'cancelled', 'not_attended'], true)) {
            return;
        }

        $this->appointment->refresh();

        if ((string) $this->appointment->status === 'in_process') {
            $this->refreshAgoraCredentials();
        }
    }

    #[On('patient-session-started')]
    public function onPatientSessionStarted(int $appointmentId): void
    {
        if ((int) $this->appointment->id !== $appointmentId) {
            return;
        }

        $this->appointment->refresh();
        $this->refreshAgoraCredentials();
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

    public function profilePhotoUrl(): ?string
    {
        $user = auth()->user();

        if (! $user instanceof User || ! filled($user->profile_photo_path)) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url((string) $user->profile_photo_path);
    }

    public function conversationHeaderSubtitle(): string
    {
        return __('patient.appointments.status_'.$this->appointment->status);
    }
}; ?>

<div
    id="patient-conversation-root"
    class="patient-luxury-conversation bg-slate-50 pb-[calc(4.75rem+env(safe-area-inset-bottom))] sm:bg-transparent sm:space-y-5 sm:pb-0"
    data-test="patient-luxury-conversation"
    @if (! in_array($appointment->status, ['in_process', 'completed', 'cancelled', 'not_attended'], true)) wire:poll.3s="refreshAppointmentSession" @endif
>
    <div class="sm:hidden">
        @include('partials.patient-luxury-page-header', [
            'title' => $appointment->doctor?->displayName() ?: __('patient.appointments.title'),
            'subtitle' => $this->conversationHeaderSubtitle(),
            'profilePhotoUrl' => $this->profilePhotoUrl(),
            'userName' => auth()->user()?->name,
            'testId' => 'patient-luxury-conversation-header',
            'backUrl' => route('patient.appointments'),
            'backLabel' => __('patient.appointments.title'),
        ])
    </div>

    <header class="relative hidden overflow-hidden rounded-2xl border border-zinc-200/80 bg-gradient-to-br from-white via-white to-[#f7f9ff] p-4 shadow-sm shadow-zinc-200/60 ring-1 ring-zinc-100 sm:block sm:p-5">
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
                <p id="patient-conversation-status-label" class="mt-0.5 text-xs text-zinc-500">{{ __('patient.appointments.status_'.$appointment->status) }}</p>
            </div>
        </div>
        </div>
    </header>

    @if ($appointment->allowsPatientCalls())
        <div class="flex flex-wrap items-center gap-2 px-6 pt-3 sm:px-0 sm:pt-0">
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
                    'rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-[11px] font-semibold text-zinc-600',
                    'hidden' => $appointment->status !== 'in_process',
                ])
            >
                {{ __('patient.appointments.waiting_for_specialist_call') }}
            </span>
        </div>
    @endif

    <div
        id="patient-conversation-metrics"
        wire:key="patient-conversation-metrics-{{ $appointment->id }}-{{ $appointment->status }}-{{ $appointment->actual_start_at?->timestamp ?? 0 }}"
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
        data-label-mic-permission="{{ __('patient.appointments.mic_permission_required') }}"
        data-label-system-media-permission="{{ __('patient.appointments.media_permission_denied_system') }}"
        data-label-camera-unavailable="{{ __('patient.appointments.camera_unavailable_joined_audio') }}"
        data-label-agora-sdk-missing="{{ __('patient.appointments.agora_sdk_missing') }}"
        data-label-no-active-call="{{ __('patient.appointments.no_active_call') }}"
        data-session-ended="{{ __('patient.appointments.session_time_ended') }}"
        data-relaxed-session-limits="{{ config('appointments.relaxed_session_limits') ? '1' : '0' }}"
    ></div>

    <div class="space-y-4 px-6 pt-4 sm:space-y-5 sm:px-0 sm:pt-0">
    @if (in_array($appointment->status, ['new', 'rescheduled'], true) && ! $appointment->isChatOpen())
        <flux:callout id="patient-chat-locked-callout" variant="secondary" icon="clock" class="border-zinc-200">
            {{ __('patient.appointments.chat_locked_until_doctor_starts') }}
        </flux:callout>
    @elseif ($appointment->status === 'completed' && ! $appointment->isChatOpen())
        <flux:callout variant="secondary" icon="check-circle" class="border-zinc-200 bg-white scheme-light !text-zinc-900">
            <span class="text-sm text-zinc-900">{{ __('patient.appointments.session_closed') }}</span>
        </flux:callout>
    @elseif ($appointment->status === 'completed' && $appointment->isChatOpen())
        <flux:callout variant="secondary" icon="chat-bubble-left-right" class="border-zinc-200 bg-white scheme-light !text-zinc-900">
            <span class="text-sm text-zinc-900">{{ __('patient.appointments.chat_open_after_completed', [
                'date' => $appointment->chatOpenUntil()->locale(app()->getLocale())->translatedFormat('d M Y'),
            ]) }}</span>
        </flux:callout>
    @endif

    <div
        id="patient-session-live-banner"
        @class([
            'rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-white px-4 py-3 shadow-sm shadow-emerald-900/5 ring-1 ring-emerald-100 max-sm:fixed max-sm:inset-x-6 max-sm:bottom-[calc(4.75rem+env(safe-area-inset-bottom))] max-sm:z-30 max-sm:shadow-lg',
            'hidden' => $appointment->status !== 'in_process',
        ])
    >
        <div class="flex flex-col gap-3 max-sm:gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0 max-sm:hidden">
                <p class="text-sm font-semibold text-emerald-950">{{ __('patient.appointments.session_live_banner_title') }}</p>
                <p class="mt-0.5 text-sm text-emerald-800">{{ __('patient.appointments.session_live_banner_body') }}</p>
            </div>
            <div class="flex min-w-0 flex-1 shrink-0 flex-col gap-2 max-sm:flex-row sm:flex-row sm:items-center">
                @if ($appointment->allowsPatientCalls())
                    <button
                        type="button"
                        id="patient-session-join-call-btn"
                        class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-xl bg-[#10B981] px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/20 transition hover:brightness-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"
                    >
                        <flux:icon name="video-camera" variant="mini" class="size-4" />
                        {{ __('patient.appointments.join_call') }}
                    </button>
                @endif
                <a
                    href="#patient-chat-panel"
                    class="inline-flex min-h-10 flex-1 items-center justify-center rounded-xl border border-emerald-300 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50 max-sm:hidden"
                >
                    {{ __('patient.appointments.open_session_chat') }}
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-2 sm:gap-3 lg:max-w-xl">
        <div class="rounded-2xl border border-slate-100 bg-white px-3 py-2.5 shadow-sm sm:rounded-xl sm:border-zinc-200/80 sm:bg-gradient-to-br sm:from-white sm:to-zinc-50">
            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 sm:text-[11px]">{{ __('patient.appointments.session_elapsed_label') }}</p>
            <p id="patient-timer-session-elapsed" class="mt-0.5 font-mono text-base font-bold tabular-nums text-slate-900 sm:text-lg sm:font-semibold">{{ $this->formattedAppointmentTime() }}</p>
        </div>
        <div id="patient-wrap-session-remaining" class="rounded-2xl border border-slate-100 bg-white px-3 py-2.5 shadow-sm sm:rounded-xl sm:border-zinc-200/80 sm:bg-gradient-to-br sm:from-white sm:to-zinc-50">
            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 sm:text-[11px]">{{ __('patient.appointments.session_remaining_label') }}</p>
            <p id="patient-timer-session-remaining" class="mt-0.5 font-mono text-base font-bold tabular-nums text-[#047857] sm:text-lg sm:font-semibold">--:--</p>
        </div>
    </div>

    @if (filled(config('broadcasting.connections.pusher.key')) && config('broadcasting.default') !== 'pusher')
        <flux:callout variant="warning" icon="exclamation-triangle" class="border-amber-200">
            {{ __('patient.appointments.realtime_misconfigured') }}
        </flux:callout>
    @endif

    <div
        id="incoming-call-banner"
        class="hidden rounded-2xl border border-emerald-300 bg-gradient-to-r from-emerald-50 via-emerald-50/95 to-white px-4 py-3 shadow-md shadow-emerald-900/10 ring-1 ring-inset ring-emerald-200/80 max-sm:fixed max-sm:inset-x-6 max-sm:bottom-[calc(4.75rem+env(safe-area-inset-bottom))] max-sm:z-40 max-sm:shadow-lg sm:px-5"
        role="alert"
        data-test="patient-incoming-call-banner"
    >
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-start gap-2.5">
                <span class="relative mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white shadow-sm shadow-emerald-900/25">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                    <flux:icon name="video-camera" variant="mini" class="relative size-4" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-emerald-950">{{ __('patient.appointments.incoming_call_title') }}</p>
                    <p id="incoming-call-label" class="mt-0.5 text-sm text-emerald-800"></p>
                </div>
            </div>
            <div class="flex shrink-0 flex-col gap-2 max-sm:w-full sm:flex-row sm:items-center">
                <button
                    type="button"
                    id="incoming-call-accept"
                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-[#10B981] px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/20 transition hover:brightness-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 sm:min-h-10 sm:w-auto"
                >
                    <flux:icon name="video-camera" variant="mini" class="size-4" />
                    {{ __('patient.appointments.join_call') }}
                </button>
                <button
                    type="button"
                    id="incoming-call-dismiss"
                    class="inline-flex min-h-10 w-full items-center justify-center rounded-xl border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-2 sm:w-auto"
                >
                    {{ __('patient.appointments.dismiss_call') }}
                </button>
            </div>
        </div>
    </div>

    <div
        id="patient-chat-panel"
        class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-[0_8px_32px_0_rgba(0,0,0,0.03)] ring-1 ring-slate-100 max-sm:mb-[calc(5.5rem+env(safe-area-inset-bottom))] sm:border-zinc-200/90 sm:shadow-[0_20px_55px_-32px_rgba(15,23,42,0.35)] sm:ring-zinc-100"
        data-appointment-id="{{ $appointment->id }}"
        data-notify-url="{{ route('patient.appointments.realtime.notify-call', $appointment) }}"
        data-pending-call-url="{{ route('patient.appointments.realtime.pending-call', $appointment) }}"
        data-end-call-url="{{ route('patient.appointments.realtime.end-call', $appointment) }}"
        data-token-url="{{ route('patient.appointments.realtime.agora-token', $appointment) }}"
        data-csrf="{{ csrf_token() }}"
    >
        <div class="grid min-h-[34rem] grid-cols-1 max-sm:min-h-[min(32rem,calc(100dvh-18rem))] lg:grid-cols-12">
            <div class="flex min-h-[30rem] flex-col border-zinc-200 max-sm:min-h-[min(28rem,calc(100dvh-20rem))] lg:col-span-8 lg:border-e">
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

    @include('partials.agora-media-controls')

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
    <script>
        function schedulePatientConversationInit() {
            if (window.__patientConversationInitScheduled) {
                return;
            }

            window.__patientConversationInitScheduled = true;

            window.setTimeout(() => {
                window.__patientConversationInitScheduled = false;
                initPatientConversationRealtime();
            }, 0);
        }

        function teardownPatientConversationRealtime() {
            if (typeof window.__patientConversationTeardown === 'function') {
                window.__patientConversationTeardown();
                window.__patientConversationTeardown = null;
            }
        }

        function initPatientConversationRealtime() {
            const boot = document.getElementById('patient-conversation-bootstrap');
            const panel = document.getElementById('patient-chat-panel');
            if (!boot || !panel) {
                return;
            }

            const appointmentId = Number(boot.dataset.appointmentId || 0);

            if (window.__patientConversationInitLock) {
                return;
            }

            if (boot.dataset.initialized === '1' && boot.dataset.boundAppointmentId === String(appointmentId)) {
                boot.__mountOverlayToBody?.();
                boot.__bindCallControlButtons?.();
                boot.__restorePendingCall?.();
                boot.__syncCallOverlay?.();
                boot.__observeSessionMetrics?.();
                startSessionTimers();

                return;
            }

            window.__patientConversationInitLock = true;

            teardownPatientConversationRealtime();

            if (boot.dataset.initialized === '1') {
                boot.__leaveCall?.().catch(() => {});
            }

            boot.dataset.initialized = '1';
            boot.dataset.boundAppointmentId = String(appointmentId);

            const patientId = Number(boot.dataset.patientId || 0);
            const csrf = panel.dataset.csrf || '';
            const tokenUrl = panel.dataset.tokenUrl || '';
            const pendingCallUrl = panel.dataset.pendingCallUrl || '';
            const endCallUrl = panel.dataset.endCallUrl || '';
            const pusherKey = boot.dataset.pusherKey || '';
            const pusherCluster = boot.dataset.pusherCluster || 'mt1';
            const callEnabled = boot.dataset.agoraReady === '1';
            let appointmentStatus = boot.dataset.appointmentStatus || 'new';
            const messagesWrap = document.getElementById('patient-chat-messages');
            const seen = new Set();

            function metricsEl() {
                return document.getElementById('patient-conversation-metrics');
            }

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
            let callJoinInProgress = false;

            const overlay = document.getElementById('patient-agora-overlay');
            const remoteWrap = document.getElementById('patient-agora-remote');
            const localWrap = document.getElementById('patient-agora-local');
            const chip = document.getElementById('patient-call-started-chip');

            function overlayTitleEl() {
                return document.getElementById('patient-agora-title');
            }

            function overlayDurationEl() {
                return document.getElementById('patient-overlay-call-duration');
            }

            function callChipDurationEl() {
                return document.getElementById('patient-call-chip-duration');
            }

            const labelVideo = @js(__('patient.appointments.video_call'));
            const labelVoice = @js(__('patient.appointments.voice_call'));
            const labelConnecting = metricsEl()?.dataset.labelConnecting || 'Connecting…';
            const labelCallFailed = metricsEl()?.dataset.labelCallFailed || 'Could not join the call.';
            const labelNoActiveCall = metricsEl()?.dataset.labelNoActiveCall || 'No active call yet.';
            const waitingChip = document.getElementById('patient-waiting-for-call-chip');

            function showCallToast(text, variant = 'danger') {
                if (window.Flux?.toast) {
                    window.Flux.toast({ text, variant });

                    return;
                }

                console.error(text);
            }

            function isMediaPermissionError(error) {
                if (!error) {
                    return false;
                }

                const name = String(error?.name || '');
                const message = String(error?.message || '');
                const code = String(error?.code || '');

                return name === 'NotAllowedError'
                    || name === 'PermissionDeniedError'
                    || name === 'AgoraRTCError'
                    || code === 'PERMISSION_DENIED'
                    || message.includes('NotAllowedError')
                    || message.includes('PERMISSION_DENIED')
                    || message.includes('Permission denied')
                    || message.includes('permission dismissed');
            }

            function callErrorMessage(error, fallback, mode = null) {
                if (!error) {
                    return fallback;
                }

                if (isMediaPermissionError(error)) {
                    const message = String(error?.message || '');

                    if (message.includes('denied by system')) {
                        return metricsEl()?.dataset.labelSystemMediaPermission
                            || metricsEl()?.dataset.labelCameraPermission
                            || fallback;
                    }

                    if (mode === 'audio') {
                        return metricsEl()?.dataset.labelMicPermission
                            || metricsEl()?.dataset.labelCameraPermission
                            || fallback;
                    }

                    return metricsEl()?.dataset.labelCameraPermission || fallback;
                }

                const message = error?.message || '';

                if (message) {
                    return message;
                }

                return fallback;
            }

            function resolveEffectiveCallMode(mode, payload = null, cfg = null) {
                const callType = payload?.call_type || cfg?.call_type;

                if (callType === 'audio') {
                    return 'audio';
                }

                if (callType === 'video') {
                    return 'video';
                }

                return mode === 'audio' ? 'audio' : 'video';
            }

            async function createLocalMediaTracks(mode) {
                const micTrack = await AgoraRTC.createMicrophoneAudioTrack();

                if (mode !== 'video') {
                    return { micTrack, camTrack: null, cameraUnavailable: false };
                }

                try {
                    const camTrack = await AgoraRTC.createCameraVideoTrack();

                    return { micTrack, camTrack, cameraUnavailable: false };
                } catch {
                    return { micTrack, camTrack: null, cameraUnavailable: true };
                }
            }

            async function tryCreateCameraTrack() {
                try {
                    return await AgoraRTC.createCameraVideoTrack();
                } catch {
                    return null;
                }
            }

            function releaseLocalMediaTracks(micTrack, camTrack = null) {
                if (camTrack) {
                    camTrack.stop();
                    camTrack.close();
                }

                if (micTrack) {
                    micTrack.stop();
                    micTrack.close();
                }
            }

            function mountOverlayToBody() {
                if (overlay && overlay.parentElement !== document.body) {
                    document.body.appendChild(overlay);
                }
            }

            mountOverlayToBody();
            boot.__mountOverlayToBody = mountOverlayToBody;

            function ensureAgoraSdk(timeoutMs = 12000) {
                if (window.AgoraRTC) {
                    return Promise.resolve(window.AgoraRTC);
                }

                let existing = document.querySelector('script[data-agora-sdk="1"]');
                if (!existing) {
                    existing = document.createElement('script');
                    existing.src = 'https://download.agora.io/sdk/release/AgoraRTC_N-4.23.0.js';
                    existing.dataset.agoraSdk = '1';
                    existing.async = true;
                    document.head.appendChild(existing);
                }

                return new Promise((resolve, reject) => {
                    const startedAt = Date.now();

                    const tick = () => {
                        if (window.AgoraRTC) {
                            resolve(window.AgoraRTC);

                            return;
                        }

                        if (Date.now() - startedAt >= timeoutMs) {
                            reject(new Error(metricsEl()?.dataset.labelAgoraSdkMissing || 'Agora SDK missing'));

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
                }

                if (localAudio) {
                    localAudio.stop();
                    localAudio.close();
                }

                clearLocalTracks();

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

            function markCallConnected(mode) {
                activeMode = mode;
                incomingPayload = null;

                if (!callStartedAt) {
                    startCallTimer(mode);
                } else {
                    updateActiveCallOverlayUi();
                }

                showMediaControlsForMode(mode);
                syncMediaControlUi(mode);
                refreshCallUiState();
            }

            function setOverlayConnecting(mode) {
                const titleEl = overlayTitleEl();
                if (titleEl) {
                    titleEl.textContent = labelConnecting;
                }

                const durationEl = overlayDurationEl();
                if (durationEl) {
                    durationEl.textContent = '00:00';
                }

                document.getElementById('patient-agora-toggle-video')?.classList.toggle('hidden', mode !== 'video');
                showMediaControlsForMode(mode);
            }

            function updateActiveCallOverlayUi() {
                if (!activeMode) {
                    return;
                }

                const titleEl = overlayTitleEl();
                if (titleEl) {
                    titleEl.textContent = activeMode === 'video' ? labelVideo : labelVoice;
                }

                tickCallTimer();
                syncMediaControlUi();
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

                const joinSessionBtn = document.getElementById('patient-session-join-call-btn');
                const sessionLiveBanner = document.getElementById('patient-session-live-banner');
                const hasIncomingCall = Boolean(incomingPayload)
                    || (incomingBanner && !incomingBanner.classList.contains('hidden'));
                if (joinSessionBtn) {
                    joinSessionBtn.classList.toggle('hidden', Boolean(activeMode) || hasIncomingCall);
                }

                if (sessionLiveBanner && sessionActive) {
                    sessionLiveBanner.classList.toggle('hidden', hasIncomingCall);
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
                if (!callStartedAt) {
                    return;
                }

                const formatted = formatDuration((Date.now() - callStartedAt) / 1000);
                const durationEl = overlayDurationEl();
                if (durationEl) {
                    durationEl.textContent = formatted;
                }

                const chipDurationEl = callChipDurationEl();
                if (chipDurationEl) {
                    chipDurationEl.textContent = formatted;
                }
            }

            function startCallTimer(mode) {
                if (callTimerId) {
                    clearInterval(callTimerId);
                }

                callStartedAt = Date.now();
                tickCallTimer();
                callTimerId = setInterval(tickCallTimer, 1000);

                const titleEl = overlayTitleEl();
                if (titleEl) {
                    titleEl.textContent = mode === 'video' ? labelVideo : labelVoice;
                }

                const chipLabel = document.getElementById('patient-call-chip-label');
                if (chipLabel) {
                    chipLabel.textContent = mode === 'video' ? labelVideo : labelVoice;
                }
            }

            function stopCallTimer() {
                if (callTimerId) {
                    clearInterval(callTimerId);
                }

                callTimerId = null;
                callStartedAt = null;

                const durationEl = overlayDurationEl();
                if (durationEl) {
                    durationEl.textContent = '00:00';
                }

                const chipDurationEl = callChipDurationEl();
                if (chipDurationEl) {
                    chipDurationEl.textContent = '00:00';
                }

                const chipLabel = document.getElementById('patient-call-chip-label');
                if (chipLabel) {
                    chipLabel.textContent = @js(__('patient.appointments.call_in_progress'));
                }
            }

            function syncPatientSessionFromDom(bootEl) {
                const metricsEl = document.getElementById('patient-conversation-metrics');
                const nextStatus = bootEl?.dataset.appointmentStatus || metricsEl?.dataset.status || appointmentStatus;
                const prevStatus = appointmentStatus;
                const wasWaiting = appointmentStatus !== 'in_process' && nextStatus === 'in_process';

                appointmentStatus = nextStatus;
                if (bootEl && bootEl.dataset.appointmentStatus !== appointmentStatus) {
                    bootEl.dataset.appointmentStatus = appointmentStatus;
                }

                if (metricsEl && nextStatus === 'in_process' && metricsEl.dataset.status !== nextStatus) {
                    metricsEl.dataset.status = nextStatus;
                }

                if (prevStatus === 'in_process' && nextStatus === 'completed') {
                    clearPendingCallStorage();
                    incomingPayload = null;
                    incomingBanner?.classList.add('hidden');
                    dismissIncomingAlert();
                    window.dispatchEvent(new CustomEvent('mashora:call-ended', {
                        detail: { appointment_id: appointmentId },
                    }));
                }

                if (wasWaiting && incomingLabel && incomingBanner) {
                    showCallToast(metricsEl?.dataset.sessionStartedWaiting || 'Session started.', 'success');
                    startSessionTimers();
                    refreshCallUiState();
                }
            }

            function tickSessionTimers() {
                const metrics = metricsEl();
                const elapsedEl = document.getElementById('patient-timer-session-elapsed');
                const remainingEl = document.getElementById('patient-timer-session-remaining');
                if (!metrics || !elapsedEl || !remainingEl) {
                    return;
                }

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

            function replayActiveVideoTracks() {
                const localTrack = boot.__localVideo || localVideo;
                if (localTrack) {
                    try {
                        localTrack.stop();
                        localTrack.play('patient-agora-local');
                    } catch (error) {
                        console.error('Failed to replay local video track', error);
                    }
                }

                if (!agoraClient) {
                    return;
                }

                agoraClient.remoteUsers.forEach((user) => {
                    if (!user.videoTrack) {
                        return;
                    }

                    try {
                        user.videoTrack.stop();
                        user.videoTrack.play('patient-agora-remote');
                    } catch (error) {
                        console.error('Failed to replay remote video track', error);
                    }
                });
            }

            function showOverlay(show) {
                if (!overlay) {
                    return;
                }

                overlay.classList.toggle('hidden', !show);
                overlay.setAttribute('aria-hidden', show ? 'false' : 'true');

                if (show) {
                    window.requestAnimationFrame(() => {
                        window.requestAnimationFrame(() => {
                            replayActiveVideoTracks();
                        });
                    });
                }
            }

            function assignLocalTracks(audio, video = null) {
                localAudio = audio;
                localVideo = video;
                boot.__localAudio = audio;
                boot.__localVideo = video;
            }

            function clearLocalTracks() {
                assignLocalTracks(null, null);
            }

            function mediaControlOptions(mode = activeMode) {
                return {
                    micBtnId: 'patient-agora-toggle-mic',
                    videoBtnId: 'patient-agora-toggle-video',
                    localPreviewId: 'patient-agora-local',
                    localAudio: boot.__localAudio || localAudio,
                    localVideo: boot.__localVideo || localVideo,
                    mode,
                };
            }

            function syncMediaControlUi(mode = activeMode) {
                window.MashoraAgoraMediaControls?.sync(mediaControlOptions(mode));
            }

            function showMediaControlsForMode(mode) {
                window.MashoraAgoraMediaControls?.showControlsForMode(
                    'patient-agora-toggle-mic',
                    'patient-agora-toggle-video',
                    mode,
                );
            }

            async function leaveCallLocal() {
                stopCallTimer();
                if (localVideo) {
                    localVideo.stop();
                    localVideo.close();
                }
                if (localAudio) {
                    localAudio.stop();
                    localAudio.close();
                }
                clearLocalTracks();
                if (agoraClient) {
                    try {
                        await agoraClient.leave();
                    } catch {
                        // ignore cleanup errors
                    }

                    agoraClient = null;
                }
                activeMode = null;
                incomingPayload = null;
                if (remoteWrap) {
                    remoteWrap.innerHTML = '';
                }
                if (localWrap) {
                    localWrap.innerHTML = '';
                }
                showOverlay(false);
                syncMediaControlUi(null);
                document.getElementById('patient-agora-toggle-mic')?.classList.add('hidden');
                document.getElementById('patient-agora-toggle-video')?.classList.add('hidden');
            }

            async function postEndCall() {
                if (!endCallUrl) {
                    return;
                }

                try {
                    await fetch(endCallUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                } catch (_) {
                    // ignore network errors
                }

                clearPendingCallStorage();
            }

            async function leaveCall(notifyRemote = true) {
                const wasActive = activeMode;

                dismissIncomingAlert();
                incomingBanner?.classList.add('hidden');
                await leaveCallLocal();

                if (notifyRemote && wasActive) {
                    await postEndCall();
                    window.dispatchEvent(new CustomEvent('mashora:call-ended', {
                        detail: { appointment_id: appointmentId },
                    }));
                }

                refreshCallUiState();
            }

            async function handleRemoteCallEnded(data) {
                if (Number(data?.appointment_id || 0) !== appointmentId) {
                    return;
                }

                clearPendingCallStorage();
                incomingPayload = null;
                incomingBanner?.classList.add('hidden');
                dismissIncomingAlert();
                window.MashoraRealtimeAlerts?.stopIncomingRing();

                if (activeMode) {
                    await leaveCallLocal();
                    refreshCallUiState();
                }

                window.dispatchEvent(new CustomEvent('mashora:call-ended', {
                    detail: { appointment_id: appointmentId },
                }));
            }

            function registerRemoteUserHandlers(client, mode) {
                client.on('user-published', async (user, mediaType) => {
                    try {
                        await client.subscribe(user, mediaType);
                        if (mediaType === 'video') {
                            user.videoTrack?.play('patient-agora-remote');
                        }
                        if (mediaType === 'audio') {
                            user.audioTrack?.play();
                        }

                        markCallConnected(mode);
                    } catch (error) {
                        console.error('Failed to subscribe to remote user media', error);
                    }
                });

                client.on('user-unpublished', (user, mediaType) => {
                    if (mediaType === 'video' && remoteWrap) {
                        remoteWrap.innerHTML = '';
                    }
                });

                client.on('user-left', () => {
                    if (remoteWrap) {
                        remoteWrap.innerHTML = '';
                    }
                });
            }

            async function subscribeExistingRemoteUsers(client, mode) {
                for (const user of client.remoteUsers) {
                    try {
                        if (user.hasAudio) {
                            await client.subscribe(user, 'audio');
                            user.audioTrack?.play();
                        }

                        if (user.hasVideo && mode === 'video') {
                            await client.subscribe(user, 'video');
                            user.videoTrack?.play('patient-agora-remote');
                        }
                    } catch (error) {
                        console.error('Failed to subscribe to existing remote user', error);
                    }
                }

                if (client.remoteUsers.length > 0) {
                    markCallConnected(mode);
                }
            }

            async function resolveJoinConfig(payload) {
                const serverCfg = await refreshConfig();
                const payloadCfg = resolveAgoraConfig(payload);

                if (serverCfg?.agora_app_id && serverCfg?.agora_channel) {
                    return {
                        agora_app_id: serverCfg.agora_app_id,
                        agora_token: serverCfg.agora_token || null,
                        agora_channel: serverCfg.agora_channel,
                        call_type: payloadCfg?.call_type || payload?.call_type || 'video',
                    };
                }

                return payloadCfg;
            }

            function maybeEndCallWhenSessionExpired(leftSeconds) {
                if (metricsEl()?.dataset.relaxedSessionLimits === '1') {
                    return;
                }

                if (leftSeconds > 0) {
                    sessionEndedDisconnectHandled = false;

                    return;
                }

                if (!activeMode || sessionEndedDisconnectHandled) {
                    return;
                }

                sessionEndedDisconnectHandled = true;
                const endedMessage = metricsEl()?.dataset.sessionEnded || 'Session time has ended.';

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

            function dismissIncomingAlert() {
                window.MashoraRealtimeAlerts?.stopIncomingRing();
            }

            function resolveAgoraConfig(payload) {
                if (payload && payload.agora_app_id && payload.agora_channel) {
                    return payload;
                }

                return null;
            }

            function applySessionStartedPayload(data) {
                appointmentStatus = data.status || 'in_process';
                boot.dataset.appointmentStatus = appointmentStatus;
                const metrics = metricsEl();
                if (metrics) {
                    metrics.dataset.status = data.status || 'in_process';
                    metrics.dataset.sessionStart = data.actual_start_at || '';
                    metrics.dataset.sessionEnd = data.extend_at || '';
                }

                document.getElementById('patient-session-live-banner')?.classList.remove('hidden');
                document.getElementById('patient-chat-locked-callout')?.classList.add('hidden');

                const waitingChipEl = document.getElementById('patient-waiting-for-call-chip');
                if (waitingChipEl) {
                    waitingChipEl.classList.remove('hidden');
                }

                const statusLabel = document.getElementById('patient-conversation-status-label');
                if (statusLabel) {
                    statusLabel.textContent = @js(__('patient.appointments.status_in_process'));
                }

                if (window.Livewire) {
                    Livewire.dispatch('patient-session-started', { appointmentId });
                }

                showCallToast(metricsEl()?.dataset.sessionStartedWaiting || @js(__('patient.appointments.session_started_waiting')), 'success');
                startSessionTimers();
                refreshCallUiState();
            }

            function showIncomingCallBanner(data, options = {}) {
                if (appointmentStatus !== 'in_process') {
                    clearPendingCallStorage();
                    incomingPayload = null;
                    incomingBanner?.classList.add('hidden');

                    return;
                }

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
                        showIncomingCallBanner(data, { silent: true });
                    }
                } catch (_) {
                    // ignore parse errors
                }
            }

            async function restorePendingCallFromServer() {
                if (!pendingCallUrl) {
                    return;
                }

                try {
                    const res = await fetch(pendingCallUrl, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!res.ok) {
                        return;
                    }

                    const data = await res.json();
                    if (!data?.pending || !data?.agora_app_id) {
                        clearPendingCallStorage();
                        incomingPayload = null;
                        incomingBanner?.classList.add('hidden');
                        refreshCallUiState();

                        return;
                    }

                    showIncomingCallBanner(data, { silent: true });
                } catch (_) {
                    // ignore network errors
                }
            }

            async function joinSessionCall() {
                if (incomingPayload?.agora_app_id) {
                    boot.__acceptIncoming?.();

                    return;
                }

                restorePendingCallFromStorage();
                if (incomingPayload?.agora_app_id) {
                    boot.__acceptIncoming?.();

                    return;
                }

                await restorePendingCallFromServer();
                if (incomingPayload?.agora_app_id) {
                    boot.__acceptIncoming?.();

                    return;
                }

                showCallToast(labelNoActiveCall, 'warning');
                refreshCallUiState();
            }

            async function joinCall(mode, payload = null) {
                if (!callEnabled || activeMode || callJoinInProgress) {
                    return;
                }

                if (appointmentStatus !== 'in_process') {
                    showCallToast(labelNoActiveCall, 'warning');

                    return;
                }

                callJoinInProgress = true;
                dismissIncomingAlert();
                incomingBanner?.classList.add('hidden');
                showOverlay(true);
                let effectiveMode = resolveEffectiveCallMode(mode, payload, null);
                setOverlayConnecting(effectiveMode);
                let micTrack = null;
                let camTrack = null;

                try {
                    await ensureAgoraSdk();
                    await resetPartialAgoraJoin();

                    effectiveMode = resolveEffectiveCallMode(mode, payload, null);
                    setOverlayConnecting(effectiveMode);

                    const tracks = await createLocalMediaTracks(effectiveMode);
                    micTrack = tracks.micTrack;
                    camTrack = tracks.camTrack;
                    let cameraUnavailable = tracks.cameraUnavailable;

                    const cfg = await resolveJoinConfig(payload);
                    const resolvedMode = resolveEffectiveCallMode(mode, payload, cfg);

                    if (resolvedMode !== effectiveMode) {
                        if (resolvedMode === 'audio' && camTrack) {
                            camTrack.stop();
                            camTrack.close();
                            camTrack = null;
                            cameraUnavailable = false;
                        } else if (resolvedMode === 'video' && !camTrack) {
                            camTrack = await tryCreateCameraTrack();
                            cameraUnavailable = !camTrack;
                        }

                        effectiveMode = resolvedMode;
                        setOverlayConnecting(effectiveMode);
                    }

                    if (!cfg || !cfg.agora_app_id || !cfg.agora_channel) {
                        throw new Error(labelNoActiveCall);
                    }

                    clearPendingCallStorage();

                    const client = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });
                    agoraClient = client;
                    registerRemoteUserHandlers(client, effectiveMode);

                    await client.join(cfg.agora_app_id, cfg.agora_channel, cfg.agora_token || null, null);
                    assignLocalTracks(micTrack, camTrack);

                    if (camTrack) {
                        camTrack.play('patient-agora-local');
                        await client.publish([micTrack, camTrack]);
                    } else {
                        await client.publish([micTrack]);
                    }

                    micTrack = null;
                    camTrack = null;

                    markCallConnected(effectiveMode);
                    replayActiveVideoTracks();

                    if (cameraUnavailable && effectiveMode === 'video') {
                        showCallToast(metricsEl()?.dataset.labelCameraUnavailable || labelCallFailed, 'warning');
                    }

                    subscribeExistingRemoteUsers(client, effectiveMode).catch((error) => {
                        console.error('Failed to subscribe to existing remote users', error);
                    });
                } catch (e) {
                    console.error(e);
                    releaseLocalMediaTracks(micTrack, camTrack);
                    await resetPartialAgoraJoin();
                    showOverlay(false);
                    showCallToast(callErrorMessage(e, labelCallFailed, effectiveMode));
                    refreshCallUiState();
                } finally {
                    callJoinInProgress = false;
                }
            }

            boot.__joinCall = (mode, payload = null) => joinCall(mode, payload);
            boot.__joinSessionCall = () => joinSessionCall();
            boot.__leaveCall = (notifyRemote = true) => leaveCall(notifyRemote);
            boot.__toggleMic = () => {
                window.MashoraAgoraMediaControls?.toggleMic(mediaControlOptions())
                    .catch((error) => console.error(error));
            };
            boot.__toggleVideo = () => {
                window.MashoraAgoraMediaControls?.toggleVideo(mediaControlOptions())
                    .catch((error) => console.error(error));
            };
            boot.__acceptIncoming = () => {
                if (callJoinInProgress || activeMode) {
                    return;
                }

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

                const mode = resolveEffectiveCallMode('audio', incomingPayload, null);
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
                // Join and overlay controls use document-level delegation to prevent duplicate Agora joins.
            }

            bindCallControlButtons();
            boot.__bindCallControlButtons = bindCallControlButtons;
            boot.__restorePendingCall = restorePendingCallFromStorage;

            if (appointmentStatus === 'in_process') {
                restorePendingCallFromStorage();
                restorePendingCallFromServer();
            } else {
                clearPendingCallStorage();
                incomingPayload = null;
                incomingBanner?.classList.add('hidden');
            }

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

                    if (event.target.closest('#patient-session-join-call-btn')) {
                        event.preventDefault();
                        bootEl.__joinSessionCall?.();
                    }

                    if (event.target.closest('#patient-agora-leave, [data-video-call-leave="patient-agora-leave"]')) {
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

            if (!window.__patientConversationCallEndedHook) {
                window.__patientConversationCallEndedHook = true;

                window.addEventListener('mashora:call-ended', (event) => {
                    handleRemoteCallEnded(event.detail || {});
                });
            }

            if (!window.__patientConversationIncomingCallHook) {
                window.__patientConversationIncomingCallHook = true;

                window.addEventListener('mashora:incoming-call', (event) => {
                    const data = event.detail;
                    if (Number(data?.appointment_id || 0) === appointmentId) {
                        showIncomingCallBanner(data, { silent: true });
                    }
                });

                window.addEventListener('mashora:session-started', (event) => {
                    const data = event.detail;
                    if (Number(data?.appointment_id || 0) === appointmentId) {
                        applySessionStartedPayload(data);
                    }
                });
            }

            if (!window.__patientConversationNavigateHook) {
                window.__patientConversationNavigateHook = true;

                document.addEventListener('livewire:navigating', () => {
                    teardownPatientConversationRealtime();
                });
            }

            if (pusherKey) {
                const pusher = window.MashoraPatientPusher.acquire({
                    key: pusherKey,
                    cluster: pusherCluster,
                    csrf,
                });

                if (pusher) {
                    const appointmentChannelName = 'private-appointment.' + appointmentId;
                    const channel = window.MashoraPatientPusher.subscribe(appointmentChannelName);

                    if (channel && channel.__mashoraPatientConversationBound !== appointmentId) {
                        channel.__mashoraPatientConversationBound = appointmentId;

                        channel.bind('pusher:subscription_error', (error) => {
                            console.error('Pusher appointment channel error', error);
                        });
                        channel.bind('message.created', (data) => appendMessageRow(data));
                        channel.bind('session.started', (data) => {
                            applySessionStartedPayload(data);
                        });
                        channel.bind('call.incoming', (data) => {
                            if (Number(data?.appointment_id || appointmentId) !== appointmentId) {
                                return;
                            }

                            window.MashoraRealtimeAlerts?.stopIncomingRing();
                            showIncomingCallBanner(data, { silent: true });
                        });
                        channel.bind('call.ended', (data) => {
                            handleRemoteCallEnded(data);
                        });
                    }

                    window.__patientConversationTeardown = () => {
                        boot.__leaveCall?.().catch(() => {});
                        if (channel) {
                            delete channel.__mashoraPatientConversationBound;
                        }
                        window.MashoraPatientPusher.unsubscribe(appointmentChannelName);
                        window.MashoraPatientPusher.release();
                        delete boot.dataset.initialized;
                        delete boot.dataset.boundAppointmentId;
                        delete boot.dataset.sessionObserved;
                    };
                }
            } else {
                console.warn('Pusher is not configured: set PUSHER_APP_KEY and BROADCAST_CONNECTION=pusher');
            }

            boot.__syncCallOverlay = () => {
                if (activeMode) {
                    showOverlay(true);
                    updateActiveCallOverlayUi();
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
                            bootEl?.__mountOverlayToBody?.();
                            bootEl?.__syncCallOverlay?.();
                            bootEl?.__observeSessionMetrics?.();
                            startSessionTimers();
                        });
                    });
                };

                if (window.Livewire) {
                    registerHook();
                } else {
                    document.addEventListener('livewire:init', registerHook);
                }
            }

            function observeSessionMetrics() {
                const metrics = metricsEl();
                if (!metrics) {
                    return;
                }

                if (boot.__sessionMetricsObserver) {
                    boot.__sessionMetricsObserver.disconnect();
                    boot.__sessionMetricsObserver = null;
                }

                const sessionObserver = new MutationObserver(() => {
                    syncPatientSessionFromDom(boot);
                    refreshCallUiState();
                    startSessionTimers();
                });

                sessionObserver.observe(metrics, {
                    attributes: true,
                    attributeFilter: ['data-status', 'data-session-start', 'data-session-end'],
                });

                boot.__sessionMetricsObserver = sessionObserver;
            }

            boot.__observeSessionMetrics = observeSessionMetrics;

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
                observeSessionMetrics();
            }

            window.__patientConversationInitLock = false;
        }

        if (!window.__patientConversationInitBound) {
            window.__patientConversationInitBound = true;

            document.addEventListener('DOMContentLoaded', schedulePatientConversationInit);
            document.addEventListener('livewire:navigated', schedulePatientConversationInit);
        }

        schedulePatientConversationInit();
    </script>
@endpush
