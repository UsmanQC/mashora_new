<?php

use App\Models\Appointment;
use App\Models\User;
use App\Services\AppointmentMissedService;
use App\Services\AppointmentWalletService;
use App\Services\PatientMissedAppointmentService;
use Carbon\Carbon;
use Flux\Flux;
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
            'missed' => ['not_attended'],
            'cancelled' => ['cancelled'],
        ];
    }

    public function mount(): void
    {
        if (! array_key_exists($this->tab, $this->tabStatuses())) {
            $this->tab = 'ongoing';
        }

        app(AppointmentMissedService::class)->processDueMissedAppointments();
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
        $query = $this->baseQuery()
            ->whereIn('status', $this->tabStatuses()[$this->tab]);

        if (in_array($this->tab, ['ongoing', 'rescheduled'], true)) {
            $query->orderBy('appointment_date')->orderBy('start_time');
        } else {
            $query->orderByDesc('appointment_date')->orderByDesc('start_time');
        }

        return $query->paginate(10);
    }

    /**
     * @return Collection<string, int>
     */
    public function getTabCountsProperty(): Collection
    {
        $counts = $this->baseQuery()
            ->whereIn('status', ['new', 'in_process', 'pending_follow_up', 'rescheduled', 'completed', 'cancelled', 'not_attended'])
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count);

        return collect([
            'ongoing' => (int) ($counts['new'] ?? 0) + (int) ($counts['in_process'] ?? 0) + (int) ($counts['pending_follow_up'] ?? 0),
            'rescheduled' => (int) ($counts['rescheduled'] ?? 0),
            'completed' => (int) ($counts['completed'] ?? 0),
            'missed' => (int) ($counts['not_attended'] ?? 0),
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
            'not_attended' => __('patient.appointments.status_missed'),
            default => str_replace('_', ' ', $status),
        };
    }

    public function statusLabelFor(Appointment $appointment): string
    {
        if ($appointment->is_follow_up) {
            return $appointment->isPendingFollowUp()
                ? __('patient.follow_up.badge')
                : __('patient.follow_up.confirmed_badge');
        }

        return $this->statusLabel((string) $appointment->status);
    }

    public function tabHeading(): string
    {
        return match ($this->tab) {
            'ongoing' => __('patient.appointments.tab_ongoing'),
            'rescheduled' => __('patient.appointments.tab_rescheduled'),
            'completed' => __('patient.appointments.tab_completed'),
            'missed' => __('patient.appointments.tab_missed'),
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
            'missed' => __('patient.appointments.empty_missed'),
            'cancelled' => __('patient.appointments.empty_cancelled'),
            default => __('patient.menu.no_record_found'),
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
            'not_attended' => 'bg-orange-100 text-orange-800',
            default => 'bg-zinc-100 text-zinc-700',
        };
    }

    public function statusBadgeClassesFor(Appointment $appointment): string
    {
        if ($appointment->is_follow_up) {
            return 'bg-violet-100 text-violet-700';
        }

        return $this->statusBadgeClasses((string) $appointment->status);
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

    public function shouldShowStartTimer(Appointment $appointment): bool
    {
        if (! in_array($appointment->status, ['new', 'rescheduled'], true)) {
            return false;
        }

        return $appointment->sessionStartsAt() !== null;
    }

    public function sessionStartsAtIso(Appointment $appointment): ?string
    {
        return $appointment->sessionStartsAt()?->toIso8601String();
    }

    public function canOpenChat(Appointment $appointment): bool
    {
        return $appointment->isChatOpen();
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

    public function canResolveMissed(Appointment $appointment): bool
    {
        return app(PatientMissedAppointmentService::class)->canResolve($appointment);
    }

    public function hasMissedRefund(Appointment $appointment): bool
    {
        return app(AppointmentWalletService::class)->hasRefunded($appointment);
    }

    public function refundMissed(int $appointmentId): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $appointment = $this->baseQuery()->findOrFail($appointmentId);

        app(PatientMissedAppointmentService::class)->refund($user, $appointment);

        Flux::toast(
            variant: 'success',
            text: __('patient.missed.refund_success', [
                'amount' => number_format((float) $appointment->total, 2),
            ]),
        );
    }
}; ?>

<div class="space-y-5">
    @if (filled(config('broadcasting.connections.pusher.key')) && config('broadcasting.default') !== 'pusher')
        <flux:callout variant="warning" icon="exclamation-triangle" class="border-amber-200">
            {{ __('patient.appointments.realtime_misconfigured') }}
        </flux:callout>
    @endif

    <div id="patient-call-join-banner" class="hidden">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p id="patient-call-join-text" class="text-sm font-medium text-emerald-900"></p>
                <a
                    id="patient-call-join-now"
                    href="#"
                    wire:navigate
                    class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                >
                    {{ __('patient.appointments.join_call') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Page header --}}
    <div class="border-b border-zinc-200/80 bg-white px-4 py-5 sm:px-6">
        <div class="flex w-full items-start justify-between gap-4">
            <div class="min-w-0">
                <flux:heading size="xl" class="font-semibold text-[#10B981]">{{ __('patient.appointments.title') }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500">{{ __('patient.appointments.subtitle') }}</flux:text>
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
    </div>

    <div class="space-y-5 lg:space-y-6">
        {{-- Book CTA — soft vertical 50/50 (top + bottom) --}}
        <a
            href="{{ route('patient.schedule.filter') }}"
            wire:navigate
            class="grid min-h-[11rem] grid-rows-2 overflow-hidden rounded-2xl border border-emerald-100/90 bg-white shadow-sm transition hover:border-emerald-200 hover:shadow-md sm:min-h-[10.5rem]"
        >
            <span class="flex items-center gap-4 bg-emerald-50/70 p-4 sm:p-5 lg:gap-5">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#10B981]/12 text-[#10B981] lg:size-12">
                    <flux:icon name="calendar-days" variant="mini" class="size-5 lg:size-6" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-zinc-900 sm:text-base">{{ __('patient.appointments.book_card_title') }}</span>
                    <span class="mt-0.5 block text-xs text-zinc-600 sm:text-sm">{{ __('patient.appointments.book_card_sub') }}</span>
                </span>
            </span>
            <span class="flex items-center justify-between gap-3 border-t border-emerald-100/90 bg-white px-4 py-4 sm:px-5 sm:py-5">
                <span class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-4 py-2 text-sm font-semibold text-[#10B981] ring-1 ring-emerald-100">
                    <flux:icon name="plus" variant="mini" class="size-4" />
                    {{ __('patient.appointments.book_new') }}
                </span>
                <flux:icon name="chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}" variant="mini" class="size-5 shrink-0 text-[#10B981]/70 rtl:rotate-180" />
            </span>
        </a>

        {{-- Tab filters --}}
        <div
            class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5 sm:gap-2.5"
            role="tablist"
            aria-label="{{ __('patient.appointments.tabs_aria') }}"
        >
            @foreach ([
                'ongoing' => __('patient.appointments.tab_ongoing'),
                'rescheduled' => __('patient.appointments.tab_rescheduled'),
                'completed' => __('patient.appointments.tab_completed'),
                'missed' => __('patient.appointments.tab_missed'),
                'cancelled' => __('patient.appointments.tab_cancelled'),
            ] as $tabKey => $tabLabel)
                @php($isActiveTab = $tab === $tabKey)
                @php($tabCount = $this->tabCounts[$tabKey] ?? 0)
                <button
                    type="button"
                    role="tab"
                    wire:click="selectTab('{{ $tabKey }}')"
                    wire:loading.attr="disabled"
                    aria-selected="{{ $isActiveTab ? 'true' : 'false' }}"
                    @class([
                        'group relative flex min-h-[5.25rem] flex-col items-center justify-center gap-1.5 rounded-xl border px-3 py-3 text-center transition-all duration-200 sm:min-h-[5.75rem] sm:px-4 sm:py-3.5',
                        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#10B981]/40 focus-visible:ring-offset-2',
                        'cursor-pointer disabled:cursor-wait disabled:opacity-70',
                        $isActiveTab
                            ? 'border-[#10B981] bg-[#10B981] text-white shadow-lg shadow-[#10B981]/25'
                            : 'border-zinc-200 bg-white text-zinc-700 shadow-sm hover:-translate-y-0.5 hover:border-[#10B981]/35 hover:bg-[#10B981]/[0.04] hover:shadow-md active:translate-y-0',
                    ])
                >
                    <span @class([
                        'inline-flex min-h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-xl font-bold tabular-nums leading-none sm:text-2xl',
                        $isActiveTab
                            ? 'bg-white/20 text-white'
                            : 'bg-zinc-100 text-zinc-800 group-hover:bg-[#10B981]/10 group-hover:text-[#10B981]',
                    ])>
                        {{ $tabCount }}
                    </span>
                    <span @class([
                        'text-xs font-semibold leading-tight sm:text-sm',
                        $isActiveTab ? 'text-white' : 'text-zinc-600 group-hover:text-[#10B981]',
                    ])>
                        {{ $tabLabel }}
                    </span>
                </button>
            @endforeach
        </div>

        {{-- Appointments list --}}
        <section role="tabpanel" aria-live="polite">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-2 border-b border-zinc-200/80 pb-3">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900">{{ $this->tabHeading() }}</h2>
                    <p class="text-sm text-zinc-500">{{ __('patient.appointments.list_heading') }}</p>
                </div>
                <span class="rounded-full bg-[#10B981]/10 px-3 py-1 text-xs font-semibold text-[#10B981]">
                    {{ trans_choice('patient.appointments.sessions_count', $this->tabCounts[$tab] ?? 0, ['count' => $this->tabCounts[$tab] ?? 0]) }}
                </span>
            </div>

            @if ($this->appointments->isEmpty())
                <div class="flex flex-col items-center rounded-2xl bg-white px-6 py-14 text-center shadow-sm ring-1 ring-zinc-200/90">
                    @include('partials.patient-empty-record-illustration')
                    <p class="mt-5 max-w-xs text-sm text-zinc-500">{{ $this->tabEmptyMessage() }}</p>
                    @if ($tab === 'ongoing')
                        <flux:button
                            :href="route('patient.schedule.filter')"
                            wire:navigate
                            variant="primary"
                            class="mt-5 !bg-[#10B981] !text-white"
                        >
                            {{ __('patient.appointments.book_new') }}
                        </flux:button>
                    @endif
                </div>
            @else
                <div class="grid gap-4 lg:grid-cols-2 lg:gap-5">
                    @foreach ($this->appointments as $appointment)
                        @include('partials.patient-appointment-card', ['component' => $this, 'appointment' => $appointment])
                    @endforeach
                </div>

                @if ($this->appointments->hasPages())
                    <div class="pt-2">
                        {{ $this->appointments->links() }}
                    </div>
                @endif
            @endif
        </section>
    </div>

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
        data-label-call="{{ __('patient.appointments.join_session') }}"
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
                        {{ __('patient.appointments.join_session') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    @include('partials.realtime-call-alerts')
    <script data-navigate-once>
        function appointmentStartTimer(isoStart) {
            return {
                label: '',
                ariaLabel: '',
                showPrefix: true,
                interval: null,
                labels: {
                    ready: @json(__('patient.appointments.ready_to_start')),
                    startsIn: @json(__('patient.appointments.starts_in')),
                },
                start() {
                    this.tick();
                    this.interval = setInterval(() => this.tick(), 1000);
                },
                tick() {
                    if (! isoStart) {
                        this.label = '';
                        this.ariaLabel = '';
                        this.showPrefix = true;

                        return;
                    }

                    const target = new Date(isoStart);
                    const diff = target.getTime() - Date.now();

                    if (diff <= 0) {
                        this.label = this.labels.ready;
                        this.ariaLabel = this.labels.ready;
                        this.showPrefix = false;

                        if (this.interval) {
                            clearInterval(this.interval);
                            this.interval = null;
                        }

                        return;
                    }

                    this.showPrefix = true;

                    const totalSeconds = Math.floor(diff / 1000);
                    const days = Math.floor(totalSeconds / 86400);
                    const hours = Math.floor((totalSeconds % 86400) / 3600);
                    const minutes = Math.floor((totalSeconds % 3600) / 60);
                    const seconds = totalSeconds % 60;

                    let timeLabel;

                    if (days > 0) {
                        timeLabel = `${days}d ${hours}h`;
                    } else if (hours > 0) {
                        timeLabel = `${hours}h ${String(minutes).padStart(2, '0')}m`;
                    } else {
                        timeLabel = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                    }

                    this.label = timeLabel;
                    this.ariaLabel = this.labels.startsIn.replace(':time', timeLabel);
                },
            };
        }
    </script>
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

            pusher.connection.bind('error', (error) => {
                console.error('Pusher connection error', error);
            });

            const incomingCallTitle = @js(__('patient.appointments.incoming_call_title'));
            const incomingVideoLabel = @js(__('patient.appointments.incoming_video'));
            const incomingVoiceLabel = @js(__('patient.appointments.incoming_voice'));

            const showJoin = (appointmentId, options = {}) => {
                if (!banner || !text) return;
                const label = options.label || labelCall;
                text.textContent = label;
                banner.classList.remove('hidden');
                currentAppointmentId = Number(appointmentId) || 0;

                window.MashoraRealtimeAlerts?.playIncomingRing();
                window.MashoraRealtimeAlerts?.showDesktopNotification(incomingCallTitle, label);
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
                const cfg = (payload && payload.agora_app_id) ? payload : await fetchAgoraConfig(appointmentId);
                if (!cfg) {
                    window.location.href = joinBase.replace('__ID__', String(appointmentId));
                    return;
                }

                window.MashoraRealtimeAlerts?.stopIncomingRing();

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

            joinNowBtn?.addEventListener('click', async (event) => {
                const appointmentId = currentAppointmentId;
                if (!appointmentId) return;

                const payload = payloadByAppointment.get(appointmentId);
                if (payload && payload.agora_app_id) {
                    event.preventDefault();
                    await joinInlineCall(
                        appointmentId,
                        payload,
                        false,
                        payload.call_type === 'audio' ? 'audio' : 'video',
                    );
                    return;
                }

                joinNowBtn.href = joinBase.replace('__ID__', String(appointmentId));
            });

            endBtn?.addEventListener('click', () => {
                leaveInlineCall().catch(() => {});
            });

            ids.forEach((id) => {
                const appointmentId = Number(id);
                if (!appointmentId) return;
                const channel = pusher.subscribe('private-appointment.' + appointmentId);
                channel.bind('pusher:subscription_error', (error) => {
                    console.error('Pusher appointment channel error', error);
                });
                channel.bind('session.started', (payload) => showJoin(payload?.appointment_id || appointmentId));
                channel.bind('call.incoming', (payload) => {
                    const incomingAppointmentId = Number(payload?.appointment_id || appointmentId);
                    payloadByAppointment.set(incomingAppointmentId, payload || null);
                    const label = payload?.call_type === 'video' ? incomingVideoLabel : incomingVoiceLabel;
                    showJoin(incomingAppointmentId, { label });
                });
            });

            if (patientId > 0) {
                const patientChannel = pusher.subscribe('private-patient.' + patientId);
                patientChannel.bind('pusher:subscription_error', (error) => {
                    console.error('Pusher patient channel error', error);
                });
                patientChannel.bind('session.join-requested', (payload) => {
                    const appointmentId = Number(payload?.appointment_id || 0);
                    if (!appointmentId) return;
                    payloadByAppointment.set(appointmentId, payload || null);
                    const label = payload?.call_type === 'video' ? incomingVideoLabel : incomingVoiceLabel;
                    showJoin(appointmentId, { label });
                });
            }
        }

        document.addEventListener('DOMContentLoaded', initPatientAppointmentsRealtime);
        document.addEventListener('livewire:navigated', initPatientAppointmentsRealtime);
        initPatientAppointmentsRealtime();
    </script>
@endpush
