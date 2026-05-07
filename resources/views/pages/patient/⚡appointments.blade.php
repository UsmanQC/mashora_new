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
            'ongoing' => ['new', 'in_process'],
            'completed' => ['completed'],
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
            ->whereIn('status', ['new', 'in_process', 'completed'])
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count);

        return collect([
            'ongoing' => (int) ($counts['new'] ?? 0) + (int) ($counts['in_process'] ?? 0),
            'completed' => (int) ($counts['completed'] ?? 0),
        ]);
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'new' => __('New'),
            'in_process' => __('In process'),
            'completed' => __('Completed'),
            default => str_replace('_', ' ', $status),
        };
    }

    public function statusBadgeClasses(string $status): string
    {
        return match ($status) {
            'new' => 'bg-sky-100 text-sky-700',
            'in_process' => 'bg-amber-100 text-amber-700',
            'completed' => 'bg-emerald-100 text-emerald-700',
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
            ->whereIn('status', ['new', 'in_process'])
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }
}; ?>

<div class="mx-auto w-full max-w-5xl px-4 py-4 pb-20 sm:px-6 sm:py-5 sm:pb-10">
    <div id="patient-call-join-banner" class="mb-3 hidden rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p id="patient-call-join-text" class="text-sm font-medium text-emerald-900"></p>
            <a id="patient-call-join-now" href="#" wire:navigate class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                <flux:icon name="chat-bubble-left-right" variant="mini" class="size-4" />
                {{ __('patient.appointments.start_session') }}
            </a>
        </div>
    </div>

    <header class="flex items-center gap-3">
        <a
            href="{{ route('patient.home') }}"
            wire:navigate
            class="inline-flex size-10 shrink-0 items-center justify-center rounded-full border border-zinc-200/90 bg-white text-[#1565c0] shadow-sm transition hover:bg-zinc-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1565c0]/30"
            aria-label="{{ __('patient.appointments.back_aria') }}"
        >
            <flux:icon name="chevron-left" variant="outline" class="size-6 rtl:rotate-180" />
        </a>
        <h1 class="min-w-0 truncate text-xl font-bold text-[#1565c0] sm:text-2xl">
            {{ __('patient.appointments.title') }}
        </h1>
    </header>

    <div class="mt-2">
        <p class="text-sm text-zinc-500">
            {{ __('patient.appointments.tabs_aria') }}
        </p>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-2.5 lg:max-w-xl">
        <button
            type="button"
            wire:click="selectTab('ongoing')"
            class="rounded-xl border p-3 text-start shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1565c0]/30 {{ $tab === 'ongoing' ? 'border-[#1565c0] bg-[#1565c0]/5' : 'border-zinc-200 bg-white hover:border-[#1565c0]/35' }}"
        >
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('patient.appointments.tab_ongoing') }}</p>
            <p class="mt-1 text-2xl font-bold text-[#1565c0]">{{ $this->tabCounts['ongoing'] ?? 0 }}</p>
        </button>
        <button
            type="button"
            wire:click="selectTab('completed')"
            class="rounded-xl border p-3 text-start shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1565c0]/30 {{ $tab === 'completed' ? 'border-[#1565c0] bg-[#1565c0]/5' : 'border-zinc-200 bg-white hover:border-[#1565c0]/35' }}"
        >
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('patient.appointments.tab_completed') }}</p>
            <p class="mt-1 text-2xl font-bold text-[#1565c0]">{{ $this->tabCounts['completed'] ?? 0 }}</p>
        </button>
    </div>

    <div class="mt-3 flex flex-col items-stretch gap-2.5 sm:mt-4 sm:flex-row sm:flex-wrap sm:items-center">
        @php
            $tabActive = 'rounded-lg border border-[#1565c0] bg-[#1565c0] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition outline-none focus-visible:ring-2 focus-visible:ring-[#1565c0]/40';
            $tabInactive = 'rounded-lg border border-zinc-200 bg-white px-4 py-2.5 text-sm font-semibold text-[#1565c0] shadow-sm transition outline-none hover:border-[#1565c0]/35 focus-visible:ring-2 focus-visible:ring-[#1565c0]/30';
        @endphp

        <div
            class="flex w-full gap-2 sm:w-auto sm:max-w-full sm:flex-wrap"
            role="tablist"
            aria-label="{{ __('patient.appointments.tabs_aria') }}"
        >
            <flux:button
                type="button"
                role="tab"
                wire:click="selectTab('ongoing')"
                :aria-selected="$tab === 'ongoing'"
                class="min-h-11 shrink-0 {{ $tab === 'ongoing' ? $tabActive : $tabInactive }}"
            >
                {{ __('patient.appointments.tab_ongoing') }}
            </flux:button>
            <flux:button
                type="button"
                role="tab"
                wire:click="selectTab('completed')"
                :aria-selected="$tab === 'completed'"
                class="min-h-11 shrink-0 {{ $tab === 'completed' ? $tabActive : $tabInactive }}"
            >
                {{ __('patient.appointments.tab_completed') }}
            </flux:button>
        </div>

        <a
            href="{{ route('patient.schedule.filter') }}"
            wire:navigate
            role="button"
            class="{{ $tabInactive }} inline-flex min-h-11 w-full items-center justify-center text-center no-underline sm:w-auto sm:min-w-[14rem]"
        >
            {{ __('patient.appointments.book_new') }}
        </a>
    </div>

    @if ($this->appointments->isEmpty())
        <section class="mt-10 flex flex-col items-center pb-8 text-center sm:mt-12" role="tabpanel" aria-live="polite">
            @include('partials.patient-empty-record-illustration')
            <p class="mt-8 text-base font-medium text-zinc-400 sm:text-lg">
                {{ __('patient.menu.no_record_found') }}
            </p>
        </section>
    @else
        <section class="mt-5 grid grid-cols-1 gap-3 lg:grid-cols-2">
            @foreach ($this->appointments as $appointment)
                <article class="h-full rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm transition hover:shadow-md">
                    <div class="flex items-start gap-3">
                        <div class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-[#1565c0]/10 text-sm font-semibold text-[#1565c0]">
                            {{ \Illuminate\Support\Str::of((string) ($appointment->doctor?->displayName() ?: 'DR'))->explode(' ')->filter()->take(2)->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->implode('') }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <h2 class="truncate text-sm font-semibold text-zinc-900 sm:text-base">
                                    {{ $appointment->doctor?->displayName() ?: __('patient.appointments.title') }}
                                </h2>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $this->statusBadgeClasses((string) $appointment->status) }}">
                                    {{ $this->statusLabel((string) $appointment->status) }}
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-1 gap-2 text-xs text-zinc-600 sm:grid-cols-2">
                                <div class="inline-flex items-center gap-1.5">
                                    <flux:icon name="calendar-days" variant="mini" class="size-4 text-zinc-400" />
                                    <span>{{ $this->formattedSessionDate($appointment) }}</span>
                                </div>
                                <div class="inline-flex items-center gap-1.5">
                                    <flux:icon name="clock" variant="mini" class="size-4 text-zinc-400" />
                                    <span>{{ $this->formattedSessionTime($appointment) }}</span>
                                </div>
                            </div>

                            <div class="mt-2 inline-flex items-center gap-1.5 text-xs text-zinc-500">
                                <flux:icon name="user" variant="mini" class="size-4 text-zinc-400" />
                                <span class="truncate">{{ $appointment->patient_name }}</span>
                            </div>

                            @if (in_array($appointment->status, ['new', 'in_process'], true))
                                <div class="mt-3">
                                    <a
                                        href="{{ route('patient.appointments.conversation', ['appointment' => $appointment->id]) }}"
                                        wire:navigate
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-[#1565c0]/30 bg-[#1565c0]/5 px-3 py-1.5 text-xs font-semibold text-[#1565c0] transition hover:bg-[#1565c0]/10"
                                    >
                                        <flux:icon name="chat-bubble-left-right" variant="mini" class="size-4" />
                                        {{ __('patient.appointments.start_session') }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if (filled($appointment->appointment_number))
                        <div class="mt-3 border-t border-zinc-100 pt-3 text-[11px] text-zinc-400">
                            #{{ $appointment->appointment_number }}
                        </div>
                    @endif
                </article>
            @endforeach
        </section>

        @if ($this->appointments->hasPages())
            <div class="mt-4 rounded-xl border border-zinc-200/80 bg-white px-3 py-2 shadow-sm">
                {{ $this->appointments->links() }}
            </div>
        @endif
    @endif

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
