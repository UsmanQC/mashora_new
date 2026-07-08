<?php

use App\Events\AppointmentChatMessageSent;
use App\Livewire\Concerns\CompletesDoctorAppointment;
use App\Models\Appointment;
use App\Models\ChMessage;
use App\Models\Diagnosis;
use App\Services\AppointmentSessionService;
use App\Support\DoctorAgoraChannel;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Conversation')] class extends Component
{
    use CompletesDoctorAppointment;

    public Appointment $appointment;

    /**
     * @var list<array{id: string, body: string|null, send_by: string, created_at: string|null}>
     */
    public array $messages = [];

    public string $draft = '';

    public string $agoraAppId = '';

    public string $agoraToken = '';

    public string $agoraChannel = '';

    public function mount(): void
    {
        $this->appointment->loadMissing(['user', 'diagnosis', 'medications']);

        $this->refreshAgoraCredentials();
        $this->loadMessages();
    }

    public function patientInitials(): string
    {
        return Str::of($this->appointment->patient_name)
            ->explode(' ')
            ->take(2)
            ->map(fn (string $word): string => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function patientAgeLabel(): ?string
    {
        $birthDate = $this->appointment->user?->birth_date;

        if ($birthDate === null) {
            return null;
        }

        return $birthDate->age.__('doctor.consultation.age_suffix');
    }

    public function patientGenderLabel(): ?string
    {
        $gender = strtolower((string) ($this->appointment->user?->gender ?? ''));

        if ($gender === '') {
            return null;
        }

        $key = 'doctor.register.gender_'.$gender;
        $label = __($key);

        return $label !== $key ? $label : ucfirst($gender);
    }

    public function patientMetaLine(): string
    {
        $parts = array_filter([
            filled($this->appointment->appointment_number)
                ? __('doctor.consultation.mrn', ['number' => $this->appointment->appointment_number])
                : null,
            $this->patientAgeLabel(),
            $this->patientGenderLabel(),
        ]);

        return $parts !== [] ? implode(' · ', $parts) : '—';
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

    /**
     * @return EloquentCollection<int, Appointment>
     */
    #[Computed]
    public function patientMedicalHistories(): EloquentCollection
    {
        if ($this->appointment->user_id === null) {
            return new EloquentCollection;
        }

        return Appointment::query()
            ->with(['diagnosis', 'medications'])
            ->withCount('medications')
            ->where('user_id', $this->appointment->user_id)
            ->where('id', '!=', $this->appointment->id)
            ->where('status', 'completed')
            ->orderByDesc('scheduled_at')
            ->limit(6)
            ->get();
    }

    #[Computed]
    public function priorVisitSummary(): ?string
    {
        if ($this->appointment->user_id === null) {
            return null;
        }

        $prior = Appointment::query()
            ->with('diagnosis')
            ->where('user_id', $this->appointment->user_id)
            ->where('id', '!=', $this->appointment->id)
            ->where('status', 'completed')
            ->whereHas('diagnosis')
            ->orderByDesc('scheduled_at')
            ->first();

        $diagnosis = $prior?->diagnosis;

        if (! $diagnosis instanceof Diagnosis) {
            return null;
        }

        return collect([
            $diagnosis->diagnosis_name,
            $diagnosis->medical_history,
            $diagnosis->treatment_plan,
        ])->filter(fn (?string $value): bool => filled($value))->first();
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function medicationLabels(): array
    {
        $labels = $this->appointment->medications
            ->map(fn ($medication): string => trim(collect([$medication->name, $medication->dosage])->filter()->implode(' ')))
            ->filter()
            ->values()
            ->all();

        if ($labels !== []) {
            return $labels;
        }

        if ($this->appointment->user_id === null) {
            return [];
        }

        $prior = Appointment::query()
            ->with('medications')
            ->where('user_id', $this->appointment->user_id)
            ->where('id', '!=', $this->appointment->id)
            ->where('status', 'completed')
            ->whereHas('medications')
            ->orderByDesc('scheduled_at')
            ->first();

        return $prior?->medications
            ->map(fn ($medication): string => trim(collect([$medication->name, $medication->dosage])->filter()->implode(' ')))
            ->filter()
            ->values()
            ->all() ?? [];
    }

    public function startSession(AppointmentSessionService $sessions): void
    {
        $doctor = auth('doctor')->user();
        if ($doctor === null) {
            return;
        }

        $wasInProcess = (string) $this->appointment->status === 'in_process';

        if (! $sessions->start($doctor, $this->appointment)) {
            if (! $this->appointment->isSessionStartDue()
                && ! (bool) config('appointments.relaxed_session_limits', false)
                && ! $this->appointment->isSessionStartApproved()) {
                Flux::toast(variant: 'warning', text: __('doctor.conversation.session_start_wait_one_hour'));
            }

            return;
        }

        $this->appointment->refresh();
        $this->refreshAgoraCredentials();

        if ((string) $this->appointment->status === 'in_process' && ! $wasInProcess) {
            Flux::toast(variant: 'success', text: __('doctor.conversation.session_started_toast'));

            return;
        }

        if ($this->appointment->isSessionStartRequestPending()) {
            Flux::toast(variant: 'success', text: __('doctor.conversation.session_start_request_sent'));
        }
    }

    public function canStartSessionDirectly(AppointmentSessionService $sessions): bool
    {
        return $this->appointment->isSessionStartApproved()
            || $sessions->canDoctorStartWithoutPatientApproval($this->appointment);
    }

    public function canOfferSessionStart(AppointmentSessionService $sessions): bool
    {
        return $sessions->canDoctorOfferSessionStart($this->appointment);
    }

    public function canPressStartSession(AppointmentSessionService $sessions): bool
    {
        if ($this->appointment->isSessionStartRequestPending()) {
            return false;
        }

        return $this->canStartSessionDirectly($sessions)
            || $this->canOfferSessionStart($sessions);
    }

    #[On('session-start-approved')]
    public function onSessionStartApproved(int $appointmentId = 0): void
    {
        if ($appointmentId !== 0 && $appointmentId !== (int) $this->appointment->id) {
            return;
        }

        $this->appointment->refresh();
    }

    #[On('doctor-session-completed')]
    public function onDoctorSessionCompleted(int $appointmentId = 0): void
    {
        if ($appointmentId !== 0 && $appointmentId !== (int) $this->appointment->id) {
            return;
        }

        $this->appointment->refresh();
    }

    public function refreshAppointmentSessionState(): void
    {
        if (! in_array((string) $this->appointment->status, ['new', 'rescheduled', 'in_process'], true)) {
            return;
        }

        $this->appointment->refresh();
    }

    public function preSessionStatusMessage(AppointmentSessionService $sessions): string
    {
        if ((string) $this->appointment->status === 'in_process') {
            return __('doctor.consultation.waiting_for_call');
        }

        if (! in_array((string) $this->appointment->status, ['new', 'rescheduled'], true)) {
            return __('doctor.consultation.session_not_active');
        }

        if ($this->appointment->isSessionStartRequestPending()) {
            return __('doctor.conversation.waiting_for_patient_approval');
        }

        if ($this->canPressStartSession($sessions)) {
            return __('doctor.consultation.ready_to_start_hint');
        }

        if (! $this->appointment->isSessionStartDue()
            && ! (bool) config('appointments.relaxed_session_limits', false)
            && ! $this->appointment->isSessionStartApproved()) {
            return __('doctor.conversation.session_start_wait_one_hour');
        }

        return __('doctor.card.open_session_wait');
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

    public function sendMessage(): void
    {
        if (! $this->appointment->isDoctorChatOpen()) {
            return;
        }

        $this->validate([
            'draft' => ['required', 'string', 'max:5000'],
        ], [], ['draft' => __('doctor.conversation.message_field')]);

        $doctor = auth('doctor')->user();
        if ($doctor === null) {
            return;
        }

        $message = ChMessage::query()->create([
            'from_id' => $doctor->id,
            'to_id' => $this->appointment->user_id,
            'appointment_id' => $this->appointment->id,
            'body' => $this->draft,
            'send_by' => 'doctor',
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
                'send_by' => $m->send_by,
                'created_at' => $m->created_at?->toIso8601String(),
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
}; ?>

<div @if (in_array($appointment->status, ['new', 'rescheduled', 'in_process'], true)) wire:poll.5s="refreshAppointmentSessionState" @endif>
    @include('partials.doctor-luxury-consultation-mobile')

    <div
        id="conversation-page-metrics"
        class="hidden"
        data-status="{{ $appointment->status }}"
        data-session-start="{{ $appointment->actual_start_at?->toIso8601String() }}"
        data-session-end="{{ $appointment->extend_at?->toIso8601String() }}"
        data-session-scheduled-time="{{ filled($appointment->start_time) ? \Illuminate\Support\Carbon::createFromFormat('H:i:s', (string) $appointment->start_time)->format('h:i A') : '--:--' }}"
        data-session-not-started="{{ __('doctor.conversation.session_not_started') }}"
        data-label-video="{{ __('doctor.conversation.video_call') }}"
        data-label-voice="{{ __('doctor.conversation.voice_call') }}"
        data-label-live="{{ __('doctor.conversation.live') }}"
        data-label-connecting="{{ __('doctor.conversation.connecting') }}"
        data-label-call-failed="{{ __('doctor.conversation.call_failed') }}"
        data-label-call-already-active="{{ __('doctor.conversation.call_already_active') }}"
        data-label-call-controls-failed="{{ __('doctor.conversation.call_controls_failed') }}"
        data-label-patient-notify-failed="{{ __('doctor.conversation.patient_notify_failed') }}"
        data-label-camera-permission="{{ __('doctor.conversation.camera_permission_required') }}"
        data-label-agora-sdk-missing="{{ __('doctor.conversation.agora_sdk_missing') }}"
        data-session-ended="{{ __('doctor.conversation.session_time_ended') }}"
        data-relaxed-session-limits="{{ config('appointments.relaxed_session_limits') ? '1' : '0' }}"
    ></div>

    <div class="hidden space-y-5 lg:block">
    @include('partials.doctor-appointment-workspace-header', ['appointment' => $appointment, 'active' => 'conversation'])

    <div class="relative overflow-hidden rounded-3xl border border-zinc-200/90 bg-white shadow-[0_20px_55px_-32px_rgba(15,23,42,0.35)] ring-1 ring-zinc-100">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-[#2f49ca] via-[#10B981] to-[#6f86ff] opacity-85"></div>
        <div class="grid min-h-[36rem] grid-cols-1 lg:grid-cols-12">
            <div class="flex min-h-[28rem] flex-col border-zinc-200 lg:col-span-8 lg:min-h-0 lg:border-e">
                {{-- Session workspace header --}}
                <div class="shrink-0 border-b border-zinc-200/90 bg-gradient-to-br from-[#f6f9ff] via-white to-[#f8fbff] px-4 py-4 sm:px-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                            <div class="relative shrink-0">
                                <div class="rounded-2xl bg-white p-0.5 shadow-md shadow-zinc-200/60 ring-1 ring-zinc-100">
                                    <flux:avatar :name="$appointment->patient_name" circle size="lg" />
                                </div>
                                @if ($appointment->status === 'in_process')
                                    <span class="absolute -bottom-0.5 -end-0.5 flex size-3.5 items-center justify-center rounded-full border-2 border-white bg-emerald-500 shadow-sm" title="{{ __('doctor.conversation.status_in_process') }}">
                                        <span class="sr-only">{{ __('doctor.conversation.status_in_process') }}</span>
                                    </span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-lg font-semibold tracking-tight text-zinc-900">{{ $appointment->patient_name }}</p>
                                <p class="mt-0.5 text-sm text-zinc-500">{{ __('doctor.conversation.status_'.$appointment->status) }}</p>
                            </div>
                        </div>

                        {{-- Timers: session elapsed + time until extend_at (client-updated) --}}
                        @if ($appointment->status === 'in_process')
                            <div class="flex flex-wrap items-stretch gap-2 sm:gap-3">
                                <div class="flex min-w-[7.5rem] flex-1 flex-col justify-center rounded-xl border border-zinc-200/80 bg-gradient-to-br from-white to-zinc-50 px-3 py-2 shadow-sm backdrop-blur-sm sm:flex-initial sm:min-w-[8.5rem]">
                                    <p class="text-[0.65rem] font-semibold uppercase tracking-wider text-zinc-400">{{ __('doctor.conversation.session_elapsed_label') }}</p>
                                    <p id="timer-session-elapsed" class="mt-0.5 font-mono text-lg font-semibold tabular-nums text-zinc-900">00:00</p>
                                </div>
                                @if ($appointment->extend_at)
                                    <div id="wrap-session-remaining" class="flex min-w-[7.5rem] flex-1 flex-col justify-center rounded-xl border border-zinc-200/80 bg-gradient-to-br from-white to-zinc-50 px-3 py-2 shadow-sm backdrop-blur-sm sm:flex-initial sm:min-w-[8.5rem]">
                                        <p class="text-[0.65rem] font-semibold uppercase tracking-wider text-zinc-400">{{ __('doctor.conversation.session_remaining_label') }}</p>
                                        <p id="timer-session-remaining" class="mt-0.5 font-mono text-lg font-semibold tabular-nums text-[#047857]">--:--</p>
                                    </div>
                                @endif
                                <div id="call-status-chip" class="hidden min-w-0 flex-1 items-center gap-2.5 rounded-xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50 to-emerald-100/70 px-3 py-2 shadow-sm sm:flex-initial sm:min-w-[11rem]">
                                    <span class="relative flex h-2.5 w-2.5 shrink-0">
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[0.65rem] font-semibold uppercase tracking-wider text-emerald-700/90">
                                            <span id="call-status-label">{{ __('doctor.conversation.live') }}</span>
                                            · <span id="call-type-label"></span>
                                        </p>
                                        <p class="font-mono text-base font-semibold tabular-nums text-emerald-900">
                                            <span id="call-duration-display">00:00</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="flex flex-wrap items-center gap-2 xl:justify-end">
                            @if (in_array($appointment->status, ['new', 'rescheduled'], true))
                                @if ($appointment->isSessionStartRequestPending())
                                    <div>
                                        <flux:button
                                            type="button"
                                            variant="primary"
                                            icon="clock"
                                            class="min-h-10 !bg-[#047857] !text-white cursor-not-allowed opacity-70"
                                            disabled
                                        >
                                            {{ __('doctor.conversation.start_session_pending') }}
                                        </flux:button>
                                        <p class="mt-2 text-xs text-zinc-500">{{ __('doctor.conversation.waiting_for_patient_approval') }}</p>
                                    </div>
                                @elseif ($this->canPressStartSession(app(AppointmentSessionService::class)))
                                    <flux:button type="button" variant="primary" icon="play" class="min-h-10 !bg-[#047857] !text-white shadow-md shadow-[#047857]/20 hover:!brightness-95" wire:click="startSession" wire:loading.attr="disabled">
                                        {{ __('doctor.conversation.start_session') }}
                                    </flux:button>
                                @else
                                    <flux:button
                                        type="button"
                                        variant="primary"
                                        icon="play"
                                        class="min-h-10 !bg-[#047857] !text-white cursor-not-allowed opacity-50"
                                        disabled
                                        title="{{ __('doctor.conversation.session_start_wait_one_hour') }}"
                                    >
                                        {{ __('doctor.conversation.start_session') }}
                                    </flux:button>
                                    <p class="mt-2 text-xs text-zinc-500">{{ __('doctor.conversation.session_start_wait_one_hour') }}</p>
                                @endif
                            @endif
                            @if ($appointment->status === 'in_process')
                                <button
                                    type="button"
                                    id="btn-agora-video"
                                    onclick="window.mashoraDoctorStartVideoCall?.(event)"
                                    @class([
                                        'inline-flex min-h-10 items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#10B981] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-45',
                                        'border-zinc-200 bg-gradient-to-b from-white to-zinc-50 text-zinc-800 shadow-sm hover:border-[#047857]/30 hover:from-zinc-50 hover:to-zinc-100' => true,
                                    ])
                                    @disabled($agoraAppId === '')
                                    title="{{ $agoraAppId === '' ? __('doctor.conversation.agora_required') : '' }}"
                                >
                                    <flux:icon name="video-camera" variant="mini" class="size-5 text-zinc-600" />
                                    <span class="btn-label">{{ __('doctor.conversation.video') }}</span>
                                </button>
                                <button
                                    type="button"
                                    id="btn-agora-audio"
                                    onclick="window.mashoraDoctorStartAudioCall?.(event)"
                                    @class([
                                        'inline-flex min-h-10 items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#10B981] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-45',
                                        'border-zinc-200 bg-gradient-to-b from-white to-zinc-50 text-zinc-800 shadow-sm hover:border-[#047857]/30 hover:from-zinc-50 hover:to-zinc-100' => true,
                                    ])
                                    @disabled($agoraAppId === '')
                                    title="{{ $agoraAppId === '' ? __('doctor.conversation.agora_required') : '' }}"
                                >
                                    <flux:icon name="phone" variant="mini" class="size-5 text-zinc-600" />
                                    <span class="btn-label">{{ __('doctor.conversation.voice') }}</span>
                                </button>
                                <flux:button
                                    type="button"
                                    size="sm"
                                    class="min-h-10 !border-0 bg-red-600 text-white hover:bg-red-700"
                                    wire:click="requestCompleteAppointment"
                                    wire:loading.attr="disabled"
                                >
                                    {{ __('doctor.card.mark_complete') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($appointment->status === 'in_process' && $agoraAppId === '')
                    <div class="shrink-0 border-b border-amber-200/80 bg-gradient-to-r from-amber-50 to-amber-50/50 px-4 py-2.5 sm:px-5">
                        <p class="flex items-start gap-2 text-xs leading-relaxed text-amber-950">
                            <flux:icon name="exclamation-triangle" variant="mini" class="mt-0.5 size-4 shrink-0 text-amber-600" />
                            <span>{{ __('doctor.conversation.agora_configure_hint') }}</span>
                        </p>
                    </div>
                @endif

                <div x-data="{ chatMinimized: false }" class="flex min-h-0 flex-1 flex-col">
                    <div class="flex shrink-0 items-center justify-between border-b border-zinc-200/90 bg-white px-4 py-2 sm:px-5">
                        <p class="flex items-center gap-2 text-sm font-semibold text-zinc-900">
                            <flux:icon name="chat-bubble-left-right" variant="mini" class="size-4 text-zinc-500" />
                            {{ __('doctor.conversation.chat_panel_title') }}
                        </p>
                        <button
                            type="button"
                            x-on:click="chatMinimized = !chatMinimized"
                            class="flex items-center gap-1.5 rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-semibold text-zinc-600 shadow-sm transition hover:bg-zinc-50"
                            data-test="doctor-chat-minimize-toggle"
                        >
                            <flux:icon x-show="!chatMinimized" name="chevron-down" variant="mini" class="size-3.5" />
                            <flux:icon x-show="chatMinimized" x-cloak name="chevron-up" variant="mini" class="size-3.5" />
                            <span x-text="chatMinimized ? @js(__('doctor.conversation.chat_expand')) : @js(__('doctor.conversation.chat_minimize'))"></span>
                        </button>
                    </div>

                    <div x-show="chatMinimized" x-cloak class="shrink-0 px-4 py-3 text-center sm:px-5">
                        <button
                            type="button"
                            x-on:click="chatMinimized = false"
                            class="text-sm font-semibold text-[#047857] hover:underline"
                        >
                            {{ __('doctor.conversation.chat_minimized_hint') }}
                        </button>
                    </div>

                <div
                    x-show="!chatMinimized"
                    class="relative flex min-h-0 flex-1 flex-col bg-gradient-to-b from-zinc-50/90 via-zinc-50/70 to-zinc-100/80"
                    id="doctor-chat-panel"
                    data-appointment-id="{{ $appointment->id }}"
                    data-notify-url="{{ route('doctor.appointments.realtime.notify-call', $appointment) }}"
                    data-end-call-url="{{ route('doctor.appointments.realtime.end-call', $appointment) }}"
                    data-token-url="{{ route('doctor.appointments.realtime.agora-token', $appointment) }}"
                    data-csrf="{{ csrf_token() }}"
                >
                    <div class="min-h-0 flex-1 space-y-3 overflow-y-auto px-4 py-4 sm:px-5" id="doctor-chat-messages" wire:ignore.self>
                        @forelse ($messages as $msg)
                            <div
                                wire:key="doc-chat-{{ $msg['id'] }}"
                                @class([
                                    'flex',
                                    'justify-end' => $msg['send_by'] === 'doctor',
                                    'justify-start' => $msg['send_by'] !== 'doctor',
                                ])
                            >
                                <div
                                    @class([
                                        'max-w-[min(85%,28rem)] rounded-2xl px-3.5 py-2.5 text-sm shadow-sm ring-1',
                                        'bg-gradient-to-br from-[#047857] to-[#103b9e] text-white shadow-[#047857]/25 ring-[#047857]/20' => $msg['send_by'] === 'doctor',
                                        'border border-zinc-200/90 bg-white text-zinc-800 shadow-zinc-200/30 ring-zinc-100' => $msg['send_by'] !== 'doctor',
                                    ])
                                >
                                    <p class="whitespace-pre-wrap break-words">{{ $msg['body'] }}</p>
                                    @if ($msg['created_at'])
                                        <p
                                            @class([
                                                'mt-1 text-[0.65rem]',
                                                'text-white/70' => $msg['send_by'] === 'doctor',
                                                'text-zinc-400' => $msg['send_by'] !== 'doctor',
                                            ])
                                        >
                                            {{ \Illuminate\Support\Carbon::parse($msg['created_at'])->timezone(config('app.timezone'))->format('H:i') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-16 text-center">
                                <div class="mb-3 flex size-14 items-center justify-center rounded-2xl bg-white shadow-md shadow-zinc-200/50 ring-1 ring-zinc-100">
                                    <flux:icon name="chat-bubble-left-right" class="size-7 text-zinc-400" />
                                </div>
                                <p class="max-w-xs text-sm leading-relaxed text-zinc-500">{{ __('doctor.conversation.empty_chat') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div x-show="!chatMinimized" class="shrink-0 border-t border-zinc-200/90 bg-white px-4 py-3 pb-[max(0.85rem,env(safe-area-inset-bottom))] sm:px-5 lg:pb-3">
                    <form
                        wire:submit="sendMessage"
                        class="mx-auto flex max-w-4xl items-end gap-2 rounded-2xl border border-zinc-200/90 bg-gradient-to-r from-zinc-50/80 to-zinc-100/70 p-1.5 shadow-inner shadow-zinc-200/20 ring-1 ring-zinc-100/80 @if (! $appointment->isDoctorChatOpen()) pointer-events-none opacity-55 @endif"
                    >
                        <div class="min-w-0 flex-1">
                            <flux:input
                                wire:model="draft"
                                type="text"
                                :placeholder="__('doctor.conversation.type_message')"
                                :disabled="! $appointment->isDoctorChatOpen()"
                                class="!rounded-xl !border-0 !bg-transparent !shadow-none"
                            />
                        </div>
                        <flux:button
                            type="submit"
                            variant="primary"
                            icon="paper-airplane"
                            class="shrink-0 !rounded-xl shadow-md shadow-[#047857]/25"
                            wire:loading.attr="disabled"
                            :disabled="! $appointment->isDoctorChatOpen()"
                        >
                            <span class="hidden sm:inline">{{ __('doctor.conversation.send') }}</span>
                        </flux:button>
                    </form>
                    @if (in_array($appointment->status, ['new', 'rescheduled'], true) && ! $appointment->isDoctorChatOpen())
                        <p class="mt-2.5 text-center text-xs text-zinc-500">{{ __('doctor.conversation.chat_locked_until_one_hour') }}</p>
                    @elseif (in_array($appointment->status, ['new', 'rescheduled'], true) && ! $appointment->isChatOpen())
                        <p class="mt-2.5 text-center text-xs text-zinc-500">{{ __('doctor.conversation.chat_open_session_needs_approval') }}</p>
                    @elseif ($appointment->status === 'completed' && $appointment->isChatOpen())
                        <p class="mt-2.5 text-center text-xs text-zinc-900">
                            {{ __('doctor.conversation.chat_open_after_completed', [
                                'date' => $appointment->chatOpenUntil()->locale(app()->getLocale())->translatedFormat('d M Y'),
                            ]) }}
                        </p>
                    @elseif ($appointment->status === 'completed')
                        <p class="mt-2.5 text-center text-xs text-zinc-500">{{ __('doctor.conversation.chat_closed_after_window') }}</p>
                    @endif
                </div>
                </div>
            </div>

            <aside class="hidden bg-gradient-to-b from-white via-zinc-50/30 to-zinc-50/70 lg:col-span-4 lg:block">
                <div class="border-b border-zinc-200/90 bg-gradient-to-r from-[#f7f9ff] to-white px-5 py-4">
                    <h3 class="text-base font-semibold text-zinc-900">{{ __('doctor.conversation.patient_details') }}</h3>
                    <p class="mt-0.5 text-xs text-zinc-500">{{ __('doctor.workspace.title') }}</p>
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
                        @if (filled($appointment->appointment_number))
                            <div class="rounded-xl border border-indigo-200/70 bg-indigo-50/60 px-3 py-2 shadow-sm">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-500">Appointment number</p>
                                <p class="mt-0.5 text-sm font-semibold text-indigo-900">#{{ $appointment->appointment_number }}</p>
                            </div>
                        @endif
                        <div class="rounded-xl border border-zinc-200/80 bg-white/90 px-3 py-2 shadow-sm">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">{{ __('doctor.conversation.status') }}</p>
                            <p class="mt-0.5 text-sm font-semibold text-zinc-800">{{ __('doctor.conversation.status_'.$appointment->status) }}</p>
                        </div>
                        <div class="rounded-xl border border-zinc-200/80 bg-white/90 px-3 py-2 shadow-sm">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-400">{{ __('doctor.conversation.session_label') }}</p>
                            <p class="mt-0.5 text-sm font-semibold text-zinc-800">{{ $appointment->appointment_date?->format('d/m/Y') }} · {{ \Illuminate\Support\Carbon::parse((string) $appointment->start_time)->format('h:i A') }}</p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
    </div>

    @include('partials.video-call-overlay', [
        'overlayId' => 'agora-call-overlay',
        'titleId' => 'agora-call-title',
        'durationId' => 'overlay-call-duration',
        'durationLabel' => __('doctor.conversation.call_duration_label'),
        'leaveBtnId' => 'agora-leave-btn',
        'remoteId' => 'agora-remote-player',
        'localId' => 'agora-local-player',
        'toggleMicId' => 'agora-toggle-mic',
        'toggleVideoId' => 'agora-toggle-video',
        'title' => __('doctor.conversation.call_in_progress'),
        'youLabel' => __('doctor.conversation.you'),
        'endCallLabel' => __('doctor.conversation.end_call'),
        'micLabel' => __('doctor.conversation.mic'),
        'cameraLabel' => __('doctor.conversation.camera'),
        'micMutedLabel' => __('doctor.conversation.mic_muted'),
        'cameraOffLabel' => __('doctor.conversation.camera_off'),
    ])

    @include('partials.agora-media-controls')

    <div
        id="doctor-conversation-bootstrap"
        wire:ignore
        class="hidden"
        data-pusher-key="{{ config('broadcasting.connections.pusher.key') }}"
        data-pusher-cluster="{{ config('broadcasting.connections.pusher.options.cluster') }}"
        data-appointment-id="{{ $appointment->id }}"
        data-doctor-id="{{ auth('doctor')->id() }}"
        data-agora-app-id="{{ $agoraAppId }}"
        data-agora-token="{{ $agoraToken }}"
        data-agora-channel="{{ $agoraChannel }}"
        data-agora-ready="{{ $agoraAppId !== '' ? '1' : '0' }}"
    ></div>

    @include('partials.doctor-complete-appointment-modals')
</div>

@push('scripts')
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.23.0.js" data-agora-sdk="1"></script>
    <script>
        function initDoctorConversationRealtime() {
            const boot = document.getElementById('doctor-conversation-bootstrap');
            if (!boot) {
                return;
            }

            const appointmentId = Number(boot.dataset.appointmentId);

            if (boot.dataset.initialized === '1' && boot.dataset.boundAppointmentId === String(appointmentId)) {
                if (!boot.__joinVideoCall) {
                    delete boot.dataset.initialized;
                    delete boot.dataset.boundAppointmentId;
                    initDoctorConversationRealtime();

                    return;
                }

                boot.__bindCallButtons?.();

                return;
            }

            if (boot.dataset.initialized === '1') {
                // Soft re-init must not broadcast call.ended to the patient.
                boot.__leaveCall?.(false).catch(() => {});
            }

            const pusherKey = boot.dataset.pusherKey || '';
            const pusherCluster = boot.dataset.pusherCluster || 'mt1';

            function isConsultationMobile() {
                return window.matchMedia('(max-width: 1023px)').matches;
            }

            function agoraRemotePlayerId() {
                return isConsultationMobile() ? 'agora-remote-player-mobile' : 'agora-remote-player';
            }

            function agoraLocalPlayerId() {
                return isConsultationMobile() ? 'agora-local-player-mobile' : 'agora-local-player';
            }

            function chatPanelEl() {
                return document.getElementById(isConsultationMobile() ? 'doctor-chat-panel-mobile' : 'doctor-chat-panel');
            }

            function chatMessagesEl() {
                return document.getElementById(isConsultationMobile() ? 'doctor-chat-messages-mobile' : 'doctor-chat-messages');
            }

            const csrfToken = chatPanelEl()?.dataset.csrf
                || document.querySelector('#doctor-chat-panel')?.dataset.csrf
                || '';
            const seenMessageIds = new Set();
            const doctorId = Number(boot.dataset.doctorId || 0);

            const metricsEl = document.getElementById('conversation-page-metrics');
            const bootAgoraReady = boot.dataset.agoraReady === '1';

            const callChip = document.getElementById('call-status-chip');
            const callTypeLabel = document.getElementById('call-type-label');
            const callDurationDisplay = document.getElementById('call-duration-display');

            function overlayTitleEl() {
                return document.getElementById('agora-call-title');
            }

            function overlayDurationEl() {
                return document.getElementById('overlay-call-duration');
            }

            function updateActiveCallOverlayUi() {
                if (!currentMode) {
                    return;
                }

                const labelVideo = metricsEl?.dataset.labelVideo || 'Video call';
                const labelVoice = metricsEl?.dataset.labelVoice || 'Voice call';
                const titleEl = overlayTitleEl();

                if (titleEl) {
                    titleEl.textContent = currentMode === 'video' ? labelVideo : labelVoice;
                }

                if (callTypeLabel) {
                    callTypeLabel.textContent = currentMode === 'video' ? labelVideo : labelVoice;
                }

                tickCallTimer();
                syncMediaControlUi();
            }

            function btnVideo() {
                return document.getElementById(isConsultationMobile() ? 'btn-agora-video-mobile' : 'btn-agora-video');
            }

            function btnAudio() {
                return document.getElementById(isConsultationMobile() ? 'btn-agora-audio-mobile' : 'btn-agora-audio');
            }

            const btnIdleClass =
                'inline-flex min-h-10 items-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-zinc-800 shadow-sm transition hover:border-[#047857]/30 hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#10B981] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-45';
            const btnActiveClass =
                'inline-flex min-h-10 items-center gap-2 rounded-xl border-2 border-emerald-500 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-md shadow-emerald-900/10 ring-2 ring-emerald-500/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-45';

            document.querySelectorAll('#doctor-chat-messages-mobile [wire\\:key^="doc-chat-mobile-"], #doctor-chat-messages [wire\\:key^="doc-chat-"]').forEach((el) => {
                const key = el.getAttribute('wire:key') || '';
                const id = key.replace('doc-chat-mobile-', '').replace('doc-chat-', '');
                if (id) seenMessageIds.add(id);
            });

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

            let sessionTimerId = null;
            let callTimerId = null;
            let callStartedAt = null;
            let sessionEndedDisconnectHandled = false;
            let agoraClient = null;
            let localAudio = null;
            let localVideo = null;
            let currentMode = null;
            let callJoinInProgress = false;

            function maybeEndCallWhenSessionExpired(leftSeconds) {
                if (metricsEl?.dataset.relaxedSessionLimits === '1') {
                    return;
                }

                if (leftSeconds > 0) {
                    sessionEndedDisconnectHandled = false;

                    return;
                }

                if (!currentMode || sessionEndedDisconnectHandled) {
                    return;
                }

                sessionEndedDisconnectHandled = true;
                const endedMessage = metricsEl?.dataset.sessionEnded || 'Session time has ended.';

                leaveCall()
                    .then(() => {
                        if (window.Flux?.toast) {
                            window.Flux.toast({ text: endedMessage, variant: 'warning' });
                        }
                    })
                    .catch(() => {});
            }

            let sessionExpiredRefreshHandled = false;

            function maybeRefreshWhenSessionExpires(leftSeconds) {
                if (metricsEl?.dataset.relaxedSessionLimits === '1') {
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

            function tickSessionTimers() {
                const metrics = document.getElementById('conversation-page-metrics');
                const elapsedEl = document.getElementById(isConsultationMobile() ? 'timer-session-elapsed-mobile' : 'timer-session-elapsed');
                const remainingEl = document.getElementById(isConsultationMobile() ? 'timer-session-remaining-mobile' : 'timer-session-remaining');
                if (!metrics || !elapsedEl) return;

                const status = metrics.dataset.status || '';
                const startIso = metrics.dataset.sessionStart || '';
                const endIso = metrics.dataset.sessionEnd || '';
                const scheduledTime = metrics.dataset.sessionScheduledTime || '--:--';

                // "Session time" should show fixed scheduled time, not elapsed stopwatch.
                elapsedEl.textContent = scheduledTime;

                if (status !== 'in_process' || !startIso) {
                    if (remainingEl) remainingEl.textContent = '—';
                    return;
                }

                const now = Date.now();

                if (remainingEl && endIso) {
                    const end = new Date(endIso).getTime();
                    const left = (end - now) / 1000;
                    remainingEl.textContent = formatDuration(left);
                    remainingEl.classList.toggle('text-amber-700', left > 0 && left <= 300);
                    remainingEl.classList.toggle('text-rose-600', left <= 0);
                    remainingEl.classList.toggle('text-[#047857]', left > 300);
                    maybeEndCallWhenSessionExpired(left);
                    maybeRefreshWhenSessionExpires(left);
                }
            }

            function startSessionTimers() {
                if (sessionTimerId) clearInterval(sessionTimerId);
                tickSessionTimers();
                sessionTimerId = setInterval(tickSessionTimers, 1000);
            }

            function tickCallTimer() {
                if (!callStartedAt) {
                    return;
                }

                const formatted = formatDuration((Date.now() - callStartedAt) / 1000);

                if (callDurationDisplay) {
                    callDurationDisplay.textContent = formatted;
                }

                const durationEl = overlayDurationEl();
                if (durationEl) {
                    durationEl.textContent = formatted;
                }
            }

            function startCallTimer(mode, labelVideo, labelVoice) {
                if (callTimerId) {
                    clearInterval(callTimerId);
                }

                callStartedAt = Date.now();
                tickCallTimer();
                callTimerId = setInterval(tickCallTimer, 1000);

                if (callChip) {
                    callChip.classList.remove('hidden');
                    callChip.classList.add('flex');
                }

                if (callTypeLabel) {
                    callTypeLabel.textContent = mode === 'video' ? labelVideo : labelVoice;
                }

                const titleEl = overlayTitleEl();
                if (titleEl) {
                    titleEl.textContent = mode === 'video' ? labelVideo : labelVoice;
                }
            }

            function stopCallTimer() {
                if (callTimerId) {
                    clearInterval(callTimerId);
                }

                callTimerId = null;
                callStartedAt = null;

                if (callChip) {
                    callChip.classList.add('hidden');
                    callChip.classList.remove('flex');
                }

                if (callDurationDisplay) {
                    callDurationDisplay.textContent = '00:00';
                }

                const durationEl = overlayDurationEl();
                if (durationEl) {
                    durationEl.textContent = '00:00';
                }
            }

            function setCallButtonsIdle() {
                const videoBtn = btnVideo();
                const audioBtn = btnAudio();
                if (videoBtn) {
                    videoBtn.className = btnIdleClass;
                    const l = videoBtn.querySelector('.btn-label');
                    if (l) {
                        l.textContent = videoBtn.dataset.labelVideo || 'Video';
                    }
                }
                if (audioBtn) {
                    audioBtn.className = btnIdleClass;
                    const l = audioBtn.querySelector('.btn-label');
                    if (l) {
                        l.textContent = audioBtn.dataset.labelVoice || 'Voice';
                    }
                }
            }

            function setCallButtonConnecting(btn) {
                if (!btn) return;
                const label = btn.querySelector('.btn-label');
                const connecting = document.getElementById('conversation-page-metrics')?.dataset.labelConnecting || '…';
                if (label) label.textContent = connecting;
                btn.disabled = true;
            }

            function setCallButtonActive(btn, labelText) {
                if (!btn) return;
                btn.className = btnActiveClass;
                btn.disabled = true;
                const label = btn.querySelector('.btn-label');
                if (label) label.textContent = labelText;
            }

            function restoreCallButtonsAfterError() {
                const videoBtn = btnVideo();
                const audioBtn = btnAudio();
                if (videoBtn) {
                    videoBtn.disabled = !bootAgoraReady;
                }
                if (audioBtn) {
                    audioBtn.disabled = !bootAgoraReady;
                }
                setCallButtonsIdle();
            }

            const initialVideoBtn = btnVideo();
            const initialAudioBtn = btnAudio();
            if (initialVideoBtn) {
                initialVideoBtn.dataset.labelVideo = initialVideoBtn.querySelector('.btn-label')?.textContent || 'Video';
            }
            if (initialAudioBtn) {
                initialAudioBtn.dataset.labelVoice = initialAudioBtn.querySelector('.btn-label')?.textContent || 'Voice';
            }

            startSessionTimers();

            function appendMessageRow(payload) {
                const wrap = chatMessagesEl();
                if (!wrap || !payload.id) return;
                if (seenMessageIds.has(payload.id)) return;
                if (payload.send_by === 'doctor' && doctorId && Number(payload.from_id) === doctorId) {
                    return;
                }
                seenMessageIds.add(payload.id);

                const emptyCard = wrap.querySelector('.doctor-consultation-chat-empty')
                    || wrap.querySelector('.flex.flex-col.items-center.justify-center');
                if (emptyCard) emptyCard.remove();

                const row = document.createElement('div');
                const isDoctor = payload.send_by === 'doctor';
                const mobileChat = isConsultationMobile() && wrap.id === 'doctor-chat-messages-mobile';

                if (mobileChat) {
                    row.className = isDoctor ? 'flex flex-row-reverse gap-2' : 'flex gap-2';

                    const stack = document.createElement('div');
                    stack.className = 'min-w-0 max-w-[78%]'.concat(isDoctor ? ' text-end' : '');

                    const bubble = document.createElement('div');
                    bubble.className = isDoctor
                        ? 'inline-block rounded-2xl rounded-br-md bg-[#047857] px-3.5 py-2 text-sm text-white shadow-sm'
                        : 'inline-block rounded-2xl rounded-bl-md border border-slate-100 bg-slate-50 px-3.5 py-2 text-sm text-slate-800 shadow-sm';

                    const text = document.createElement('p');
                    text.className = 'whitespace-pre-wrap break-words text-start';
                    text.textContent = payload.body || '';
                    bubble.appendChild(text);
                    stack.appendChild(bubble);

                    if (payload.created_at) {
                        const time = document.createElement('time');
                        time.className = 'mt-1 block text-[0.625rem] text-slate-400'.concat(isDoctor ? ' text-end' : '');
                        const d = new Date(payload.created_at);
                        time.textContent = d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
                        stack.appendChild(time);
                    }

                    row.appendChild(stack);
                    wrap.appendChild(row);
                    wrap.scrollTop = wrap.scrollHeight;
                    window.dispatchEvent(new CustomEvent('doctor-chat-message-received'));

                    return;
                }

                row.className = isDoctor ? 'flex justify-end' : 'flex justify-start';

                const bubble = document.createElement('div');
                bubble.className = isDoctor
                    ? 'max-w-[min(85%,28rem)] rounded-2xl bg-[#047857] px-3.5 py-2.5 text-sm text-white shadow-sm shadow-[#047857]/25'
                    : 'max-w-[min(85%,28rem)] rounded-2xl border border-zinc-200/90 bg-white px-3.5 py-2.5 text-sm text-zinc-800 shadow-sm shadow-zinc-200/30';

                const text = document.createElement('p');
                text.className = 'whitespace-pre-wrap break-words';
                text.textContent = payload.body || '';
                bubble.appendChild(text);

                if (payload.created_at) {
                    const time = document.createElement('p');
                    time.className = isDoctor ? 'mt-1 text-[0.65rem] text-white/70' : 'mt-1 text-[0.65rem] text-zinc-400';
                    const d = new Date(payload.created_at);
                    time.textContent = d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
                    bubble.appendChild(time);
                }

                row.appendChild(bubble);
                wrap.appendChild(row);
                wrap.scrollTop = wrap.scrollHeight;
            }

            if (pusherKey) {
                const pusher = new Pusher(pusherKey, {
                    cluster: pusherCluster,
                    authEndpoint: '/broadcasting/auth',
                    auth: {
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    },
                });

                const channel = pusher.subscribe('private-appointment.' + appointmentId);
                channel.bind('message.created', (data) => appendMessageRow(data));
                channel.bind('call.ended', (data) => {
                    handleRemoteCallEnded(data);
                });
                channel.bind('session.start-approved', (data) => {
                    const approvedAppointmentId = Number(data?.appointment_id || appointmentId);

                    if (window.Livewire) {
                        window.Livewire.dispatch('session-start-approved', {
                            appointmentId: approvedAppointmentId,
                        });

                        const conversationRoot = boot.closest('[wire\\:id]');
                        const wireId = conversationRoot?.getAttribute('wire:id');

                        if (wireId) {
                            window.Livewire.find(wireId)?.$refresh();
                        }
                    }
                });
            }

            const panel = chatPanelEl();
            const notifyUrl = panel?.dataset.notifyUrl || '';
            const endCallUrl = panel?.dataset.endCallUrl || '';
            const tokenUrl = panel?.dataset.tokenUrl || '';

            async function refreshAgoraConfig() {
                if (!tokenUrl) return null;
                const res = await fetch(tokenUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });
                if (!res.ok) return null;
                return res.json();
            }

            function showOverlay(show) {
                const inline = document.getElementById('doctor-consultation-inline-video');
                const idle = document.getElementById('doctor-consultation-video-idle');
                const quality = document.getElementById('doctor-consultation-call-quality');
                const leaveMobile = document.getElementById('agora-leave-btn-mobile');
                const controlsWrap = document.getElementById('doctor-consultation-call-controls-wrap');
                const el = document.getElementById('agora-call-overlay');

                if (isConsultationMobile()) {
                    el?.classList.add('hidden');
                    el?.setAttribute('aria-hidden', 'true');
                    inline?.classList.toggle('doctor-consultation-inline-video--live', show);
                    idle?.classList.toggle('hidden', show);
                    quality?.classList.toggle('hidden', !show);
                    quality?.classList.toggle('inline-flex', show);
                    leaveMobile?.classList.toggle('hidden', !show);
                    leaveMobile?.classList.toggle('inline-flex', show);
                    controlsWrap?.classList.toggle('hidden', !show);

                    const chatToggle = document.getElementById('agora-toggle-chat-mobile');
                    chatToggle?.classList.toggle('hidden', !show);
                    chatToggle?.classList.toggle('inline-flex', show);

                    window.dispatchEvent(new CustomEvent(show ? 'consultation-call-active' : 'consultation-call-ended'));

                    document.querySelectorAll('#doctor-consultation-inline-video .doctor-consultation-call-controls__btn:not(.hidden)').forEach((btn) => {
                        btn.classList.add('inline-flex');
                    });

                    if (show) {
                        window.requestAnimationFrame(() => {
                            window.requestAnimationFrame(() => {
                                replayActiveVideoTracks();
                            });
                        });
                    }

                    return;
                }

                if (!el) {
                    return;
                }

                el.classList.toggle('hidden', !show);
                el.setAttribute('aria-hidden', show ? 'false' : 'true');

                if (show) {
                    window.requestAnimationFrame(() => {
                        window.requestAnimationFrame(() => {
                            replayActiveVideoTracks();
                        });
                    });
                }
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

            function assignLocalTracks(audio, video = null) {
                localAudio = audio;
                localVideo = video;
                boot.__localAudio = audio;
                boot.__localVideo = video;
            }

            function clearLocalTracks() {
                assignLocalTracks(null, null);
            }

            function mediaControlOptions(mode = currentMode) {
                return {
                    micBtnId: isConsultationMobile() ? 'agora-toggle-mic-mobile' : 'agora-toggle-mic',
                    videoBtnId: isConsultationMobile() ? 'agora-toggle-video-mobile' : 'agora-toggle-video',
                    localPreviewId: agoraLocalPlayerId(),
                    localAudio: boot.__localAudio || localAudio,
                    localVideo: boot.__localVideo || localVideo,
                    mode,
                };
            }

            function syncMediaControlUi(mode = currentMode) {
                window.MashoraAgoraMediaControls?.sync(mediaControlOptions(mode));
            }

            function showMediaControlsForMode(mode) {
                window.MashoraAgoraMediaControls?.showControlsForMode(
                    isConsultationMobile() ? 'agora-toggle-mic-mobile' : 'agora-toggle-mic',
                    isConsultationMobile() ? 'agora-toggle-video-mobile' : 'agora-toggle-video',
                    mode,
                );
            }

            async function leaveCallLocal() {
                stopCallTimer();
                setCallButtonsIdle();
                const videoBtn = btnVideo();
                const audioBtn = btnAudio();
                if (videoBtn) {
                    videoBtn.disabled = !bootAgoraReady;
                }
                if (audioBtn) {
                    audioBtn.disabled = !bootAgoraReady;
                }
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
                currentMode = null;
                boot.__currentMode = null;
                const rp = document.getElementById(agoraRemotePlayerId());
                const lp = document.getElementById(agoraLocalPlayerId());
                if (rp) {
                    rp.innerHTML = '';
                }
                if (lp) {
                    lp.innerHTML = '';
                }
                showOverlay(false);
                syncMediaControlUi(null);
                document.getElementById('agora-toggle-mic')?.classList.add('hidden');
                document.getElementById('agora-toggle-video')?.classList.add('hidden');
                document.getElementById('agora-toggle-mic-mobile')?.classList.add('hidden');
                document.getElementById('agora-toggle-video-mobile')?.classList.add('hidden');
                document.getElementById('agora-leave-btn-mobile')?.classList.add('hidden');
                document.getElementById('agora-toggle-mic-mobile')?.classList.remove('inline-flex');
                document.getElementById('agora-toggle-video-mobile')?.classList.remove('inline-flex');
                document.getElementById('agora-leave-btn-mobile')?.classList.remove('inline-flex');
            }

            async function postEndCall() {
                if (!endCallUrl) {
                    return;
                }

                try {
                    await fetch(endCallUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                } catch (_) {
                    // ignore network errors
                }
            }

            async function leaveCall(notifyRemote = true) {
                const wasActive = currentMode;

                await leaveCallLocal();

                if (notifyRemote && wasActive) {
                    await postEndCall();
                }
            }

            async function handleRemoteCallEnded(data) {
                if (Number(data?.appointment_id || 0) !== appointmentId) {
                    return;
                }

                if (currentMode) {
                    await leaveCallLocal();
                }

                const nextStatus = data?.status || null;
                const metrics = document.getElementById('conversation-page-metrics');

                if (nextStatus && metrics) {
                    metrics.dataset.status = nextStatus;
                }

                if (nextStatus === 'completed') {
                    document.getElementById('btn-agora-video')?.classList.add('hidden');
                    document.getElementById('btn-agora-audio')?.classList.add('hidden');
                    document.getElementById('btn-agora-video-mobile')?.classList.add('hidden');
                    document.getElementById('btn-agora-audio-mobile')?.classList.add('hidden');

                    if (window.Livewire) {
                        window.Livewire.dispatch('doctor-session-completed', {
                            appointmentId,
                        });

                        const conversationRoot = boot.closest('[wire\\:id]');
                        const wireId = conversationRoot?.getAttribute('wire:id');

                        if (wireId) {
                            window.Livewire.find(wireId)?.$refresh();
                        }
                    }
                }
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
                    } catch (error) {
                        console.error('Failed to subscribe to remote user media', error);
                    }
                });

                client.on('user-unpublished', () => {
                    // Patient refresh may briefly unpublish; keep containers + local stream alive.
                });

                client.on('user-left', () => {
                    // Wait for patient rejoin without destroying the live call UI.
                    if (currentMode && notifyUrl) {
                        refreshAgoraConfig()
                            .then((cfg) => {
                                if (! cfg) {
                                    return null;
                                }

                                return postNotify(currentMode === 'audio' ? 'audio' : 'video', cfg);
                            })
                            .catch(() => {});
                    }
                });
            }

            async function subscribeExistingRemoteUsers(client, mode) {
                for (const user of client.remoteUsers) {
                    if (user.hasAudio) {
                        await client.subscribe(user, 'audio');
                        user.audioTrack?.play();
                    }

                    if (user.hasVideo && mode === 'video') {
                        await client.subscribe(user, 'video');
                        user.videoTrack?.play(agoraRemotePlayerId());
                    }
                }
            }

            async function postNotify(callType, cfg) {
                if (!notifyUrl) return null;
                const res = await fetch(notifyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        agora_app_id: cfg.agora_app_id,
                        agora_token: cfg.agora_token,
                        agora_channel: cfg.agora_channel,
                        call_type: callType,
                    }),
                });

                if (!res.ok) {
                    let message = metricsEl?.dataset.labelPatientNotifyFailed || 'Could not notify the patient.';
                    try {
                        const body = await res.json();
                        if (body?.message) {
                            message = body.message;
                        }
                    } catch (_) {
                        // ignore json parse errors
                    }

                    showCallToast(message, 'warning');

                    return null;
                }

                return res.json();
            }

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
                    return metricsEl?.dataset.labelCameraPermission || fallback;
                }

                if (message) {
                    return message;
                }

                return fallback;
            }

            function mountOverlayToBody() {
                const overlayEl = document.getElementById('agora-call-overlay');
                if (overlayEl && overlayEl.parentElement !== document.body) {
                    document.body.appendChild(overlayEl);
                }
            }

            mountOverlayToBody();

            function ensureAgoraSdk(timeoutMs = 12000) {
                if (window.AgoraRTC) {
                    return Promise.resolve(window.AgoraRTC);
                }

                const existing = document.querySelector('script[data-agora-sdk="1"]');
                if (!existing) {
                    return Promise.reject(new Error(metricsEl?.dataset.labelAgoraSdkMissing || 'Agora SDK missing'));
                }

                return new Promise((resolve, reject) => {
                    const startedAt = Date.now();

                    const tick = () => {
                        if (window.AgoraRTC) {
                            resolve(window.AgoraRTC);

                            return;
                        }

                        if (Date.now() - startedAt >= timeoutMs) {
                            reject(new Error(metricsEl?.dataset.labelAgoraSdkMissing || 'Agora SDK missing'));

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

                currentMode = null;
                const rp = document.getElementById(agoraRemotePlayerId());
                const lp = document.getElementById(agoraLocalPlayerId());
                if (rp) {
                    rp.innerHTML = '';
                }
                if (lp) {
                    lp.innerHTML = '';
                }
            }

            function setOverlayConnecting(mode, labelConnecting) {
                const titleEl = overlayTitleEl();
                if (titleEl) {
                    titleEl.textContent = labelConnecting;
                }

                if (callTypeLabel) {
                    callTypeLabel.textContent = mode === 'video' ? labelVideo : labelVoice;
                }

                const durationEl = overlayDurationEl();
                if (durationEl) {
                    durationEl.textContent = '00:00';
                }

                showMediaControlsForMode(mode);
            }

            const labelVideo = metricsEl?.dataset.labelVideo || 'Video call';
            const labelVoice = metricsEl?.dataset.labelVoice || 'Voice call';
            const labelLive = metricsEl?.dataset.labelLive || 'Live';
            const labelConnecting = metricsEl?.dataset.labelConnecting || 'Connecting…';
            const labelCallFailed = metricsEl?.dataset.labelCallFailed || 'Could not start the call.';

            async function joinVideoCall() {
                if (currentMode || callJoinInProgress) {
                    if (currentMode) {
                        showCallToast(metricsEl?.dataset.labelCallAlreadyActive || 'A call is already in progress. End it first.', 'warning');
                    }

                    return;
                }

                callJoinInProgress = true;
                setCallButtonConnecting(btnVideo());
                showOverlay(true);
                setOverlayConnecting('video', labelConnecting);

                try {
                    await ensureAgoraSdk();
                    await resetPartialAgoraJoin();

                    const cfg = await refreshAgoraConfig();
                    if (!cfg) {
                        throw new Error(labelCallFailed);
                    }

                    const notify = await postNotify('video', cfg);
                    if (metricsEl && notify) {
                        metricsEl.dataset.status = notify.status || 'in_process';
                        metricsEl.dataset.sessionStart = notify.actual_start_at || '';
                        metricsEl.dataset.sessionEnd = notify.extend_at || '';
                    }

                    const client = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });
                    agoraClient = client;
                    registerRemoteUserHandlers(client, 'video');

                    await client.join(cfg.agora_app_id, cfg.agora_channel, cfg.agora_token || null, null);
                    const audioTrack = await AgoraRTC.createMicrophoneAudioTrack();
                    const videoTrack = await AgoraRTC.createCameraVideoTrack();
                    assignLocalTracks(audioTrack, videoTrack);
                    videoTrack.play(agoraLocalPlayerId());
                    await client.publish([audioTrack, videoTrack]);
                    await subscribeExistingRemoteUsers(client, 'video');
                    currentMode = 'video';
                    boot.__currentMode = 'video';
                    const videoBtn = btnVideo();
                    const audioBtn = btnAudio();
                    setCallButtonActive(videoBtn, labelLive + ' · ' + labelVideo);
                    if (audioBtn) {
                        audioBtn.disabled = true;
                    }
                    showOverlay(true);
                    showMediaControlsForMode('video');
                    startCallTimer('video', labelVideo, labelVoice);
                    syncMediaControlUi('video');
                    replayActiveVideoTracks();
                } catch (e) {
                    console.error(e);
                    await resetPartialAgoraJoin();
                    showOverlay(false);
                    showCallToast(callErrorMessage(e, labelCallFailed));
                    restoreCallButtonsAfterError();
                } finally {
                    callJoinInProgress = false;
                }
            }

            async function joinAudioCall() {
                if (currentMode || callJoinInProgress) {
                    return;
                }

                callJoinInProgress = true;
                setCallButtonConnecting(btnAudio());
                showOverlay(true);
                setOverlayConnecting('audio', labelConnecting);

                try {
                    await ensureAgoraSdk();
                    await resetPartialAgoraJoin();

                    const cfg = await refreshAgoraConfig();
                    if (!cfg) {
                        throw new Error(labelCallFailed);
                    }

                    const notify = await postNotify('audio', cfg);
                    if (metricsEl && notify) {
                        metricsEl.dataset.status = notify.status || 'in_process';
                        metricsEl.dataset.sessionStart = notify.actual_start_at || '';
                        metricsEl.dataset.sessionEnd = notify.extend_at || '';
                    }

                    const client = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });
                    agoraClient = client;
                    registerRemoteUserHandlers(client, 'audio');

                    await client.join(cfg.agora_app_id, cfg.agora_channel, cfg.agora_token || null, null);
                    const audioTrack = await AgoraRTC.createMicrophoneAudioTrack();
                    assignLocalTracks(audioTrack, null);
                    await client.publish([audioTrack]);
                    await subscribeExistingRemoteUsers(client, 'audio');
                    currentMode = 'audio';
                    boot.__currentMode = 'audio';
                    const videoBtn = btnVideo();
                    const audioBtn = btnAudio();
                    setCallButtonActive(audioBtn, labelLive + ' · ' + labelVoice);
                    if (videoBtn) {
                        videoBtn.disabled = true;
                    }
                    showOverlay(true);
                    showMediaControlsForMode('audio');
                    startCallTimer('audio', labelVideo, labelVoice);
                    syncMediaControlUi('audio');
                } catch (e) {
                    console.error(e);
                    await resetPartialAgoraJoin();
                    showOverlay(false);
                    showCallToast(callErrorMessage(e, labelCallFailed));
                    restoreCallButtonsAfterError();
                } finally {
                    callJoinInProgress = false;
                }
            }

            boot.__syncCallOverlay = () => {
                const mode = currentMode || boot.__currentMode;
                if (! mode) {
                    return;
                }

                currentMode = mode;
                boot.__currentMode = mode;
                showOverlay(true);
                showMediaControlsForMode(mode);
                syncMediaControlUi(mode);
                updateActiveCallOverlayUi();
                window.requestAnimationFrame(() => {
                    window.requestAnimationFrame(() => {
                        replayActiveVideoTracks();
                    });
                });
            };

            function registerDoctorConversationMorphHook() {
                if (window.__doctorConversationMorphHook) {
                    return;
                }

                window.__doctorConversationMorphHook = true;

                const registerHook = () => {
                    Livewire.hook('morph.updated', () => {
                        const bootEl = document.getElementById('doctor-conversation-bootstrap');
                        if (bootEl?.__currentMode) {
                            bootEl.__syncCallOverlay?.();
                        }
                    });

                    Livewire.hook('commit', ({ succeed }) => {
                        succeed(() => {
                            const bootEl = document.getElementById('doctor-conversation-bootstrap');
                            bootEl?.__bindCallButtons?.();
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

            registerDoctorConversationMorphHook();

            boot.__joinVideoCall = () => joinVideoCall();
            boot.__joinAudioCall = () => joinAudioCall();
            boot.__leaveCall = (notifyRemote = true) => leaveCall(notifyRemote);
            boot.__toggleMic = () => {
                window.MashoraAgoraMediaControls?.toggleMic(mediaControlOptions())
                    .catch((error) => console.error(error));
            };
            boot.__toggleVideo = () => {
                window.MashoraAgoraMediaControls?.toggleVideo(mediaControlOptions())
                    .catch((error) => console.error(error));
            };

            function bindCallControlButtons() {
                // Call controls use document-level delegation to avoid duplicate handlers after Livewire morphs.
            }

            bindCallControlButtons();
            boot.__bindCallButtons = bindCallControlButtons;

            if (!window.__doctorConversationClickBound) {
                window.__doctorConversationClickBound = true;

                document.addEventListener('click', (event) => {
                    const bootEl = document.getElementById('doctor-conversation-bootstrap');
                    if (!bootEl) {
                        return;
                    }

                    if (event.target.closest('#agora-leave-btn, #agora-leave-btn-mobile, [data-video-call-leave="agora-leave-btn"]')) {
                        event.preventDefault();
                        bootEl.__leaveCall?.().catch((error) => console.error(error));
                    }

                    if (event.target.closest('#agora-toggle-mic, #agora-toggle-mic-mobile')) {
                        event.preventDefault();
                        bootEl.__toggleMic?.();
                    }

                    if (event.target.closest('#agora-toggle-video, #agora-toggle-video-mobile')) {
                        event.preventDefault();
                        bootEl.__toggleVideo?.();
                    }
                });
            }

            if (!window.__doctorConversationNavigateHook) {
                window.__doctorConversationNavigateHook = true;

                document.addEventListener('livewire:navigating', () => {
                    const bootEl = document.getElementById('doctor-conversation-bootstrap');
                    // Do not notify remote — navigation / refresh must not end the call for the other party.
                    bootEl?.__leaveCall?.(false).catch(() => {});

                    if (bootEl) {
                        delete bootEl.dataset.initialized;
                        delete bootEl.dataset.boundAppointmentId;
                    }
                });
            }

            if (!window.__doctorConversationCallEndedHook) {
                window.__doctorConversationCallEndedHook = true;

                window.addEventListener('mashora:call-ended', (event) => {
                    handleRemoteCallEnded(event.detail || {});
                });
            }

            if (boot.dataset.sessionObserved !== '1' && metricsEl) {
                boot.dataset.sessionObserved = '1';
                new MutationObserver(() => {
                    startSessionTimers();
                }).observe(metricsEl, {
                    attributes: true,
                    attributeFilter: ['data-status', 'data-session-start', 'data-session-end'],
                });
            }

            boot.dataset.initialized = '1';
            boot.dataset.boundAppointmentId = String(appointmentId);
        }

        function mashoraDoctorInvokeCall(method) {
            const boot = document.getElementById('doctor-conversation-bootstrap');
            if (!boot?.__joinVideoCall && !boot?.__joinAudioCall) {
                initDoctorConversationRealtime();
            }

            const bootEl = document.getElementById('doctor-conversation-bootstrap');
            const fn = bootEl?.[method];
            if (typeof fn !== 'function') {
                const message = document.getElementById('conversation-page-metrics')?.dataset.labelCallControlsFailed
                    || 'Call controls failed to load. Please refresh the page.';
                if (window.Flux?.toast) {
                    window.Flux.toast({ text: message, variant: 'danger' });
                } else {
                    console.error(message);
                }

                return;
            }

            fn().catch((error) => console.error(error));
        }

        window.mashoraDoctorStartVideoCall = (event) => {
            event?.preventDefault?.();
            mashoraDoctorInvokeCall('__joinVideoCall');
        };

        window.mashoraDoctorStartAudioCall = (event) => {
            event?.preventDefault?.();
            mashoraDoctorInvokeCall('__joinAudioCall');
        };

        document.addEventListener('DOMContentLoaded', initDoctorConversationRealtime);
        document.addEventListener('livewire:navigated', initDoctorConversationRealtime);
        initDoctorConversationRealtime();
    </script>
@endpush

@include('partials.appointment-start-timer-script')
