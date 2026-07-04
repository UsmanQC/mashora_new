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
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::patient')] #[Title('Appointments')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = 'ongoing';

    public string $mobileSegment = 'upcoming';

    /**
     * @return array<string, list<string>>
     */
    protected function mobileSegmentStatuses(): array
    {
        return [
            'upcoming' => ['new', 'in_process', 'pending_follow_up', 'rescheduled'],
            'previous' => ['completed', 'not_attended', 'cancelled'],
        ];
    }

    public function selectMobileSegment(string $segment): void
    {
        if (! array_key_exists($segment, $this->mobileSegmentStatuses())) {
            return;
        }

        $this->mobileSegment = $segment;
        $this->resetPage();
    }

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
            ->with([
                'doctor:id,name,name_ar,profile_photo_path',
                'doctor.specialities:id,title,title_ar',
            ])
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

    public function getMobileAppointmentsProperty(): LengthAwarePaginator
    {
        $query = $this->baseQuery()
            ->whereIn('status', $this->mobileSegmentStatuses()[$this->mobileSegment]);

        if ($this->mobileSegment === 'upcoming') {
            $query->orderBy('appointment_date')->orderBy('start_time');
        } else {
            $query->orderByDesc('appointment_date')->orderByDesc('start_time');
        }

        return $query->paginate(10);
    }

    /**
     * @return Collection<string, int>
     */
    public function getMobileSegmentCountsProperty(): Collection
    {
        $counts = $this->baseQuery()
            ->whereIn('status', array_merge(...array_values($this->mobileSegmentStatuses())))
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count);

        return collect([
            'upcoming' => collect($this->mobileSegmentStatuses()['upcoming'])
                ->sum(fn (string $status): int => (int) ($counts[$status] ?? 0)),
            'previous' => collect($this->mobileSegmentStatuses()['previous'])
                ->sum(fn (string $status): int => (int) ($counts[$status] ?? 0)),
        ]);
    }

    public function headerDateLabel(): string
    {
        return now()->locale(app()->getLocale())->translatedFormat('j F Y');
    }

    public function headerSubtitle(): string
    {
        $count = (int) ($this->mobileSegmentCounts['upcoming'] ?? 0);

        return trans_choice('patient.appointments.luxury.header_subtitle', $count, [
            'count' => $count,
            'date' => $this->headerDateLabel(),
        ]);
    }

    public function profilePhotoUrl(): ?string
    {
        $user = Auth::user();

        if ($user === null || ! filled($user->profile_photo_path)) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url((string) $user->profile_photo_path);
    }

    public function doctorSpecialtyLabel(Appointment $appointment): string
    {
        $speciality = $appointment->doctor?->specialities?->first();

        if ($speciality === null) {
            return __('patient.appointments.specialist_label');
        }

        if (app()->getLocale() === 'ar' && filled($speciality->title_ar)) {
            return (string) $speciality->title_ar;
        }

        return (string) ($speciality->title ?? $speciality->title_ar ?? __('patient.appointments.specialist_label'));
    }

    public function doctorPhotoUrl(Appointment $appointment): ?string
    {
        return $appointment->doctor?->profilePhotoUrl();
    }

    public function luxuryStatusLabel(Appointment $appointment): string
    {
        if (in_array($appointment->status, ['new', 'rescheduled'], true) && ! $appointment->is_follow_up) {
            return __('patient.appointments.luxury.status_confirmed');
        }

        return $this->statusLabelFor($appointment);
    }

    public function luxuryStatusBadgeClasses(Appointment $appointment): string
    {
        if (in_array($appointment->status, ['new', 'rescheduled'], true) && ! $appointment->is_follow_up) {
            return 'bg-emerald-50 text-emerald-600';
        }

        return match ((string) $appointment->status) {
            'in_process' => 'bg-amber-50 text-amber-600',
            'pending_follow_up' => 'bg-violet-50 text-violet-600',
            'completed' => 'bg-slate-100 text-slate-600',
            'not_attended' => 'bg-orange-50 text-orange-600',
            'cancelled' => 'bg-rose-50 text-rose-600',
            default => 'bg-emerald-50 text-emerald-600',
        };
    }

    public function sessionTypeLabel(Appointment $appointment): string
    {
        if ($appointment->appointment_type === 'instant') {
            return __('patient.appointments.luxury.session_instant');
        }

        return __('patient.appointments.luxury.session_video');
    }

    public function formattedLuxurySessionSchedule(Appointment $appointment): string
    {
        $startsAt = $appointment->sessionStartsAt();

        if ($startsAt === null) {
            return trim($this->formattedSessionDate($appointment).'، '.$this->formattedSessionTime($appointment), ' ،');
        }

        $startsAt = $startsAt->locale(app()->getLocale());

        $dayLabel = $startsAt->isToday()
            ? __('patient.today')
            : ($startsAt->isTomorrow()
                ? __('patient.appointments.luxury.tomorrow')
                : $startsAt->translatedFormat('d M'));

        return $dayLabel.(app()->getLocale() === 'ar' ? '، ' : ', ').$startsAt->translatedFormat('g:i A');
    }

    public function appointmentCardUrl(Appointment $appointment): ?string
    {
        if ($appointment->status === 'in_process' && $appointment->allowsPatientCalls()) {
            return route('patient.appointments.conversation', ['appointment' => $appointment->id]);
        }

        if ($this->canOpenChat($appointment)) {
            return route('patient.appointments.conversation', ['appointment' => $appointment->id]);
        }

        if ($this->canResolveMissed($appointment)) {
            return route('patient.appointments.missed-reschedule', ['appointment' => $appointment->id]);
        }

        return null;
    }

    public function mobileEmptyMessage(): string
    {
        return $this->mobileSegment === 'upcoming'
            ? __('patient.appointments.luxury.empty_upcoming')
            : __('patient.appointments.luxury.empty_previous');
    }

    #[On('patient-appointment-session-started')]
    public function onPatientAppointmentSessionStarted(): void
    {
        unset($this->appointments, $this->tabCounts, $this->mobileAppointments, $this->mobileSegmentCounts);
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
        if ($appointment->isPatientRefunded()) {
            return __('patient.appointments.status_refunded');
        }

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
        if ($appointment->isPatientRefunded()) {
            return 'bg-emerald-100 text-emerald-800';
        }

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

    public bool $showRefundModal = false;

    public ?int $refundAppointmentId = null;

    public function promptRefundMissed(int $appointmentId): void
    {
        $appointment = $this->baseQuery()->whereKey($appointmentId)->first();

        if (! $appointment instanceof Appointment) {
            abort(404);
        }

        if (! app(PatientMissedAppointmentService::class)->canResolve($appointment)) {
            Flux::toast(variant: 'warning', text: __('patient.missed.not_eligible'));

            return;
        }

        $this->refundAppointmentId = $appointment->id;
        $this->showRefundModal = true;
    }

    public function dismissRefundMissedModal(): void
    {
        $this->showRefundModal = false;
        $this->refundAppointmentId = null;
    }

    public function confirmRefundMissed(): void
    {
        if ($this->refundAppointmentId === null) {
            return;
        }

        $appointmentId = $this->refundAppointmentId;
        $this->dismissRefundMissedModal();
        $this->refundMissed($appointmentId);
    }

    public function getPendingRefundAppointmentProperty(): ?Appointment
    {
        if ($this->refundAppointmentId === null) {
            return null;
        }

        return $this->baseQuery()->whereKey($this->refundAppointmentId)->first();
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

<div>
    @include('partials.patient-luxury-appointments-mobile')

    <div id="patient-appointments-root" class="hidden space-y-5 sm:block">
    @if (filled(config('broadcasting.connections.pusher.key')) && config('broadcasting.default') !== 'pusher')
        <flux:callout variant="warning" icon="exclamation-triangle" class="border-amber-200">
            {{ __('patient.appointments.realtime_misconfigured') }}
        </flux:callout>
    @endif

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
    </div>

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

            @if ($this->pendingRefundAppointment instanceof \App\Models\Appointment)
                <div class="mt-5 rounded-xl border border-zinc-200/90 bg-zinc-50 px-4 py-3 text-sm">
                    <p class="font-semibold text-zinc-900">{{ $this->pendingRefundAppointment->doctor?->displayName() }}</p>
                    <p class="mt-1 tabular-nums text-zinc-600">
                        {{ $this->formattedSessionDate($this->pendingRefundAppointment) }}
                        ·
                        {{ $this->formattedSessionTime($this->pendingRefundAppointment) }}
                    </p>
                    @if ((float) $this->pendingRefundAppointment->total > 0)
                        <p class="mt-2 text-xs font-medium text-[#047857]">
                            {{ __('patient.missed.refund_modal.refund_note', ['amount' => number_format((float) $this->pendingRefundAppointment->total, 2)]) }}
                        </p>
                    @endif
                </div>
            @endif

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <flux:button
                    type="button"
                    variant="ghost"
                    class="w-full sm:w-auto"
                    wire:click="dismissRefundMissedModal"
                >
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
    ></div>

    <div id="patient-inline-call-overlay" class="video-call-overlay fixed inset-0 z-[210] hidden" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-zinc-950/95 backdrop-blur-md" aria-hidden="true"></div>
        <div class="relative flex h-full min-h-0 flex-col">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-white/10 bg-zinc-900/95 px-4 py-3 text-white">
                <div class="min-w-0">
                    <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-emerald-300">
                        <span class="relative flex size-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#10B981] opacity-60"></span>
                            <span class="relative inline-flex size-2 rounded-full bg-[#10B981]"></span>
                        </span>
                        {{ __('patient.appointments.call_in_progress') }}
                    </p>
                    <p id="patient-inline-call-state" class="truncate text-sm font-semibold text-white">{{ __('patient.appointments.session_started_join_now') }}</p>
                </div>
                <button type="button" id="patient-inline-call-end" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-rose-600 px-3 py-2 text-xs font-semibold text-white shadow-lg shadow-rose-900/35 transition hover:bg-rose-500">
                    <flux:icon name="x-mark" variant="mini" class="size-4" />
                    {{ __('patient.appointments.end_call') }}
                </button>
            </div>

            <div class="relative min-h-0 flex-1 bg-black">
                <div id="patient-inline-call-remote" class="absolute inset-0 h-full w-full"></div>
                <div class="absolute end-3 top-3 z-10 w-28 overflow-hidden rounded-2xl border border-white/20 bg-zinc-900/90 shadow-2xl ring-1 ring-white/10 sm:w-36">
                    <div id="patient-inline-call-local" class="aspect-video w-full bg-zinc-800"></div>
                </div>
            </div>

            <div class="shrink-0 border-t border-white/10 bg-zinc-900/95 px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
                <div class="mb-3 rounded-xl border border-white/10 bg-zinc-800/80 px-3 py-2 text-xs text-zinc-200">
                    <p id="patient-inline-doctor-name" class="font-semibold text-white">—</p>
                    <p id="patient-inline-session-time" class="mt-0.5 text-zinc-400">—</p>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" id="patient-inline-video" class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-xl bg-white/10 px-2 py-2 text-[11px] font-semibold text-white transition hover:bg-white/20">
                        <flux:icon name="video-camera" variant="mini" class="size-4" />
                        {{ __('patient.appointments.video_call') }}
                    </button>
                    <button type="button" id="patient-inline-audio" class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-xl bg-white/10 px-2 py-2 text-[11px] font-semibold text-white transition hover:bg-white/20">
                        <flux:icon name="phone" variant="mini" class="size-4" />
                        {{ __('patient.appointments.voice_call') }}
                    </button>
                    <a id="patient-inline-open-chat" href="#" wire:navigate class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-xl border border-[#10B981]/40 bg-[#10B981]/20 px-2 py-2 text-[11px] font-semibold text-emerald-100 transition hover:bg-[#10B981]/30">
                        <flux:icon name="chat-bubble-left-right" variant="mini" class="size-4" />
                        {{ __('patient.appointments.open_chat') }}
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
    <script>
        function teardownPatientAppointmentsRealtime() {
            if (typeof window.__patientAppointmentsTeardown === 'function') {
                window.__patientAppointmentsTeardown();
                window.__patientAppointmentsTeardown = null;
            }
        }

        function initPatientAppointmentsRealtime() {
            const boot = document.getElementById('patient-appointments-realtime-bootstrap');
            if (!boot) {
                return;
            }

            teardownPatientAppointmentsRealtime();

            if (boot.dataset.initialized === '1') {
                return;
            }

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
            const overlay = document.getElementById('patient-inline-call-overlay');
            const remoteWrap = document.getElementById('patient-inline-call-remote');
            const localWrap = document.getElementById('patient-inline-call-local');
            const endBtn = document.getElementById('patient-inline-call-end');
            const openChat = document.getElementById('patient-inline-open-chat');
            const callState = document.getElementById('patient-inline-call-state');

            if (overlay && overlay.parentElement !== document.body) {
                document.body.appendChild(overlay);
            }

            function replayInlineVideoTracks() {
                if (localVideo) {
                    try {
                        localVideo.stop();
                        localVideo.play('patient-inline-call-local');
                    } catch (error) {
                        console.error('Failed to replay inline local video', error);
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
                        user.videoTrack.play('patient-inline-call-remote');
                    } catch (error) {
                        console.error('Failed to replay inline remote video', error);
                    }
                });
            }
            const payloadByAppointment = new Map();
            let currentAppointmentId = 0;
            let agoraClient = null;
            let localAudio = null;
            let localVideo = null;
            let inlineJoinInProgress = false;
            const subscribedChannels = [];

            const pusher = window.MashoraPatientPusher.acquire({
                key: pusherKey,
                cluster: pusherCluster,
                csrf,
            });

            if (!pusher) {
                return;
            }

            function refreshAppointmentsList() {
                if (window.Livewire) {
                    Livewire.dispatch('patient-appointment-session-started');
                }
            }

            function showSessionJoin(appointmentId) {
                const id = Number(appointmentId) || 0;
                if (!id) {
                    return;
                }

                currentAppointmentId = id;
                refreshAppointmentsList();
            }

            function showIncomingCallJoin(appointmentId) {
                const id = Number(appointmentId) || 0;
                if (!id) {
                    return;
                }

                currentAppointmentId = id;
                refreshAppointmentsList();
            }

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

            async function ensureAgoraSdk(timeoutMs = 12000) {
                if (window.AgoraRTC) {
                    return window.AgoraRTC;
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
                            reject(new Error('Agora SDK missing'));

                            return;
                        }

                        window.setTimeout(tick, 50);
                    };

                    tick();
                });
            }

            async function tryInlineCameraTrack() {
                try {
                    return await AgoraRTC.createCameraVideoTrack();
                } catch {
                    return null;
                }
            }

            function releaseInlineMediaTracks(micTrack, camTrack = null) {
                if (camTrack) {
                    camTrack.stop();
                    camTrack.close();
                }

                if (micTrack) {
                    micTrack.stop();
                    micTrack.close();
                }
            }

            async function joinInlineCall(appointmentId, payload = null, shouldNotify = false, callType = 'video') {
                if (!appointmentId || inlineJoinInProgress) {
                    return;
                }

                inlineJoinInProgress = true;

                const resolvedCallType = (payload?.call_type === 'audio' || callType === 'audio') ? 'audio' : 'video';
                let micTrack = null;
                let camTrack = null;

                try {
                    await ensureAgoraSdk();

                    if (resolvedCallType === 'video') {
                        micTrack = await AgoraRTC.createMicrophoneAudioTrack();
                        camTrack = await tryInlineCameraTrack();
                    } else {
                        micTrack = await AgoraRTC.createMicrophoneAudioTrack();
                    }

                    const cfg = (payload && payload.agora_app_id) ? payload : await fetchAgoraConfig(appointmentId);
                    if (!cfg) {
                        releaseInlineMediaTracks(micTrack, camTrack);
                        micTrack = null;
                        camTrack = null;
                        window.location.href = joinBase.replace('__ID__', String(appointmentId));

                        return;
                    }

                    window.MashoraRealtimeAlerts?.stopIncomingRing();

                    if (shouldNotify) {
                        await notifyDoctor(appointmentId, resolvedCallType, cfg);
                    }

                    await leaveInlineCall();

                    const client = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });
                    agoraClient = client;
                    client.on('user-published', async (user, mediaType) => {
                        await client.subscribe(user, mediaType);
                        if (mediaType === 'video') {
                            user.videoTrack.play('patient-inline-call-remote');
                        }
                        if (mediaType === 'audio') {
                            user.audioTrack.play();
                        }
                    });

                    await client.join(cfg.agora_app_id, cfg.agora_channel, cfg.agora_token || null, null);
                    localAudio = micTrack;
                    localVideo = camTrack;
                    micTrack = null;
                    camTrack = null;

                    if (localVideo) {
                        localVideo.play('patient-inline-call-local');
                        await client.publish([localAudio, localVideo]);
                    } else {
                        await client.publish([localAudio]);
                    }

                    if (callState) {
                        callState.textContent = resolvedCallType === 'video'
                            ? @js(__('patient.appointments.incoming_video'))
                            : @js(__('patient.appointments.incoming_voice'));
                    }
                    if (openChat) {
                        openChat.href = joinBase.replace('__ID__', String(appointmentId));
                    }
                    if (overlay) {
                        overlay.classList.remove('hidden');
                        window.requestAnimationFrame(() => {
                            window.requestAnimationFrame(() => {
                                replayInlineVideoTracks();
                            });
                        });
                    }
                } catch (error) {
                    console.error(error);
                    releaseInlineMediaTracks(micTrack, camTrack);
                } finally {
                    inlineJoinInProgress = false;
                }
            }

            endBtn?.addEventListener('click', () => {
                leaveInlineCall().catch(() => {});
            });

            ids.forEach((id) => {
                const appointmentId = Number(id);
                if (!appointmentId) {
                    return;
                }

                const channelName = 'private-appointment.' + appointmentId;
                subscribedChannels.push(channelName);
                const channel = window.MashoraPatientPusher.subscribe(channelName);
                if (!channel) {
                    return;
                }

                channel.bind('pusher:subscription_error', (error) => {
                    console.error('Pusher appointment channel error', error);
                });
                channel.bind('session.started', (payload) => showSessionJoin(payload?.appointment_id || appointmentId));
                channel.bind('call.incoming', (payload) => {
                    const incomingAppointmentId = Number(payload?.appointment_id || appointmentId);
                    payloadByAppointment.set(incomingAppointmentId, payload || null);
                    showIncomingCallJoin(incomingAppointmentId);
                });
            });

            if (!window.__patientAppointmentsSessionStartedHook) {
                window.__patientAppointmentsSessionStartedHook = true;

                window.addEventListener('mashora:session-started', (event) => {
                    const appointmentId = Number(event.detail?.appointment_id || 0);
                    if (!appointmentId) {
                        return;
                    }

                    showSessionJoin(appointmentId);
                });
            }

            if (!window.__patientAppointmentsIncomingCallHook) {
                window.__patientAppointmentsIncomingCallHook = true;

                window.addEventListener('mashora:incoming-call', (event) => {
                    const payload = event.detail || {};
                    const appointmentId = Number(payload.appointment_id || 0);
                    if (!appointmentId) {
                        return;
                    }

                    payloadByAppointment.set(appointmentId, payload);
                    showIncomingCallJoin(appointmentId);
                });
            }

            window.__patientAppointmentsTeardown = () => {
                leaveInlineCall().catch(() => {});
                subscribedChannels.forEach((channelName) => {
                    window.MashoraPatientPusher.unsubscribe(channelName);
                });
                window.MashoraPatientPusher.release();
                delete boot.dataset.initialized;
            };
        }

        if (!window.__patientAppointmentsNavigateHook) {
            window.__patientAppointmentsNavigateHook = true;
            document.addEventListener('livewire:navigating', teardownPatientAppointmentsRealtime);
        }

        document.addEventListener('DOMContentLoaded', initPatientAppointmentsRealtime);
        document.addEventListener('livewire:navigated', initPatientAppointmentsRealtime);
        initPatientAppointmentsRealtime();
    </script>
@endpush
