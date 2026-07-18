<?php

use App\Events\AppointmentChatMessageSent;
use App\Models\Appointment;
use App\Models\ChMessage;
use App\Models\User;
use App\Services\AppointmentMissedService;
use App\Services\AppointmentSessionService;
use App\Services\PatientMissedAppointmentService;
use App\Support\DoctorAgoraChannel;
use Flux\Flux;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
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

        app(AppointmentMissedService::class)->processDueMissedAppointments();

        $this->appointment = $appointment->fresh() ?? $appointment;
        $this->appointment->loadMissing(['doctor.specialities']);
        $this->refreshAgoraCredentials();
        $this->loadMessages();
    }

    public function canResolveMissed(): bool
    {
        return app(PatientMissedAppointmentService::class)->canResolve($this->appointment);
    }

    public bool $showRefundModal = false;

    public string $refundReason = 'service_not_provided';

    public string $refundReasonNote = '';

    public string $refundDestination = 'wallet';

    public function canSelectPaymentAccountRefund(): bool
    {
        return $this->appointment->hasPaymentAccountRefundSource();
    }

    /**
     * @return array<string, string>
     */
    public function refundReasonOptions(): array
    {
        return [
            'duplicate_payment' => __('patient.missed.refund_reasons.duplicate_payment'),
            'appointment_cancelled' => __('patient.missed.refund_reasons.appointment_cancelled'),
            'service_not_provided' => __('patient.missed.refund_reasons.service_not_provided'),
            'technical_issue' => __('patient.missed.refund_reasons.technical_issue'),
            'doctor_unable_to_attend' => __('patient.missed.refund_reasons.doctor_unable_to_attend'),
            'other' => __('patient.missed.refund_reasons.other'),
        ];
    }

    public function promptRefundMissed(int $appointmentId): void
    {
        if ((int) $this->appointment->id !== $appointmentId) {
            abort(404);
        }

        if (! $this->canResolveMissed()) {
            Flux::toast(variant: 'warning', text: __('patient.missed.not_eligible'));

            return;
        }

        $this->showRefundModal = true;
        $this->refundReason = 'service_not_provided';
        $this->refundReasonNote = '';
        $this->refundDestination = 'wallet';
    }

    public function dismissRefundMissedModal(): void
    {
        $this->showRefundModal = false;
        $this->refundReason = 'service_not_provided';
        $this->refundReasonNote = '';
        $this->refundDestination = 'wallet';
    }

    public function confirmRefundMissed(): void
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            abort(403);
        }

        if (! $this->canResolveMissed()) {
            Flux::toast(variant: 'warning', text: __('patient.missed.not_eligible'));
            $this->dismissRefundMissedModal();

            return;
        }

        $this->validate([
            'refundReason' => ['required', 'string', 'in:'.implode(',', PatientMissedAppointmentService::REFUND_REASON_KEYS)],
            'refundReasonNote' => ['nullable', 'string', 'max:2000', 'required_if:refundReason,other'],
            'refundDestination' => ['required', 'string', 'in:wallet,payment_account'],
        ], [], [
            'refundReason' => __('patient.missed.refund'),
            'refundReasonNote' => __('patient.missed.reason_note_label'),
            'refundDestination' => __('patient.missed.refund_modal.destination_label'),
        ]);

        try {
            app(PatientMissedAppointmentService::class)->requestRefund(
                $user,
                $this->appointment,
                $this->refundReason,
                $this->refundReason === 'other' ? $this->refundReasonNote : null,
                $this->refundDestination,
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $message = collect($e->errors())->flatten()->first();

            Flux::toast(
                variant: 'danger',
                text: is_string($message) && $message !== ''
                    ? $message
                    : __('patient.missed.not_eligible'),
            );

            return;
        } catch (\Throwable $e) {
            report($e);

            Flux::toast(
                variant: 'danger',
                text: __('patient.missed.refund_request_failed'),
            );

            return;
        }

        $this->appointment->refresh();
        $this->dismissRefundMissedModal();

        Flux::toast(
            variant: 'success',
            text: __('patient.missed.refund_request_submitted'),
        );

        $this->redirectRoute('patient.appointments', ['tab' => 'missed'], navigate: true);
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

        broadcast(new AppointmentChatMessageSent($message))->toOthers();

        $this->reset('draft');
        $this->loadMessages();

        $this->js(
            'window.dispatchEvent(new CustomEvent("mashora:chat-message-sent", { detail: '
            .Js::from([
                'id' => $message->id,
                'body' => $message->body,
                'send_by' => 'patient',
                'from_id' => $patient->id,
                'created_at' => $message->created_at?->toIso8601String(),
                'self' => true,
            ])
            .' }))'
        );
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

    public function approveSessionStart(AppointmentSessionService $sessions): void
    {
        if ($this->appointment->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $sessions->approveStart($this->appointment)) {
            Flux::toast(variant: 'warning', text: __('patient.appointments.session_start_request_cannot_approve'));

            return;
        }

        $this->appointment->refresh();

        Flux::toast(variant: 'success', text: __('patient.appointments.session_start_request_approved'));
    }

    public function declineSessionStart(AppointmentSessionService $sessions): void
    {
        if ($this->appointment->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $sessions->clearStartRequest($this->appointment)) {
            Flux::toast(variant: 'warning', text: __('patient.appointments.session_start_request_cannot_decline'));

            return;
        }

        $this->appointment->refresh();

        Flux::toast(variant: 'success', text: __('patient.appointments.session_start_request_declined'));
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

        app(AppointmentMissedService::class)->processDueMissedAppointments();

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

    #[On('patient-session-completed')]
    public function onPatientSessionCompleted(int $appointmentId = 0): void
    {
        if ($appointmentId !== 0 && $appointmentId !== (int) $this->appointment->id) {
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
        if ($this->sessionTimeExpired()) {
            return __('patient.appointments.session_finished');
        }

        return $this->statusLabel((string) $this->appointment->status);
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'new' => __('patient.appointments.status_new'),
            'in_process' => __('patient.appointments.status_in_process'),
            'pending_follow_up' => __('patient.follow_up.badge'),
            'rescheduled' => __('patient.appointments.tab_rescheduled'),
            'completed' => __('patient.appointments.status_completed'),
            'cancelled' => __('patient.appointments.tab_cancelled'),
            'not_attended' => __('patient.appointments.status_missed'),
            default => __('patient.appointments.status_'.$status),
        };
    }

    public function doctorInitials(): string
    {
        return Str::of($this->appointment->doctor?->displayName() ?? '')
            ->explode(' ')
            ->take(2)
            ->map(fn (string $word): string => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function doctorSpecialtyLabel(): string
    {
        $speciality = $this->appointment->doctor?->specialities?->first();

        if ($speciality === null) {
            return __('patient.appointments.specialist_label');
        }

        if (app()->getLocale() === 'ar' && filled($speciality->title_ar)) {
            return (string) $speciality->title_ar;
        }

        return (string) ($speciality->title ?? $speciality->title_ar ?? __('patient.appointments.specialist_label'));
    }

    public function appointmentDateLabel(): ?string
    {
        $date = $this->appointment->appointment_date ?? $this->appointment->scheduled_at;

        if ($date === null) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($date)
            ->timezone(config('app.timezone'))
            ->locale(app()->getLocale())
            ->translatedFormat('D, d M Y');
    }

    public function appointmentTimeRangeLabel(): ?string
    {
        $startsAt = $this->appointment->sessionStartsAt();
        $start = $startsAt?->timezone(config('app.timezone'))->format('h:i A');

        if ($start === null && filled($this->appointment->formattedSessionStart())) {
            $start = $this->appointment->formattedSessionStart();
        }

        $endsAt = (string) $this->appointment->status === 'in_process' && $this->appointment->extend_at !== null
            ? $this->appointment->extend_at
            : $this->appointment->sessionEndsAt();

        $end = $endsAt?->timezone(config('app.timezone'))->format('h:i A');

        if ($start !== null && $end !== null) {
            return $start.' – '.$end;
        }

        return $start ?? $end;
    }

    public function appointmentEndsAtLabel(): ?string
    {
        $endsAt = (string) $this->appointment->status === 'in_process' && $this->appointment->extend_at !== null
            ? $this->appointment->extend_at
            : $this->appointment->sessionEndsAt();

        return $endsAt?->timezone(config('app.timezone'))->format('h:i A');
    }

    public function preSessionStatusMessage(): string
    {
        if ((string) $this->appointment->status === 'in_process') {
            return __('patient.appointments.waiting_for_specialist_call');
        }

        if (! in_array((string) $this->appointment->status, ['new', 'rescheduled'], true)) {
            return __('patient.appointments.luxury.session_not_active');
        }

        if ($this->appointment->isSessionStartRequestPending()) {
            return __('patient.appointments.session_start_request_banner');
        }

        if (! $this->appointment->isChatOpen()) {
            return __('patient.appointments.chat_locked_until_one_hour');
        }

        return __('patient.appointments.waiting_for_doctor_hint');
    }

    public function liveSessionIdleMessage(): string
    {
        if ($this->appointment->allowsPatientCalls()) {
            return __('patient.appointments.luxury.waiting_for_call');
        }

        return __('patient.appointments.session_started_waiting');
    }

    public function sessionTimeExpired(): bool
    {
        if ((bool) config('appointments.relaxed_session_limits', false)) {
            return false;
        }

        if ((string) $this->appointment->status !== 'in_process') {
            return false;
        }

        return $this->appointment->extend_at !== null && $this->appointment->extend_at->isPast();
    }
}; ?>

<div
    id="patient-conversation-root"
    class="patient-luxury-conversation sm:bg-transparent sm:space-y-5 sm:pb-0 max-sm:flex max-sm:h-svh max-sm:max-h-svh max-sm:min-h-0 max-sm:flex-col max-sm:overflow-hidden max-sm:bg-slate-50 max-sm:pb-0"
    data-test="patient-luxury-conversation"
    @if (! in_array($appointment->status, ['completed', 'cancelled', 'not_attended'], true)) wire:poll.3s="refreshAppointmentSession" @endif
>
    @include('partials.patient-luxury-consultation-mobile')

    <div class="hidden sm:block sm:space-y-5 sm:pb-[calc(4.75rem+env(safe-area-inset-bottom))]">
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
                <p id="patient-conversation-status-label" class="mt-0.5 text-xs text-zinc-500">{{ $this->conversationHeaderSubtitle() }}</p>
            </div>
        </div>
        </div>
    </header>

    @if ($appointment->allowsPatientCalls() && $appointment->status === 'in_process')
        <div class="hidden flex-wrap items-center gap-2 sm:flex">
            @if ($this->sessionTimeExpired())
                <span
                    id="patient-session-finished-chip-desktop"
                    class="inline-flex rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-[11px] font-semibold text-zinc-600"
                >
                    {{ __('patient.appointments.session_finished') }}
                </span>
            @else
                <span
                    id="patient-call-started-chip-desktop"
                    class="hidden items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700"
                >
                    <span id="patient-call-chip-label-desktop">{{ __('patient.appointments.call_in_progress') }}</span>
                    <span id="patient-call-chip-duration-desktop" class="font-mono tabular-nums">00:00</span>
                </span>
                <span
                    id="patient-session-finished-chip-desktop"
                    class="hidden rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-[11px] font-semibold text-zinc-600"
                >
                    {{ __('patient.appointments.session_finished') }}
                </span>
                <span
                    id="patient-waiting-for-call-chip-desktop"
                    class="inline-flex rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-[11px] font-semibold text-zinc-600"
                >
                    {{ __('patient.appointments.waiting_for_specialist_call') }}
                </span>
            @endif
        </div>
    @endif

    @if ($this->canResolveMissed())
        <div class="px-6 sm:px-0">
            <div class="rounded-2xl border border-orange-200/90 bg-gradient-to-r from-orange-50 to-amber-50/80 px-4 py-4 shadow-sm">
                @include('partials.patient-luxury-missed-resolution', ['appointment' => $appointment])
            </div>
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
        data-session-finished="{{ __('patient.appointments.session_finished') }}"
        data-relaxed-session-limits="{{ config('appointments.relaxed_session_limits') ? '1' : '0' }}"
    ></div>

    <div class="space-y-4 px-6 pt-4 sm:space-y-5 sm:px-0 sm:pt-0">
    @if ($appointment->isSessionStartRequestPending())
        <div
            class="rounded-2xl border border-[#10B981]/35 bg-gradient-to-br from-emerald-50 via-white to-white p-4 shadow-sm ring-1 ring-[#10B981]/10 sm:p-5"
            data-test="patient-session-start-dialog"
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-[#047857]">{{ __('patient.appointments.session_start_request_pending') }}</p>
                    <p class="mt-1 text-sm text-zinc-700">{{ __('patient.appointments.session_start_request_banner') }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <flux:button
                        type="button"
                        size="sm"
                        variant="primary"
                        class="!rounded-xl !bg-[#10B981] !text-white hover:!brightness-95"
                        wire:click="approveSessionStart"
                        wire:loading.attr="disabled"
                    >
                        {{ __('patient.appointments.session_start_request_approve') }}
                    </flux:button>
                    <flux:button
                        type="button"
                        size="sm"
                        variant="filled"
                        class="!rounded-xl !border !border-zinc-300 !bg-white !text-black shadow-sm hover:!bg-zinc-100"
                        wire:click="declineSessionStart"
                        wire:loading.attr="disabled"
                    >
                        {{ __('patient.appointments.session_start_request_decline') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    @if (in_array($appointment->status, ['new', 'rescheduled'], true) && ! $appointment->isChatOpen())
        <flux:callout id="patient-chat-locked-callout" variant="secondary" icon="clock" class="border-zinc-200">
            {{ __('patient.appointments.chat_locked_until_one_hour') }}
        </flux:callout>
    @elseif (in_array($appointment->status, ['new', 'rescheduled'], true) && $appointment->isChatOpen())
        <flux:callout variant="secondary" icon="chat-bubble-left-right" class="border-zinc-200 bg-white scheme-light !text-zinc-900">
            <span class="text-sm text-zinc-900">{{ __('patient.appointments.chat_open_before_session') }}</span>
        </flux:callout>
    @elseif ($appointment->status === 'completed' && ! $appointment->isChatOpen())
        <flux:callout variant="secondary" icon="check-circle" class="border-zinc-200 bg-white scheme-light !text-zinc-900">
            <span class="text-sm text-zinc-900">{{ __('patient.appointments.session_closed') }}</span>
        </flux:callout>
    @elseif ($appointment->status === 'completed' && $appointment->isChatOpen())
        <flux:callout variant="secondary" icon="chat-bubble-left-right" class="border-zinc-200 bg-white scheme-light !text-zinc-900">
            <span class="text-sm text-zinc-900">{{ __('patient.appointments.chat_open_after_completed', [
                'date' => $appointment->chatOpenUntil()->locale(app()->getLocale())->translatedFormat('d M Y · g:i A'),
            ]) }}</span>
        </flux:callout>
    @endif

    <div
        id="patient-session-live-banner"
        @class([
            'max-sm:hidden rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-white px-4 py-3 shadow-sm shadow-emerald-900/5 ring-1 ring-emerald-100',
            'hidden sm:block' => $appointment->status === 'in_process' && ! $this->sessionTimeExpired(),
            'hidden' => $appointment->status !== 'in_process' || $this->sessionTimeExpired(),
        ])
    >
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-emerald-950">{{ __('patient.appointments.session_live_banner_title') }}</p>
                <p class="mt-0.5 text-sm text-emerald-800">{{ __('patient.appointments.session_live_banner_body') }}</p>
            </div>
            <a
                href="#patient-chat-panel"
                class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-xl border border-emerald-300 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50"
            >
                {{ __('patient.appointments.open_session_chat') }}
            </a>
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
        id="incoming-call-banner-desktop"
        wire:ignore
        class="hidden rounded-2xl border border-emerald-300 bg-gradient-to-r from-emerald-50 via-emerald-50/95 to-white px-4 py-3 shadow-md shadow-emerald-900/10 ring-1 ring-inset ring-emerald-200/80 sm:px-5"
        role="alert"
        data-test="patient-incoming-call-banner-desktop"
    >
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-start gap-2.5">
                <span class="relative mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white shadow-sm shadow-emerald-900/25">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                    <flux:icon name="video-camera" variant="mini" class="relative size-4" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-emerald-950">{{ __('patient.appointments.incoming_call_title') }}</p>
                    <p id="incoming-call-label-desktop" class="mt-0.5 text-sm text-emerald-800"></p>
                </div>
            </div>
            <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center">
                <button
                    type="button"
                    id="incoming-call-accept-desktop"
                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-[#10B981] px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-900/20 transition hover:brightness-95 sm:min-h-10 sm:w-auto"
                >
                    <flux:icon name="video-camera" variant="mini" class="size-4" />
                    {{ __('patient.appointments.join_call') }}
                </button>
                <button
                    type="button"
                    id="incoming-call-dismiss-desktop"
                    class="inline-flex min-h-10 w-full items-center justify-center rounded-xl border border-emerald-300 bg-white px-4 py-2 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50 sm:w-auto"
                >
                    {{ __('patient.appointments.dismiss_call') }}
                </button>
            </div>
        </div>
    </div>

    <div
        id="patient-chat-panel"
        class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-[0_8px_32px_0_rgba(0,0,0,0.03)] ring-1 ring-slate-100 sm:border-zinc-200/90 sm:shadow-[0_20px_55px_-32px_rgba(15,23,42,0.35)] sm:ring-zinc-100"
        data-appointment-id="{{ $appointment->id }}"
        data-notify-url="{{ route('patient.appointments.realtime.notify-call', $appointment) }}"
        data-pending-call-url="{{ route('patient.appointments.realtime.pending-call', $appointment) }}"
        data-end-call-url="{{ route('patient.appointments.realtime.end-call', $appointment) }}"
        data-token-url="{{ route('patient.appointments.realtime.agora-token', $appointment) }}"
        data-csrf="{{ csrf_token() }}"
    >
        <div class="grid min-h-[34rem] grid-cols-1 max-sm:min-h-[min(32rem,calc(100dvh-18rem))] lg:grid-cols-12">
            <div class="flex min-h-[30rem] flex-col border-zinc-200 max-sm:min-h-[min(28rem,calc(100dvh-20rem))] lg:col-span-8 lg:border-e">
                <div id="patient-chat-messages" class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-gradient-to-b from-zinc-50/90 via-zinc-50/70 to-zinc-100/70 px-4 py-4 sm:px-5" wire:ignore>
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
                            <p class="mt-0.5 text-sm font-semibold text-zinc-800">{{ $this->statusLabel((string) $appointment->status) }}</p>
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

    <flux:modal wire:model.self="showRefundModal" class="max-w-md rounded-2xl shadow-xl" :closable="true">
        <div class="px-6 py-8 sm:px-8">
            <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-[#10B981]/10 text-[#10B981]">
                <flux:icon name="banknotes" variant="outline" class="size-8" />
            </div>

            <flux:heading size="lg" class="mt-5 text-center font-semibold text-zinc-900">
                {{ __('patient.missed.refund_modal.title') }}
            </flux:heading>

            <flux:text class="mt-2 text-center text-sm leading-relaxed text-zinc-600">
                {{ __('patient.missed.refund_modal.body') }}
            </flux:text>

            <div class="mt-4 space-y-3">
                <flux:field>
                    <flux:label>{{ __('patient.missed.refund_modal.destination_label') }}</flux:label>
                    <div class="doctor-emerald-pill-radios">
                        <flux:radio.group variant="pills" wire:model.live="refundDestination" class="w-full">
                            <flux:radio value="wallet" :label="__('patient.missed.refund_modal.destination_wallet')" />
                            <flux:radio
                                value="payment_account"
                                :label="__('patient.missed.refund_modal.destination_account')"
                                :disabled="! $this->canSelectPaymentAccountRefund()"
                            />
                        </flux:radio.group>
                    </div>
                    <flux:error name="refundDestination" />
                    <flux:text class="mt-1 text-xs text-zinc-500">
                        @if ($refundDestination === 'payment_account')
                            {{ __('patient.missed.refund_modal.destination_account_hint') }}
                        @else
                            {{ __('patient.missed.refund_modal.destination_wallet_hint') }}
                        @endif
                    </flux:text>
                    @unless ($this->canSelectPaymentAccountRefund())
                        <flux:text class="mt-1 text-xs text-amber-700">
                            {{ __('patient.missed.refund_modal.destination_account_unavailable') }}
                        </flux:text>
                    @endunless
                </flux:field>

                <flux:select
                    wire:model.live="refundReason"
                    :label="__('patient.missed.refund_reason_label')"
                    :placeholder="__('patient.missed.refund_reason_placeholder')"
                >
                    @foreach ($this->refundReasonOptions() as $reasonKey => $reasonLabel)
                        <option value="{{ $reasonKey }}">{{ $reasonLabel }}</option>
                    @endforeach
                </flux:select>

                @if ($refundReason === 'other')
                    <flux:textarea
                        wire:model.live="refundReasonNote"
                        :label="__('patient.missed.reason_note_label')"
                        :placeholder="__('patient.missed.reason_note_placeholder')"
                        rows="3"
                        required
                    />
                @endif
            </div>

            <div class="mt-5 rounded-xl border border-zinc-200/90 bg-zinc-50 px-4 py-3 text-sm">
                <p class="font-semibold text-zinc-900">{{ $appointment->doctor?->displayName() }}</p>
                @if ((float) $appointment->total > 0)
                    <p class="mt-2 text-xs font-medium text-[#047857]">
                        {{ __('patient.missed.refund_modal.refund_note', ['amount' => number_format((float) $appointment->total, 2)]) }}
                    </p>
                @endif
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <flux:button type="button" variant="ghost" class="w-full sm:w-auto" wire:click="dismissRefundMissedModal">
                    {{ __('patient.missed.refund_modal.dismiss') }}
                </flux:button>
                <flux:button
                    type="button"
                    class="w-full !bg-[#10B981] !text-white hover:!brightness-95 sm:w-auto"
                    wire:click="confirmRefundMissed"
                    wire:loading.attr="disabled"
                    wire:target="confirmRefundMissed"
                >
                    <span wire:loading.remove wire:target="confirmRefundMissed">
                        {{ __('patient.missed.refund_modal.confirm') }}
                    </span>
                    <span wire:loading wire:target="confirmRefundMissed">
                        {{ __('patient.missed.refund_modal.confirming') }}
                    </span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
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

            function isPatientConsultationMobile() {
                return window.matchMedia('(max-width: 639px)').matches;
            }

            function chatPanelEl() {
                return document.getElementById(isPatientConsultationMobile() ? 'patient-chat-panel-mobile' : 'patient-chat-panel');
            }

            function chatMessagesEl() {
                return document.getElementById(isPatientConsultationMobile() ? 'patient-chat-messages-mobile' : 'patient-chat-messages');
            }

            function agoraRemotePlayerId() {
                return isPatientConsultationMobile() ? 'patient-agora-remote-mobile' : 'patient-agora-remote';
            }

            function agoraLocalPlayerId() {
                return isPatientConsultationMobile() ? 'patient-agora-local-mobile' : 'patient-agora-local';
            }

            function micBtnId() {
                return isPatientConsultationMobile() ? 'patient-agora-toggle-mic-mobile' : 'patient-agora-toggle-mic';
            }

            function videoBtnId() {
                return isPatientConsultationMobile() ? 'patient-agora-toggle-video-mobile' : 'patient-agora-toggle-video';
            }

            function callUiEls() {
                if (isPatientConsultationMobile()) {
                    return {
                        chip: document.getElementById('patient-call-started-chip'),
                        waiting: document.getElementById('patient-waiting-for-call-chip'),
                        label: document.getElementById('patient-call-chip-label'),
                        duration: document.getElementById('patient-call-chip-duration'),
                    };
                }

                return {
                    chip: document.getElementById('patient-call-started-chip-desktop'),
                    waiting: document.getElementById('patient-waiting-for-call-chip-desktop'),
                    label: document.getElementById('patient-call-chip-label-desktop'),
                    duration: document.getElementById('patient-call-chip-duration-desktop'),
                };
            }

            function incomingBannerEl() {
                return document.getElementById(isPatientConsultationMobile() ? 'incoming-call-banner' : 'incoming-call-banner-desktop');
            }

            function incomingLabelEl() {
                return document.getElementById(isPatientConsultationMobile() ? 'incoming-call-label' : 'incoming-call-label-desktop');
            }

            const panel = chatPanelEl();
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
                boot.__syncCallOverlay?.();
                boot.__observeSessionMetrics?.();
                boot.__syncSessionFromDom?.();
                startSessionTimers();
                boot.__restoreAndRejoinCall?.().catch(() => {});

                return;
            }

            window.__patientConversationInitLock = true;

            teardownPatientConversationRealtime();

            if (boot.dataset.initialized === '1') {
                // Soft re-init / appointment switch must not broadcast call.ended.
                boot.__leaveCall?.(false).catch(() => {});
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
            const seen = new Set();

            function metricsEl() {
                return document.getElementById('patient-conversation-metrics');
            }

            let sessionTimerId = null;
            let sessionEndedDisconnectHandled = false;
            let sessionFinishedUiShown = false;

            document.querySelectorAll('#patient-chat-messages [wire\\:key^="patient-chat-"], #patient-chat-messages-mobile [wire\\:key^="patient-chat-mobile-"]').forEach((el) => {
                const key = el.getAttribute('wire:key') || '';
                const id = key.replace('patient-chat-mobile-', '').replace('patient-chat-', '');
                if (id) seen.add(String(id));
            });

            function patientMessageListEls() {
                return [
                    document.getElementById('patient-chat-messages-mobile'),
                    document.getElementById('patient-chat-messages'),
                ].filter(Boolean);
            }

            function appendMessageIntoPatientWrap(wrap, payload) {
                const emptyState = wrap.querySelector('.flex.min-h-\\[18rem\\], .patient-consultation-chat-empty');
                if (emptyState) {
                    emptyState.remove();
                }

                const row = document.createElement('div');
                const mine = payload.send_by === 'patient';
                const mobileChat = wrap.id === 'patient-chat-messages-mobile';

                if (mobileChat) {
                    row.className = mine ? 'flex flex-row-reverse gap-2' : 'flex gap-2';

                    const stack = document.createElement('div');
                    stack.className = 'min-w-0 max-w-[78%]'.concat(mine ? ' text-end' : '');

                    const bubble = document.createElement('div');
                    bubble.className = mine
                        ? 'inline-block rounded-2xl rounded-br-md bg-[#10B981] px-3.5 py-2 text-sm text-white shadow-sm'
                        : 'inline-block rounded-2xl rounded-bl-md border border-slate-100 bg-slate-50 px-3.5 py-2 text-sm text-slate-800 shadow-sm';

                    const p = document.createElement('p');
                    p.className = 'whitespace-pre-wrap break-words text-start';
                    p.textContent = payload.body || '';
                    bubble.appendChild(p);
                    stack.appendChild(bubble);

                    if (payload.created_at) {
                        const time = document.createElement('time');
                        time.className = 'mt-1 block text-[0.625rem] text-slate-400'.concat(mine ? ' text-end' : '');
                        const d = new Date(payload.created_at);
                        time.textContent = d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
                        stack.appendChild(time);
                    }

                    row.appendChild(stack);
                } else {
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
                }

                wrap.appendChild(row);
                wrap.scrollTop = wrap.scrollHeight;
            }

            function appendMessageRow(payload, { fromSelf = false } = {}) {
                if (! payload?.id || seen.has(String(payload.id))) {
                    return;
                }

                seen.add(String(payload.id));

                const lists = patientMessageListEls();
                if (lists.length === 0) {
                    return;
                }

                lists.forEach((wrap) => appendMessageIntoPatientWrap(wrap, payload));

                if (! fromSelf && payload.send_by !== 'patient') {
                    window.dispatchEvent(new CustomEvent('patient-chat-message-received'));
                }
            }

            boot.__appendChatMessage = (payload, options = {}) => appendMessageRow(payload, options);

            let incomingPayload = null;

            let agoraClient = null;
            let localAudio = null;
            let localVideo = null;
            let activeMode = null;
            let callJoinInProgress = false;

            const overlay = document.getElementById('patient-agora-overlay');

            function overlayTitleEl() {
                return document.getElementById('patient-agora-title');
            }

            function overlayDurationEl() {
                return document.getElementById('patient-overlay-call-duration');
            }

            function callChipDurationEl() {
                return callUiEls().duration;
            }

            const labelVideo = @js(__('patient.appointments.video_call'));
            const labelVoice = @js(__('patient.appointments.voice_call'));
            const labelConnecting = metricsEl()?.dataset.labelConnecting || 'Connecting…';
            const labelCallFailed = metricsEl()?.dataset.labelCallFailed || 'Could not join the call.';
            const labelNoActiveCall = metricsEl()?.dataset.labelNoActiveCall || 'No active call yet.';

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

            function readActiveStoredCallPayload() {
                try {
                    const activeRaw = sessionStorage.getItem('mashora_active_call_' + appointmentId);

                    if (! activeRaw) {
                        return null;
                    }

                    const data = JSON.parse(activeRaw);

                    return data?.agora_app_id && data?.agora_channel ? data : null;
                } catch (_) {
                    return null;
                }
            }

            function readPendingStoredCallPayload() {
                try {
                    const pendingRaw = sessionStorage.getItem('mashora_pending_call_' + appointmentId);

                    if (! pendingRaw) {
                        return null;
                    }

                    const data = JSON.parse(pendingRaw);

                    return data?.agora_app_id && data?.agora_channel ? data : null;
                } catch (_) {
                    return null;
                }
            }

            function readStoredCallPayload() {
                return readActiveStoredCallPayload() || readPendingStoredCallPayload();
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
                boot.__activeMode = null;

                const remotePlayer = document.getElementById(agoraRemotePlayerId());
                const localPlayer = document.getElementById(agoraLocalPlayerId());
                if (remotePlayer) {
                    remotePlayer.innerHTML = '';
                }

                if (localPlayer) {
                    localPlayer.innerHTML = '';
                }
            }

            function markCallConnected(mode) {
                activeMode = mode;
                boot.__activeMode = mode;
                incomingPayload = null;

                if (!callStartedAt) {
                    startCallTimer(mode);
                } else {
                    updateActiveCallOverlayUi();
                }

                showOverlay(true);
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

                document.getElementById(videoBtnId())?.classList.toggle('hidden', mode !== 'video');
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

            function isSessionFinished() {
                if (sessionFinishedUiShown) {
                    return true;
                }

                if (metricsEl()?.dataset.relaxedSessionLimits === '1') {
                    return false;
                }

                const endIso = metricsEl()?.dataset.sessionEnd || '';
                if (! endIso) {
                    return false;
                }

                return new Date(endIso).getTime() <= Date.now();
            }

            function setChipVisible(el, visible, displayClass = 'inline-flex') {
                if (! el) {
                    return;
                }

                el.classList.toggle('hidden', ! visible);
                el.classList.toggle(displayClass, visible);
            }

            function refreshCallUiState() {
                const sessionFinished = isSessionFinished();
                const sessionActive = callEnabled && appointmentStatus === 'in_process' && ! sessionFinished;
                const ui = callUiEls();
                const incomingBanner = incomingBannerEl();
                const hasIncomingCall = Boolean(incomingPayload)
                    || (incomingBanner && !incomingBanner.classList.contains('hidden'));
                const showWaiting = sessionActive
                    && ! activeMode
                    && ! hasIncomingCall
                    && ! sessionFinished;
                const showCallChip = Boolean(activeMode) && ! sessionFinished;

                setChipVisible(ui.waiting, showWaiting);
                setChipVisible(ui.chip, showCallChip);

                setChipVisible(
                    document.getElementById('patient-call-started-chip-desktop'),
                    showCallChip,
                );
                setChipVisible(
                    document.getElementById('patient-waiting-for-call-chip-desktop'),
                    showWaiting,
                );

                // Always keep both mobile/desktop call chips consistent.
                setChipVisible(document.getElementById('patient-call-started-chip'), showCallChip);
                setChipVisible(document.getElementById('patient-waiting-for-call-chip'), showWaiting);

                const sessionLiveBanner = document.getElementById('patient-session-live-banner');

                if (sessionLiveBanner) {
                    if (sessionFinished || !sessionActive) {
                        sessionLiveBanner.classList.add('hidden');
                    } else {
                        sessionLiveBanner.classList.remove('hidden');
                        sessionLiveBanner.classList.toggle('sm:hidden', hasIncomingCall);
                    }
                }

                if (sessionFinished) {
                    setChipVisible(document.getElementById('patient-session-finished-chip'), true, 'inline-flex');
                    setChipVisible(document.getElementById('patient-session-finished-chip-desktop'), true, 'inline-flex');
                    setChipVisible(ui.chip, false);
                    setChipVisible(document.getElementById('patient-call-started-chip'), false);
                    setChipVisible(document.getElementById('patient-call-started-chip-desktop'), false);
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

                const chipLabel = callUiEls().label;
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

                const chipLabel = callUiEls().label;
                if (chipLabel) {
                    chipLabel.textContent = @js(__('patient.appointments.call_in_progress'));
                }
            }

            function syncPatientSessionFromDom(bootEl) {
                if (boot.__syncingSessionFromDom) {
                    return;
                }

                boot.__syncingSessionFromDom = true;

                try {
                    const metricsNode = document.getElementById('patient-conversation-metrics');
                    // Metrics are Livewire-managed; bootstrap is wire:ignore so prefer metrics status.
                    const nextStatus = metricsNode?.dataset.status || bootEl?.dataset.appointmentStatus || appointmentStatus;
                    const prevStatus = appointmentStatus;
                    const wasWaiting = appointmentStatus !== 'in_process' && nextStatus === 'in_process';

                    appointmentStatus = nextStatus;

                    if (bootEl && bootEl.dataset.appointmentStatus !== appointmentStatus) {
                        bootEl.dataset.appointmentStatus = appointmentStatus;
                    }

                    // Do not write metrics.dataset.status here — Livewire owns it and MutationObserver
                    // would recurse if we mirror the value back onto the same node.

                    if (prevStatus === 'in_process' && nextStatus === 'completed') {
                        clearPendingCallStorage();
                        incomingPayload = null;
                        incomingBannerEl()?.classList.add('hidden');
                        dismissIncomingAlert();

                        if (activeMode) {
                            leaveCallLocal().catch(() => {});
                        }

                        window.dispatchEvent(new CustomEvent('mashora:call-ended', {
                            detail: { appointment_id: appointmentId, status: nextStatus },
                        }));

                        if (window.Livewire) {
                            Livewire.dispatch('patient-session-completed', { appointmentId });
                        }
                    }

                    if (wasWaiting && incomingLabelEl() && incomingBannerEl()) {
                        showCallToast(metricsNode?.dataset.sessionStartedWaiting || 'Session started.', 'success');
                        startSessionTimers();
                    }

                    refreshCallUiState();
                } finally {
                    boot.__syncingSessionFromDom = false;
                }
            }

            function tickSessionTimers() {
                const metrics = metricsEl();
                const elapsedEl = document.getElementById('patient-timer-session-elapsed');
                const remainingEl = document.getElementById(
                    isPatientConsultationMobile() ? 'patient-timer-session-remaining-mobile' : 'patient-timer-session-remaining',
                );
                if (!metrics || !elapsedEl) {
                    return;
                }

                const status = metrics.dataset.status || '';
                const startIso = metrics.dataset.sessionStart || '';
                const endIso = metrics.dataset.sessionEnd || '';
                const scheduledTime = metrics.dataset.sessionScheduledTime || '--:--';

                elapsedEl.textContent = scheduledTime;

                if (status !== 'in_process' || !startIso) {
                    if (remainingEl) {
                        remainingEl.textContent = '--:--';
                    }

                    return;
                }

                const now = Date.now();

                if (remainingEl && endIso) {
                    const end = new Date(endIso).getTime();
                    const left = (end - now) / 1000;

                    if (left <= 0) {
                        remainingEl.textContent = metrics.dataset.sessionFinished
                            || @js(__('patient.appointments.session_finished'));
                        remainingEl.classList.remove('text-amber-700', 'text-[#047857]', 'text-[#10B981]');
                        remainingEl.classList.add('text-slate-600');
                        document.getElementById('patient-session-countdown-mobile')?.classList.add('hidden');
                        maybeEndCallWhenSessionExpired(left);
                        maybeRefreshWhenSessionExpires(left);

                        return;
                    }

                    remainingEl.textContent = formatDuration(left);
                    remainingEl.classList.toggle('text-amber-700', left > 0 && left <= 300);
                    remainingEl.classList.toggle('text-rose-600', left <= 0);
                    remainingEl.classList.toggle('text-[#047857]', left > 300);
                    remainingEl.classList.toggle('text-[#10B981]', left > 300);
                    maybeEndCallWhenSessionExpired(left);
                    maybeRefreshWhenSessionExpires(left);
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
                        localTrack.play(agoraLocalPlayerId());
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
                        user.videoTrack.play(agoraRemotePlayerId());
                    } catch (error) {
                        console.error('Failed to replay remote video track', error);
                    }
                });
            }

            function playerHasPlayingVideo(playerId) {
                const el = document.getElementById(playerId);

                return Boolean(el?.querySelector('video'));
            }

            function shouldReplayVideoTracks() {
                const localTrack = boot.__localVideo || localVideo;

                if (localTrack && ! playerHasPlayingVideo(agoraLocalPlayerId())) {
                    return true;
                }

                if (! agoraClient) {
                    return false;
                }

                const hasRemoteVideo = agoraClient.remoteUsers.some((user) => Boolean(user.videoTrack));

                return hasRemoteVideo && ! playerHasPlayingVideo(agoraRemotePlayerId());
            }

            function isInlineCallLive() {
                if (isPatientConsultationMobile()) {
                    return document
                        .getElementById('patient-consultation-inline-video')
                        ?.classList
                        .contains('patient-consultation-inline-video--live') === true;
                }

                return Boolean(overlay && ! overlay.classList.contains('hidden'));
            }

            function setMobileCallControlsVisible(visible) {
                const controlsWrap = document.getElementById('patient-consultation-call-controls-wrap');

                if (! controlsWrap) {
                    return;
                }

                controlsWrap.classList.toggle('hidden', ! visible);
                controlsWrap.classList.toggle('flex', visible);
            }

            function showOverlay(show, { forceReplay = true } = {}) {
                const inline = document.getElementById('patient-consultation-inline-video');
                const idle = document.getElementById('patient-consultation-video-idle');
                const leaveMobile = document.getElementById('patient-agora-leave-mobile');

                if (isPatientConsultationMobile()) {
                    const wasLive = inline?.classList.contains('patient-consultation-inline-video--live') === true;

                    overlay?.classList.add('hidden');
                    overlay?.setAttribute('aria-hidden', 'true');
                    inline?.classList.toggle('patient-consultation-inline-video--live', show);
                    idle?.classList.toggle('hidden', show);
                    setMobileCallControlsVisible(show);
                    leaveMobile?.classList.toggle('hidden', !show);
                    leaveMobile?.classList.toggle('inline-flex', show);

                    const chatToggle = document.getElementById('patient-agora-toggle-chat-mobile');
                    chatToggle?.classList.toggle('hidden', !show);
                    chatToggle?.classList.toggle('inline-flex', show);

                    if (show !== wasLive) {
                        window.dispatchEvent(new CustomEvent(show ? 'patient-consultation-call-active' : 'patient-consultation-call-ended'));
                    }

                    document.querySelectorAll('#patient-consultation-inline-video .doctor-consultation-call-controls__btn:not(.hidden)').forEach((btn) => {
                        btn.classList.add('inline-flex');
                    });

                    if (show && (forceReplay || shouldReplayVideoTracks())) {
                        window.requestAnimationFrame(() => {
                            window.requestAnimationFrame(() => {
                                replayActiveVideoTracks();
                            });
                        });
                    }

                    return;
                }

                if (!overlay) {
                    return;
                }

                const wasVisible = ! overlay.classList.contains('hidden');

                overlay.classList.toggle('hidden', !show);
                overlay.setAttribute('aria-hidden', show ? 'false' : 'true');

                if (show && (forceReplay || shouldReplayVideoTracks() || ! wasVisible)) {
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
                    micBtnId: micBtnId(),
                    videoBtnId: videoBtnId(),
                    localPreviewId: agoraLocalPlayerId(),
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
                    micBtnId(),
                    videoBtnId(),
                    mode,
                );
            }

            async function leaveCallLocal({ clearStoredCall = true } = {}) {
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
                boot.__activeMode = null;
                incomingPayload = null;
                const remoteWrap = document.getElementById(agoraRemotePlayerId());
                const localWrap = document.getElementById(agoraLocalPlayerId());
                if (remoteWrap) {
                    remoteWrap.innerHTML = '';
                }
                if (localWrap) {
                    localWrap.innerHTML = '';
                }
                showOverlay(false);
                syncMediaControlUi(null);
                document.getElementById(micBtnId())?.classList.add('hidden');
                document.getElementById(videoBtnId())?.classList.add('hidden');

                if (clearStoredCall) {
                    clearPendingCallStorage();
                }

                refreshCallUiState();
                window.dispatchEvent(new CustomEvent('patient-consultation-call-ended'));
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
                incomingBannerEl()?.classList.add('hidden');
                await leaveCallLocal({ clearStoredCall: notifyRemote });

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

                const nextStatus = data?.status || appointmentStatus;
                clearPendingCallStorage();
                incomingPayload = null;
                incomingBannerEl()?.classList.add('hidden');
                dismissIncomingAlert();
                window.MashoraRealtimeAlerts?.stopIncomingRing();

                if (activeMode) {
                    await leaveCallLocal();
                }

                if (nextStatus && nextStatus !== appointmentStatus) {
                    appointmentStatus = nextStatus;
                    boot.dataset.appointmentStatus = nextStatus;
                    const metrics = metricsEl();
                    if (metrics) {
                        metrics.dataset.status = nextStatus;
                    }
                }

                refreshCallUiState();

                if (nextStatus === 'completed' && window.Livewire) {
                    Livewire.dispatch('patient-session-completed', { appointmentId });
                }

                window.dispatchEvent(new CustomEvent('mashora:call-ended', {
                    detail: { appointment_id: appointmentId, status: nextStatus },
                }));
            }

            function registerRemoteUserHandlers(client, mode) {
                client.on('user-published', async (user, mediaType) => {
                    try {
                        await client.subscribe(user, mediaType);
                        if (mediaType === 'video') {
                            user.videoTrack?.play(agoraRemotePlayerId());
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
                    // Keep last frame / container when tracks briefly drop during patient refresh.
                    if (mediaType === 'video' && ! user.hasVideo) {
                        // Intentionally do not clear remote player DOM here.
                    }
                });

                client.on('user-left', () => {
                    // Patient refresh leaves and rejoins — do not destroy the remote container.
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
                            user.videoTrack?.play(agoraRemotePlayerId());
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

            function showSessionFinishedUi() {
                sessionFinishedUiShown = true;

                const finishedLabel = metricsEl()?.dataset.sessionFinished
                    || @js(__('patient.appointments.session_finished'));

                document.getElementById('patient-consultation-inline-video')?.classList.add('hidden');
                document.getElementById('patient-consultation-inline-video')?.setAttribute('aria-hidden', 'true');

                const ui = callUiEls();
                setChipVisible(ui.chip, false);
                setChipVisible(ui.waiting, false);
                setChipVisible(document.getElementById('patient-call-started-chip'), false);
                setChipVisible(document.getElementById('patient-call-started-chip-desktop'), false);
                setChipVisible(document.getElementById('patient-waiting-for-call-chip'), false);
                setChipVisible(document.getElementById('patient-waiting-for-call-chip-desktop'), false);
                document.getElementById('patient-session-live-banner')?.classList.add('hidden');
                document.getElementById('patient-live-now-badge')?.classList.add('hidden');
                document.getElementById('patient-chat-live-now-badge')?.classList.add('hidden');
                document.getElementById('patient-session-countdown-mobile')?.classList.add('hidden');
                document.getElementById('incoming-call-banner')?.classList.add('hidden');
                document.getElementById('incoming-call-banner-desktop')?.classList.add('hidden');

                const remainingMobile = document.getElementById('patient-timer-session-remaining-mobile');
                const remainingDesktop = document.getElementById('patient-timer-session-remaining');
                if (remainingMobile) {
                    remainingMobile.textContent = finishedLabel;
                }
                if (remainingDesktop) {
                    remainingDesktop.textContent = finishedLabel;
                }

                setChipVisible(document.getElementById('patient-session-finished-chip'), true);
                setChipVisible(document.getElementById('patient-session-finished-chip-desktop'), true);

                const statusLabel = document.getElementById('patient-conversation-status-label');
                if (statusLabel) {
                    statusLabel.textContent = finishedLabel;
                }

                const chipLabel = callUiEls().label;
                if (chipLabel) {
                    chipLabel.textContent = finishedLabel;
                }

                const overlayTitle = overlayTitleEl();
                if (overlayTitle) {
                    overlayTitle.textContent = finishedLabel;
                }

                showOverlay(false);
                refreshCallUiState();
                window.dispatchEvent(new CustomEvent('patient-consultation-call-ended'));
            }

            function syncSessionExpiryFromDom() {
                if (metricsEl()?.dataset.relaxedSessionLimits === '1') {
                    return;
                }

                const endIso = metricsEl()?.dataset.sessionEnd || '';
                if (! endIso) {
                    return;
                }

                if (new Date(endIso).getTime() <= Date.now()) {
                    showSessionFinishedUi();
                }
            }

            function maybeEndCallWhenSessionExpired(leftSeconds) {
                if (metricsEl()?.dataset.relaxedSessionLimits === '1') {
                    return;
                }

                if (leftSeconds > 0) {
                    sessionEndedDisconnectHandled = false;
                    sessionFinishedUiShown = false;

                    return;
                }

                showSessionFinishedUi();

                if (!activeMode || sessionEndedDisconnectHandled) {
                    return;
                }

                sessionEndedDisconnectHandled = true;
                const endedMessage = metricsEl()?.dataset.sessionEnded || 'Session time has ended.';

                leaveCall()
                    .then(() => {
                        showSessionFinishedUi();
                        if (window.Flux?.toast) {
                            window.Flux.toast({ text: endedMessage, variant: 'warning' });
                        }
                    })
                    .catch(() => {});
            }

            let sessionExpiredRefreshHandled = false;

            function maybeRefreshWhenSessionExpires(leftSeconds) {
                if (metricsEl()?.dataset.relaxedSessionLimits === '1') {
                    return;
                }

                if (leftSeconds > 0) {
                    sessionExpiredRefreshHandled = false;

                    return;
                }

                if (sessionExpiredRefreshHandled) {
                    return;
                }

                sessionExpiredRefreshHandled = true;

                if (window.Livewire) {
                    const componentEl = document.querySelector('[wire\\:id]');
                    const wireId = componentEl?.getAttribute('wire:id');
                    if (wireId) {
                        window.Livewire.find(wireId)?.$refresh();
                    }
                }
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

                const liveBanner = document.getElementById('patient-session-live-banner');
                if (liveBanner) {
                    liveBanner.classList.remove('hidden');
                    liveBanner.classList.add('max-sm:hidden', 'sm:block');
                }
                document.getElementById('patient-chat-locked-callout')?.classList.add('hidden');

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
                const incomingBanner = incomingBannerEl();
                const incomingLabel = incomingLabelEl();

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
                    sessionStorage.removeItem('mashora_active_call_' + appointmentId);
                } catch (_) {
                    // ignore storage errors
                }
            }

            function persistActiveCall(data, mode = null) {
                if (! data?.agora_app_id || ! data?.agora_channel) {
                    return;
                }

                try {
                    sessionStorage.setItem(
                        'mashora_active_call_' + appointmentId,
                        JSON.stringify({
                            appointment_id: appointmentId,
                            call_type: mode === 'audio'
                                ? 'audio'
                                : (data.call_type || mode || 'video'),
                            agora_app_id: data.agora_app_id,
                            agora_token: data.agora_token || null,
                            agora_channel: data.agora_channel,
                        }),
                    );
                    sessionStorage.setItem(
                        'mashora_pending_call_' + appointmentId,
                        JSON.stringify({
                            appointment_id: appointmentId,
                            call_type: mode === 'audio'
                                ? 'audio'
                                : (data.call_type || mode || 'video'),
                            agora_app_id: data.agora_app_id,
                            agora_token: data.agora_token || null,
                            agora_channel: data.agora_channel,
                        }),
                    );
                } catch (_) {
                    // ignore storage errors
                }
            }

            function restorePendingCallFromStorage() {
                const data = readStoredCallPayload();
                if (! data) {
                    return false;
                }

                showIncomingCallBanner(data, { silent: true });

                return true;
            }

            async function restorePendingCallFromServer() {
                if (! pendingCallUrl) {
                    return false;
                }

                try {
                    const res = await fetch(pendingCallUrl, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (! res.ok) {
                        return false;
                    }

                    const data = await res.json();
                    if (! data?.pending || ! data?.agora_app_id) {
                        return false;
                    }

                    showIncomingCallBanner(data, { silent: true });

                    return true;
                } catch (_) {
                    return false;
                }
            }

            async function restoreAndRejoinCall(attempt = 0) {
                syncPatientSessionFromDom(boot);

                if (appointmentStatus !== 'in_process' || isSessionFinished()) {
                    clearPendingCallStorage();
                    activeMode = null;
                    boot.__activeMode = null;
                    refreshCallUiState();

                    return;
                }

                if (activeMode || callJoinInProgress) {
                    return;
                }

                const activeStored = readActiveStoredCallPayload();

                if (! activeStored) {
                    restorePendingCallFromStorage();
                    await restorePendingCallFromServer();
                    refreshCallUiState();

                    return;
                }

                await restorePendingCallFromServer();
                const payload = incomingPayload?.agora_app_id ? incomingPayload : activeStored;
                const mode = resolveEffectiveCallMode(
                    payload?.call_type === 'audio' ? 'audio' : 'video',
                    payload,
                    null,
                );

                try {
                    await joinCall(mode, payload);
                } catch (error) {
                    console.error('Failed to restore active call', error);
                }

                if (! activeMode && attempt < 2) {
                    window.setTimeout(() => {
                        restoreAndRejoinCall(attempt + 1).catch(() => {});
                    }, 1200 * (attempt + 1));
                }
            }

            function restoreIncomingCallBanner() {
                if (activeMode || callJoinInProgress || appointmentStatus !== 'in_process' || isSessionFinished()) {
                    return;
                }

                if (incomingPayload?.agora_app_id) {
                    incomingBannerEl()?.classList.remove('hidden');
                    refreshCallUiState();

                    return;
                }

                restorePendingCallFromStorage();
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
                incomingBannerEl()?.classList.add('hidden');
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

                    persistActiveCall({
                        ...cfg,
                        call_type: effectiveMode === 'audio' ? 'audio' : (payload?.call_type || 'video'),
                    }, effectiveMode);

                    const client = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });
                    agoraClient = client;
                    registerRemoteUserHandlers(client, effectiveMode);

                    await client.join(cfg.agora_app_id, cfg.agora_channel, cfg.agora_token || null, null);
                    assignLocalTracks(micTrack, camTrack);

                    if (camTrack) {
                        camTrack.play(agoraLocalPlayerId());
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

                    if (incomingPayload?.agora_app_id && ! activeMode) {
                        incomingBannerEl()?.classList.remove('hidden');
                    }

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
                    const stored = readStoredCallPayload();
                    if (stored) {
                        incomingPayload = stored;
                    }
                }

                if (!incomingPayload?.agora_app_id) {
                    showCallToast(labelNoActiveCall, 'warning');
                    dismissIncomingAlert();
                    incomingBannerEl()?.classList.add('hidden');
                    refreshCallUiState();

                    return;
                }

                const mode = resolveEffectiveCallMode(
                    incomingPayload.call_type === 'audio' ? 'audio' : 'video',
                    incomingPayload,
                    null,
                );
                joinCall(mode, incomingPayload).catch((error) => console.error(error));
            };
            boot.__dismissIncoming = () => {
                dismissIncomingAlert();
                incomingPayload = null;
                clearPendingCallStorage();
                incomingBannerEl()?.classList.add('hidden');
                refreshCallUiState();
            };

            function bindCallControlButtons() {
                // Join and overlay controls use document-level delegation to prevent duplicate Agora joins.
            }

            bindCallControlButtons();
            boot.__bindCallControlButtons = bindCallControlButtons;
            boot.__restorePendingCall = restorePendingCallFromStorage;
            boot.__restoreAndRejoinCall = restoreAndRejoinCall;
            boot.__restoreIncomingCallBanner = restoreIncomingCallBanner;

            if (appointmentStatus === 'in_process') {
                restoreAndRejoinCall().catch(() => {});
            } else {
                clearPendingCallStorage();
                incomingPayload = null;
                incomingBannerEl()?.classList.add('hidden');
            }

            if (!window.__patientConversationClickBound) {
                window.__patientConversationClickBound = true;

                document.addEventListener('click', (event) => {
                    const bootEl = document.getElementById('patient-conversation-bootstrap');
                    if (!bootEl) {
                        return;
                    }

                    if (event.target.closest('#incoming-call-accept, #incoming-call-accept-desktop')) {
                        event.preventDefault();
                        bootEl.__acceptIncoming?.();
                    }

                    if (event.target.closest('#incoming-call-dismiss, #incoming-call-dismiss-desktop')) {
                        event.preventDefault();
                        bootEl.__dismissIncoming?.();
                    }

                    if (event.target.closest('#patient-agora-leave, #patient-agora-leave-mobile, [data-video-call-leave="patient-agora-leave"]')) {
                        event.preventDefault();
                        bootEl.__leaveCall?.().catch((error) => console.error(error));
                    }

                    if (event.target.closest('#patient-agora-toggle-mic, #patient-agora-toggle-mic-mobile')) {
                        event.preventDefault();
                        bootEl.__toggleMic?.();
                    }

                    if (event.target.closest('#patient-agora-toggle-video, #patient-agora-toggle-video-mobile')) {
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

                window.addEventListener('mashora:chat-message-sent', (event) => {
                    const detail = event.detail || {};
                    if (detail.send_by !== 'patient') {
                        return;
                    }

                    const bootEl = document.getElementById('patient-conversation-bootstrap');
                    bootEl?.__appendChatMessage?.(detail, { fromSelf: Boolean(detail.self) });
                });

                // Persist call creds across hard refreshes; never end the remote call automatically.
                window.addEventListener('pagehide', () => {
                    const bootEl = document.getElementById('patient-conversation-bootstrap');
                    const mode = bootEl?.__activeMode;
                    if (! mode) {
                        return;
                    }

                    // Soft leave locally if possible, keep sessionStorage for rejoin.
                    bootEl.__leaveCall?.(false).catch(() => {});
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
                        // Soft teardown for Livewire navigation — keep storage so page can rejoin.
                        boot.__leaveCall?.(false).catch(() => {});
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
                const mode = activeMode || boot.__activeMode;
                if (! mode) {
                    return;
                }

                activeMode = mode;
                boot.__activeMode = mode;

                // Soft sync on Livewire poll/morph: keep the call UI without restarting Agora video.
                const forceReplay = ! isInlineCallLive() || shouldReplayVideoTracks();
                showOverlay(true, { forceReplay });
                showMediaControlsForMode(mode);
                syncMediaControlUi(mode);
                updateActiveCallOverlayUi();
            };

            function registerPatientConversationMorphHook() {
                if (window.__patientConversationMorphHook) {
                    return;
                }

                window.__patientConversationMorphHook = true;

                const registerHook = () => {
                    Livewire.hook('morph.updated', ({ el }) => {
                        const bootEl = document.getElementById('patient-conversation-bootstrap');
                        if (! bootEl || ! bootEl.__activeMode) {
                            return;
                        }

                        if (
                            el?.id === 'patient-consultation-inline-video'
                            || el?.closest?.('#patient-consultation-inline-video')
                            || el?.querySelector?.('#patient-consultation-inline-video, #patient-agora-toggle-mic-mobile, #patient-agora-leave')
                        ) {
                            bootEl.__syncCallOverlay?.();
                        }
                    });

                    Livewire.hook('commit', ({ succeed }) => {
                        succeed(() => {
                            const bootEl = document.getElementById('patient-conversation-bootstrap');
                            bootEl?.__bindCallControlButtons?.();
                            bootEl?.__mountOverlayToBody?.();
                            bootEl?.__syncCallOverlay?.();
                            bootEl?.__observeSessionMetrics?.();
                            bootEl?.__syncSessionFromDom?.();
                            bootEl?.__syncSessionExpiryFromDom?.();
                            bootEl?.__restoreIncomingCallBanner?.();
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
                    syncSessionExpiryFromDom();
                    startSessionTimers();
                });

                sessionObserver.observe(metrics, {
                    attributes: true,
                    attributeFilter: ['data-status', 'data-session-start', 'data-session-end'],
                });

                boot.__sessionMetricsObserver = sessionObserver;
            }

            boot.__observeSessionMetrics = observeSessionMetrics;
            boot.__syncSessionFromDom = () => syncPatientSessionFromDom(boot);
            boot.__syncSessionExpiryFromDom = () => syncSessionExpiryFromDom();

            registerPatientConversationMorphHook();

            startSessionTimers();
            syncPatientSessionFromDom(boot);
            syncSessionExpiryFromDom();

            if (boot.dataset.sessionObserved !== '1') {
                boot.dataset.sessionObserved = '1';
                const sessionObserver = new MutationObserver(() => {
                    syncPatientSessionFromDom(boot);
                    syncSessionExpiryFromDom();
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
