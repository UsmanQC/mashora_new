<?php

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::patient')] #[Title('Appointments')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = 'ongoing';

    /**
     * @return array<string, list<string>>
     */
    protected function tabStatuses(): array
    {
        return [
            'ongoing' => ['new', 'in_process', 'pending_follow_up'],
            'rescheduled' => ['rescheduled'],
            'completed' => ['completed'],
            'cancelled' => ['cancelled'],
        ];
    }

    public function mount(): void
    {
        if (! array_key_exists($this->tab, $this->tabStatuses())) {
            $this->tab = 'ongoing';
        }
    }

    public function selectTab(string $tab): void
    {
        if (! array_key_exists($tab, $this->tabStatuses())) {
            return;
        }

        $this->tab = $tab;
        $this->resetPage();
    }

    /**
     * @return Builder<Appointment>
     */
    protected function baseQuery(): Builder
    {
        $userId = Auth::id();
        abort_unless(is_int($userId), 403);

        return Appointment::query()
            ->with('doctor:id,name,name_ar')
            ->where('user_id', $userId);
    }

    public function getAppointmentsProperty(): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->whereIn('status', $this->tabStatuses()[$this->tab])
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->paginate(10);
    }

    /**
     * @return Collection<string, int>
     */
    public function getTabCountsProperty(): Collection
    {
        $counts = $this->baseQuery()
            ->whereIn('status', ['new', 'in_process', 'pending_follow_up', 'rescheduled', 'completed', 'cancelled'])
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count);

        return collect([
            'ongoing' => (int) ($counts['new'] ?? 0) + (int) ($counts['in_process'] ?? 0) + (int) ($counts['pending_follow_up'] ?? 0),
            'rescheduled' => (int) ($counts['rescheduled'] ?? 0),
            'completed' => (int) ($counts['completed'] ?? 0),
            'cancelled' => (int) ($counts['cancelled'] ?? 0),
        ]);
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
            default => str_replace('_', ' ', $status),
        };
    }

    public function tabHeading(): string
    {
        return match ($this->tab) {
            'ongoing' => __('patient.appointments.tab_ongoing'),
            'rescheduled' => __('patient.appointments.tab_rescheduled'),
            'completed' => __('patient.appointments.tab_completed'),
            'cancelled' => __('patient.appointments.tab_cancelled'),
            default => __('patient.appointments.list_heading'),
        };
    }

    public function tabEmptyMessage(): string
    {
        return match ($this->tab) {
            'ongoing' => __('patient.appointments.empty_ongoing'),
            'rescheduled' => __('patient.appointments.empty_rescheduled'),
            'completed' => __('patient.appointments.empty_completed'),
            'cancelled' => __('patient.appointments.empty_cancelled'),
            default => __('patient.menu.no_record_found'),
        };
    }

    public function statusAccentClasses(string $status): string
    {
        return match ($status) {
            'new' => 'border-s-sky-500',
            'in_process' => 'border-s-amber-500',
            'pending_follow_up' => 'border-s-violet-500',
            'rescheduled' => 'border-s-indigo-500',
            'completed' => 'border-s-emerald-500',
            'cancelled' => 'border-s-rose-500',
            default => 'border-s-zinc-300',
        };
    }

    public function statusBadgeClasses(string $status): string
    {
        return match ($status) {
            'new' => 'bg-sky-100 text-sky-700',
            'in_process' => 'bg-amber-100 text-amber-700',
            'pending_follow_up' => 'bg-violet-100 text-violet-700',
            'rescheduled' => 'bg-indigo-100 text-indigo-700',
            'completed' => 'bg-emerald-100 text-emerald-700',
            'cancelled' => 'bg-rose-100 text-rose-700',
            default => 'bg-zinc-100 text-zinc-700',
        };
    }

    public function formattedSessionDate(Appointment $appointment): string
    {
        return $appointment->appointment_date?->locale(app()->getLocale())->translatedFormat('d M Y') ?? '--';
    }

    public function formattedSessionTime(Appointment $appointment): string
    {
        $rawStartTime = (string) $appointment->start_time;
        if ($rawStartTime === '') {
            return '--';
        }

        try {
            return Carbon::createFromFormat('H:i:s', $rawStartTime)->format('h:i A');
        } catch (\Throwable) {
            return $rawStartTime;
        }
    }

    /**
     * @return list<int>
     */
    public function realtimeAppointmentIds(): array
    {
        return $this->baseQuery()
            ->whereIn('status', ['new', 'in_process', 'rescheduled'])
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }
}; ?>

@php
    $tabCardActive = 'border-[#1565c0] bg-[#1565c0]/5 shadow-md ring-1 ring-[#1565c0]/20';
    $tabCardInactive = 'border-zinc-200/90 bg-white hover:border-[#1565c0]/30 hover:shadow-md';
@endphp

<div class="mx-auto max-w-3xl space-y-6 px-4 py-8 pb-28 sm:pb-10">
    <div id="patient-call-join-banner" class="hidden rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-white px-4 py-3 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2">
                <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <flux:icon name="video-camera" variant="mini" class="size-4" />
                </span>
                <p id="patient-call-join-text" class="text-sm font-medium text-emerald-900"></p>
            </div>
            <a
                id="patient-call-join-now"
                href="#"
                wire:navigate
                class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700"
            >
                <flux:icon name="chat-bubble-left-right" variant="mini" class="size-4" />
                {{ __('patient.appointments.start_session') }}
            </a>
        </div>
    </div>

    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-semibold text-[#1565c0]">{{ __('patient.appointments.title') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-600">{{ __('patient.appointments.subtitle') }}</flux:text>
        </div>
        <flux:button
            :href="route('patient.home')"
            wire:navigate
            variant="ghost"
            size="sm"
            icon="arrow-left"
            :aria-label="__('patient.appointments.back_aria')"
        />
    </div>

    <div class="rounded-2xl border border-[#1565c0]/20 bg-gradient-to-br from-[#1565c0]/8 via-white to-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="text-base font-semibold text-zinc-900">{{ __('patient.appointments.book_card_title') }}</p>
                <p class="mt-1 text-sm text-zinc-600">{{ __('patient.appointments.book_card_sub') }}</p>
            </div>
            <flux:button
                :href="route('patient.schedule.filter')"
                wire:navigate
                variant="primary"
                icon="calendar-days"
                class="w-full shrink-0 !bg-[#1565c0] !text-white hover:!brightness-95 sm:w-auto"
            >
                {{ __('patient.appointments.book_new') }}
            </flux:button>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4" role="tablist" aria-label="{{ __('patient.appointments.tabs_aria') }}">
        @foreach ([
            'ongoing' => ['label' => __('patient.appointments.tab_ongoing'), 'icon' => 'clock'],
            'rescheduled' => ['label' => __('patient.appointments.tab_rescheduled'), 'icon' => 'arrow-path'],
            'completed' => ['label' => __('patient.appointments.tab_completed'), 'icon' => 'check-circle'],
            'cancelled' => ['label' => __('patient.appointments.tab_cancelled'), 'icon' => 'x-circle'],
        ] as $tabKey => $tabMeta)
            <button
                type="button"
                role="tab"
                wire:click="selectTab('{{ $tabKey }}')"
                aria-selected="{{ $tab === $tabKey ? 'true' : 'false' }}"
                class="rounded-2xl border p-4 text-start shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1565c0]/30 {{ $tab === $tabKey ? $tabCardActive : $tabCardInactive }}"
            >
                <div class="flex items-center justify-between gap-2">
                    <flux:icon :name="$tabMeta['icon']" variant="mini" @class(['size-4', $tab === $tabKey ? 'text-[#1565c0]' : 'text-zinc-400']) />
                    <span class="text-2xl font-bold tabular-nums text-[#1565c0]">{{ $this->tabCounts[$tabKey] ?? 0 }}</span>
                </div>
                <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ $tabMeta['label'] }}</p>
            </button>
        @endforeach
    </div>

    <section class="rounded-2xl border border-zinc-200/90 bg-white shadow-sm" role="tabpanel" aria-live="polite">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4 sm:px-6">
            <div>
                <flux:heading size="lg" class="font-semibold text-zinc-900">{{ $this->tabHeading() }}</flux:heading>
                <flux:text class="mt-0.5 text-sm text-zinc-500">{{ __('patient.appointments.list_heading') }}</flux:text>
            </div>
            <span class="inline-flex items-center rounded-full bg-[#1565c0]/10 px-3 py-1 text-xs font-semibold text-[#1565c0]">
                {{ $this->tabCounts[$tab] ?? 0 }}
            </span>
        </div>

        @if ($this->appointments->isEmpty())
            <div class="flex flex-col items-center px-6 py-12 text-center sm:py-16">
                @include('partials.patient-empty-record-illustration')
                <p class="mt-6 max-w-sm text-sm font-medium text-zinc-500 sm:text-base">
                    {{ $this->tabEmptyMessage() }}
                </p>
                @if ($tab === 'ongoing')
                    <flux:button
                        :href="route('patient.schedule.filter')"
                        wire:navigate
                        variant="primary"
                        class="mt-6 !bg-[#1565c0] !text-white hover:!brightness-95"
                    >
                        {{ __('patient.appointments.book_new') }}
                    </flux:button>
                @endif
            </div>
        @else
            <div class="space-y-3 p-4 sm:p-5">
                @foreach ($this->appointments as $appointment)
                    <article
                        @class([
                            'overflow-hidden rounded-2xl border border-zinc-200/90 bg-white shadow-sm transition hover:border-[#1565c0]/25 hover:shadow-md',
                            'border-s-4',
                            $this->statusAccentClasses((string) $appointment->status),
                        ])
                    >
                        <div class="p-4 sm:p-5">
                            <div class="flex items-start gap-4">
                                <flux:avatar
                                    :name="$appointment->doctor?->displayName() ?: 'DR'"
                                    circle
                                    size="lg"
                                    class="shrink-0 bg-[#1565c0]/10 text-[#1565c0] ring-2 ring-[#1565c0]/10"
                                />

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-zinc-500">
                                                {{ __('patient.appointments.specialist_label') }}
                                            </p>
                                            <h2 class="truncate text-base font-semibold text-zinc-900">
                                                {{ $appointment->doctor?->displayName() ?: __('patient.appointments.title') }}
                                            </h2>
                                        </div>
                                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $this->statusBadgeClasses((string) $appointment->status) }}">
                                            {{ $this->statusLabel((string) $appointment->status) }}
                                        </span>
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-700">
                                            <flux:icon name="calendar-days" variant="mini" class="size-3.5 text-zinc-500" />
                                            {{ $this->formattedSessionDate($appointment) }}
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-700">
                                            <flux:icon name="clock" variant="mini" class="size-3.5 text-zinc-500" />
                                            {{ $this->formattedSessionTime($appointment) }}
                                        </span>
                                    </div>

                                    <div class="mt-2 inline-flex items-center gap-1.5 text-xs text-zinc-500">
                                        <flux:icon name="user" variant="mini" class="size-3.5 text-zinc-400" />
                                        <span class="truncate">{{ $appointment->patient_name }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 pt-4">
                                @if (filled($appointment->appointment_number))
                                    <p class="text-xs text-zinc-400">
                                        {{ __('patient.appointments.reference_label') }}:
                                        <span class="font-semibold text-zinc-600">#{{ $appointment->appointment_number }}</span>
                                    </p>
                                @else
                                    <span></span>
                                @endif

                                @if ($appointment->status === 'pending_follow_up')
                                    <flux:button
                                        :href="$appointment->patient_confirmed_at === null ? route('patient.follow-up.confirm', $appointment) : route('patient.follow-up.pay', $appointment)"
                                        wire:navigate
                                        size="sm"
                                        icon="credit-card"
                                        class="!border-violet-300 !bg-violet-50 !text-violet-800 hover:!bg-violet-100"
                                    >
                                        {{ __('patient.follow_up.confirm_and_pay') }}
                                    </flux:button>
                                @elseif (in_array($appointment->status, ['new', 'in_process', 'rescheduled'], true))
                                    <flux:button
                                        :href="route('patient.appointments.conversation', ['appointment' => $appointment->id])"
                                        wire:navigate
                                        size="sm"
                                        icon="chat-bubble-left-right"
                                        class="!bg-[#1565c0] !text-white hover:!brightness-95"
                                    >
                                        {{ __('patient.appointments.start_session') }}
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($this->appointments->hasPages())
                <div class="border-t border-zinc-100 px-4 py-3 sm:px-5">
                    {{ $this->appointments->links() }}
                </div>
            @endif
        @endif
    </section>

    <div
        id="patient-appointments-realtime-bootstrap"
        class="hidden"
        data-pusher-key="{{ config('broadcasting.connections.pusher.key') }}"
        data-pusher-cluster="{{ config('broadcasting.connections.pusher.options.cluster') }}"
        data-csrf="{{ csrf_token() }}"
        data-patient-id="{{ (int) auth()->id() }}"
        data-appointment-ids='@json($this->realtimeAppointmentIds())'
        data-join-base="{{ route('patient.appointments.conversation', ['appointment' => '__ID__']) }}"
        data-token-base="{{ route('patient.appointments.realtime.agora-token', ['appointment' => '__ID__']) }}"
        data-notify-base="{{ route('patient.appointments.realtime.notify-call', ['appointment' => '__ID__']) }}"
        data-label-call="{{ __('patient.appointments.session_started_join_now') }}"
    ></div>

    <div id="patient-inline-call-overlay" class="fixed inset-0 z-[210] hidden bg-zinc-950/90 backdrop-blur-sm">
        <div class="mx-auto flex h-full w-full max-w-6xl flex-col p-3 sm:p-5">
            <div class="flex items-center justify-between rounded-xl border border-white/10 bg-zinc-900/80 px-4 py-3 text-white">
                <div>
                    <p class="text-xs uppercase tracking-wide text-zinc-300">{{ __('patient.appointments.call_in_progress') }}</p>
                    <p id="patient-inline-call-state" class="text-sm font-semibold text-white">{{ __('patient.appointments.session_started_join_now') }}</p>
                </div>
                <button type="button" id="patient-inline-call-end" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                    {{ __('patient.appointments.end_call') }}
                </button>
            </div>

            <div class="mt-3 grid min-h-0 flex-1 grid-cols-1 gap-3 md:grid-cols-[1fr_18rem]">
                <div class="min-h-[50vh] overflow-hidden rounded-2xl border border-white/10 bg-black">
                    <div id="patient-inline-call-remote" class="h-full w-full"></div>
                </div>
                <div class="flex flex-col gap-3">
                    <div class="rounded-xl border border-white/10 bg-zinc-900/80 px-3 py-2 text-xs text-zinc-200">
                        <p id="patient-inline-doctor-name" class="font-semibold text-white">—</p>
                        <p id="patient-inline-session-time" class="mt-0.5 text-zinc-300">—</p>
                    </div>
                    <div class="overflow-hidden rounded-xl border border-white/10 bg-zinc-900">
                        <div id="patient-inline-call-local" class="aspect-video w-full"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" id="patient-inline-video" class="inline-flex items-center justify-center rounded-lg bg-white/10 px-3 py-2 text-xs font-semibold text-white hover:bg-white/20">
                            {{ __('patient.appointments.video_call') }}
                        </button>
                        <button type="button" id="patient-inline-audio" class="inline-flex items-center justify-center rounded-lg bg-white/10 px-3 py-2 text-xs font-semibold text-white hover:bg-white/20">
                            {{ __('patient.appointments.voice_call') }}
                        </button>
                    </div>
                    <a id="patient-inline-open-chat" href="#" wire:navigate class="inline-flex items-center justify-center rounded-lg border border-white/20 bg-white/10 px-3 py-2 text-xs font-semibold text-white hover:bg-white/20">
                        {{ __('patient.appointments.start_session') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.23.0.js"></script>
    <script>
        function initPatientAppointmentsRealtime() {
            const boot = document.getElementById('patient-appointments-realtime-bootstrap');
            if (!boot) return;
            if (boot.dataset.initialized === '1') return;
            boot.dataset.initialized = '1';

            const pusherKey = boot.dataset.pusherKey || '';
            if (!pusherKey) return;

            let ids = [];
            try {
                ids = JSON.parse(boot.dataset.appointmentIds || '[]');
            } catch (_) {
                ids = [];
            }
            if (!Array.isArray(ids) || ids.length === 0) return;

            const pusherCluster = boot.dataset.pusherCluster || 'mt1';
            const csrf = boot.dataset.csrf || '';
            const patientId = Number(boot.dataset.patientId || 0);
            const joinBase = boot.dataset.joinBase || '';
            const tokenBase = boot.dataset.tokenBase || '';
            const notifyBase = boot.dataset.notifyBase || '';
            const labelCall = boot.dataset.labelCall || 'Session started. Join now.';
            const banner = document.getElementById('patient-call-join-banner');
            const text = document.getElementById('patient-call-join-text');
            const joinNowBtn = document.getElementById('patient-call-join-now');
            const overlay = document.getElementById('patient-inline-call-overlay');
            const remoteWrap = document.getElementById('patient-inline-call-remote');
            const localWrap = document.getElementById('patient-inline-call-local');
            const endBtn = document.getElementById('patient-inline-call-end');
            const openChat = document.getElementById('patient-inline-open-chat');
            const callState = document.getElementById('patient-inline-call-state');
            const payloadByAppointment = new Map();
            let currentAppointmentId = 0;
            let agoraClient = null;
            let localAudio = null;
            let localVideo = null;

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

            const showJoin = (appointmentId) => {
                if (!banner || !text) return;
                text.textContent = labelCall;
                banner.classList.remove('hidden');
                currentAppointmentId = Number(appointmentId) || 0;

                if ('Notification' in window) {
                    if (Notification.permission === 'granted') {
                        new Notification(labelCall);
                    } else if (Notification.permission === 'default') {
                        Notification.requestPermission().then((permission) => {
                            if (permission === 'granted') {
                                new Notification(labelCall);
                            }
                        });
                    }
                }
            };

            async function leaveInlineCall() {
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
                if (remoteWrap) remoteWrap.innerHTML = '';
                if (localWrap) localWrap.innerHTML = '';
                if (overlay) overlay.classList.add('hidden');
            }

            async function fetchAgoraConfig(appointmentId) {
                if (!tokenBase || !appointmentId) return null;
                const url = tokenBase.replace('__ID__', String(appointmentId));
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                });
                if (!res.ok) return null;
                return res.json();
            }

            async function notifyDoctor(appointmentId, callType, cfg) {
                if (!notifyBase || !appointmentId) return null;
                const url = notifyBase.replace('__ID__', String(appointmentId));
                const res = await fetch(url, {
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
                        call_type: callType,
                    }),
                });
                if (!res.ok) return null;
                return res.json();
            }

            async function joinInlineCall(appointmentId, payload = null, shouldNotify = false, callType = 'video') {
                if (!window.AgoraRTC || !appointmentId) return;
                const cfg = payload || await fetchAgoraConfig(appointmentId);
                if (!cfg) {
                    window.location.href = joinBase.replace('__ID__', String(appointmentId));
                    return;
                }

                if (shouldNotify) {
                    await notifyDoctor(appointmentId, callType, cfg);
                }

                await leaveInlineCall();
                agoraClient = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });
                agoraClient.on('user-published', async (user, mediaType) => {
                    await agoraClient.subscribe(user, mediaType);
                    if (mediaType === 'video') user.videoTrack.play('patient-inline-call-remote');
                    if (mediaType === 'audio') user.audioTrack.play();
                });

                const resolvedCallType = (payload?.call_type === 'audio' || callType === 'audio') ? 'audio' : 'video';
                if (resolvedCallType === 'video') {
                    const [, micTrack, camTrack] = await Promise.all([
                        agoraClient.join(cfg.agora_app_id, cfg.agora_channel, cfg.agora_token || null, null),
                        AgoraRTC.createMicrophoneAudioTrack(),
                        AgoraRTC.createCameraVideoTrack(),
                    ]);
                    localAudio = micTrack;
                    localVideo = camTrack;
                    camTrack.play('patient-inline-call-local');
                    await agoraClient.publish([micTrack, camTrack]);
                } else {
                    const [, micTrack] = await Promise.all([
                        agoraClient.join(cfg.agora_app_id, cfg.agora_channel, cfg.agora_token || null, null),
                        AgoraRTC.createMicrophoneAudioTrack(),
                    ]);
                    localAudio = micTrack;
                    await agoraClient.publish([micTrack]);
                }

                if (callState) {
                    callState.textContent = resolvedCallType === 'video'
                        ? @js(__('patient.appointments.incoming_video'))
                        : @js(__('patient.appointments.incoming_voice'));
                }
                if (openChat) {
                    openChat.href = joinBase.replace('__ID__', String(appointmentId));
                }
                if (overlay) overlay.classList.remove('hidden');
                banner?.classList.add('hidden');
            }

            joinNowBtn?.addEventListener('click', () => {
                const appointmentId = currentAppointmentId;
                if (!appointmentId) return;
                joinNowBtn.href = joinBase.replace('__ID__', String(appointmentId));
            });

            endBtn?.addEventListener('click', () => {
                leaveInlineCall().catch(() => {});
            });

            ids.forEach((id) => {
                const appointmentId = Number(id);
                if (!appointmentId) return;
                const channel = pusher.subscribe('private-appointment.' + appointmentId);
                channel.bind('session.started', (payload) => showJoin(payload?.appointment_id || appointmentId));
                channel.bind('call.incoming', (payload) => {
                    const incomingAppointmentId = Number(payload?.appointment_id || appointmentId);
                    payloadByAppointment.set(incomingAppointmentId, payload || null);
                    showJoin(incomingAppointmentId);
                });
            });

            if (patientId > 0) {
                const patientChannel = pusher.subscribe('private-patient.' + patientId);
                patientChannel.bind('session.join-requested', (payload) => {
                    const appointmentId = Number(payload?.appointment_id || 0);
                    if (!appointmentId) return;
                    payloadByAppointment.set(appointmentId, payload || null);
                    showJoin(appointmentId);
                });
            }
        }

        document.addEventListener('DOMContentLoaded', initPatientAppointmentsRealtime);
        document.addEventListener('livewire:navigated', initPatientAppointmentsRealtime);
    </script>
@endpush
