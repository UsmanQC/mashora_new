<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\AppointmentMissedService;
use App\Services\AppointmentSessionService;
use App\Services\AppointmentWalletService;
use App\Services\FollowUpAppointmentService;
use App\Services\PatientAppointmentNotifier;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::doctor')] #[Title('Appointments')] class extends Component
{
    use WithPagination;

    public function mount(): void
    {
        app(AppointmentMissedService::class)->processDueMissedAppointments();
    }

    #[Url]
    public string $status = 'all';

    /**
     * @return array<string, string>
     */
    public function statusOptions(): array
    {
        return [
            'all' => __('All'),
            'new' => __('doctor.appointment_status.new'),
            'in_process' => __('doctor.appointment_status.in_process'),
            'completed' => __('doctor.appointment_status.completed'),
            'pending_follow_up' => __('doctor.appointment_status.pending_follow_up'),
            'cancelled' => __('doctor.appointment_status.cancelled'),
            'rescheduled' => __('doctor.appointment_status.rescheduled'),
            'not_attended' => __('doctor.appointment_status.not_attended'),
        ];
    }

    protected function doctor(): Doctor
    {
        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        return $doctor;
    }

    /**
     * @return Builder<Appointment>
     */
    protected function baseAppointmentsQuery(): Builder
    {
        return Appointment::query()
            ->where('doctor_id', $this->doctor()->id);
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function getAppointmentsProperty(): LengthAwarePaginator
    {
        $query = $this->baseAppointmentsQuery()
            ->with([
                'followUps' => fn ($query) => $query
                    ->where('status', 'pending_follow_up')
                    ->latest('id'),
                'parentAppointment',
            ])
            ->when($this->status !== 'all', function (Builder $query): void {
                if ($this->status === 'pending_follow_up') {
                    $query->upcomingFollowUp();
                } elseif ($this->status === 'new') {
                    $query->where('status', 'new')->where('is_follow_up', false);
                } else {
                    $query->where('status', $this->status);
                }
            });

        if (in_array($this->status, ['new', 'in_process', 'pending_follow_up', 'rescheduled'], true)) {
            $query->orderBy('appointment_date')->orderBy('start_time');
        } else {
            $query->orderByDesc('appointment_date')->orderByDesc('start_time');
        }

        return $query->paginate(12);
    }

    public function canScheduleFollowUp(Appointment $appointment): bool
    {
        return app(FollowUpAppointmentService::class)->parentCanScheduleFollowUp($appointment);
    }

    public function pendingFollowUpFor(Appointment $appointment): ?Appointment
    {
        return app(FollowUpAppointmentService::class)->pendingFollowUpFor($appointment);
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
        return $appointment->sessionStartsAt()?->copy()->subHour()->toIso8601String();
    }

    public function canOpenSessionNow(Appointment $appointment): bool
    {
        if ((string) $appointment->status === 'in_process') {
            return true;
        }

        return app(AppointmentSessionService::class)->canDoctorStart($appointment);
    }

    public function canOpenChat(Appointment $appointment): bool
    {
        return $appointment->isDoctorChatOpen();
    }

    public function statusLabelFor(Appointment $appointment): string
    {
        if ($appointment->isPatientRefunded()) {
            return __('doctor.appointment_status.refunded');
        }

        if ($appointment->is_follow_up && $appointment->status !== 'pending_follow_up') {
            return __('doctor.appointment_status.follow_up');
        }

        return __('doctor.appointment_status.'.$appointment->status);
    }

    public function statusBadgeClassesFor(Appointment $appointment): string
    {
        if ($appointment->isPatientRefunded()) {
            return 'bg-emerald-100 text-emerald-800';
        }

        if ($appointment->is_follow_up && $appointment->status !== 'pending_follow_up') {
            return 'bg-violet-100 text-violet-700';
        }

        return match ($appointment->status) {
            'completed' => 'bg-emerald-100 text-emerald-700',
            'in_process' => 'bg-amber-100 text-amber-700',
            'new' => 'bg-sky-100 text-sky-700',
            'pending_follow_up' => 'bg-violet-100 text-violet-700',
            'cancelled' => 'bg-rose-100 text-rose-700',
            'rescheduled' => 'bg-indigo-100 text-indigo-700',
            'not_attended' => 'bg-orange-100 text-orange-800',
            default => 'bg-zinc-100 text-zinc-700',
        };
    }

    public function cancelRequiresRefund(Appointment $appointment): bool
    {
        return (float) $appointment->total > 0;
    }

    public function cancelActionLabel(Appointment $appointment): string
    {
        return $this->cancelRequiresRefund($appointment)
            ? __('doctor.appointments.cancel_refund')
            : __('doctor.appointments.cancel_appointment');
    }

    public function cancelSuccessMessage(Appointment $appointment): string
    {
        return $this->cancelRequiresRefund($appointment)
            ? __('doctor.appointments.cancel_refunded')
            : __('doctor.appointments.cancel_success');
    }

    /**
     * @return Collection<string, int>
     */
    public function getStatusCountsProperty(): Collection
    {
        $counts = $this->baseAppointmentsQuery()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count);

        return collect($this->statusOptions())
            ->except('all')
            ->mapWithKeys(fn ($_label, $status): array => [$status => (int) ($counts[$status] ?? 0)])
            ->map(function (int $count, string $status): int {
                if ($status === 'pending_follow_up') {
                    return $this->baseAppointmentsQuery()->upcomingFollowUp()->count();
                }

                if ($status === 'new') {
                    return $this->baseAppointmentsQuery()
                        ->where('status', 'new')
                        ->where('is_follow_up', false)
                        ->count();
                }

                return $count;
            });
    }

    public function filterCount(string $key): int
    {
        if ($key === 'all') {
            return (int) $this->statusCounts->sum();
        }

        return (int) ($this->statusCounts[$key] ?? 0);
    }

    /**
     * @return array<string, array{icon: string, active: string, idle: string, badge_active: string, badge_idle: string, ring: string}>
     */
    public function statusFilterMeta(): array
    {
        return [
            'all' => [
                'icon' => 'squares-2x2',
                'active' => 'border-[#047857] bg-[#047857] text-white shadow-md shadow-[#047857]/15',
                'idle' => 'border-zinc-200/90 bg-white text-zinc-600 hover:border-[#047857]/35 hover:bg-[#047857]/[0.04] hover:text-[#047857]',
                'badge_active' => 'bg-white/20 text-white',
                'badge_idle' => 'bg-zinc-100 text-zinc-600 group-hover:bg-[#047857]/10 group-hover:text-[#047857]',
                'ring' => 'ring-[#047857]/30',
            ],
            'new' => [
                'icon' => 'sparkles',
                'active' => 'border-sky-500 bg-sky-500 text-white shadow-md shadow-sky-500/15',
                'idle' => 'border-zinc-200/90 bg-white text-zinc-600 hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700',
                'badge_active' => 'bg-white/20 text-white',
                'badge_idle' => 'bg-sky-50 text-sky-700 group-hover:bg-sky-100',
                'ring' => 'ring-sky-500/30',
            ],
            'in_process' => [
                'icon' => 'clock',
                'active' => 'border-amber-500 bg-amber-500 text-white shadow-md shadow-amber-500/15',
                'idle' => 'border-zinc-200/90 bg-white text-zinc-600 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-800',
                'badge_active' => 'bg-white/20 text-white',
                'badge_idle' => 'bg-amber-50 text-amber-800 group-hover:bg-amber-100',
                'ring' => 'ring-amber-500/30',
            ],
            'completed' => [
                'icon' => 'check-circle',
                'active' => 'border-emerald-500 bg-emerald-500 text-white shadow-md shadow-emerald-500/15',
                'idle' => 'border-zinc-200/90 bg-white text-zinc-600 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800',
                'badge_active' => 'bg-white/20 text-white',
                'badge_idle' => 'bg-emerald-50 text-emerald-800 group-hover:bg-emerald-100',
                'ring' => 'ring-emerald-500/30',
            ],
            'pending_follow_up' => [
                'icon' => 'calendar-days',
                'active' => 'border-violet-500 bg-violet-500 text-white shadow-md shadow-violet-500/15',
                'idle' => 'border-zinc-200/90 bg-white text-zinc-600 hover:border-violet-300 hover:bg-violet-50 hover:text-violet-800',
                'badge_active' => 'bg-white/20 text-white',
                'badge_idle' => 'bg-violet-50 text-violet-800 group-hover:bg-violet-100',
                'ring' => 'ring-violet-500/30',
            ],
            'cancelled' => [
                'icon' => 'x-circle',
                'active' => 'border-rose-500 bg-rose-500 text-white shadow-md shadow-rose-500/15',
                'idle' => 'border-zinc-200/90 bg-white text-zinc-600 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-800',
                'badge_active' => 'bg-white/20 text-white',
                'badge_idle' => 'bg-rose-50 text-rose-800 group-hover:bg-rose-100',
                'ring' => 'ring-rose-500/30',
            ],
            'rescheduled' => [
                'icon' => 'arrow-path',
                'active' => 'border-indigo-500 bg-indigo-500 text-white shadow-md shadow-indigo-500/15',
                'idle' => 'border-zinc-200/90 bg-white text-zinc-600 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-800',
                'badge_active' => 'bg-white/20 text-white',
                'badge_idle' => 'bg-indigo-50 text-indigo-800 group-hover:bg-indigo-100',
                'ring' => 'ring-indigo-500/30',
            ],
            'not_attended' => [
                'icon' => 'user-minus',
                'active' => 'border-orange-500 bg-orange-500 text-white shadow-md shadow-orange-500/15',
                'idle' => 'border-zinc-200/90 bg-white text-zinc-600 hover:border-orange-300 hover:bg-orange-50 hover:text-orange-800',
                'badge_active' => 'bg-white/20 text-white',
                'badge_idle' => 'bg-orange-50 text-orange-800 group-hover:bg-orange-100',
                'ring' => 'ring-orange-500/30',
            ],
        ];
    }

    /** @var list<string> */
    public const CANCELLABLE_STATUSES = ['new', 'rescheduled', 'in_process'];

    public bool $showCancelModal = false;

    public ?int $cancelAppointmentId = null;

    public function promptCancelAppointment(int $appointmentId): void
    {
        $appointment = $this->baseAppointmentsQuery()->whereKey($appointmentId)->first();

        if (! $appointment instanceof Appointment) {
            abort(404);
        }

        if (! in_array($appointment->status, self::CANCELLABLE_STATUSES, true)) {
            Flux::toast(variant: 'warning', text: __('doctor.appointments.cancel_not_allowed'));

            return;
        }

        $this->cancelAppointmentId = $appointment->id;
        $this->showCancelModal = true;
    }

    public function dismissCancelAppointmentModal(): void
    {
        $this->showCancelModal = false;
        $this->cancelAppointmentId = null;
    }

    public function confirmCancelAppointment(): void
    {
        if ($this->cancelAppointmentId === null) {
            return;
        }

        $appointmentId = $this->cancelAppointmentId;
        $this->dismissCancelAppointmentModal();
        $this->cancelAppointment($appointmentId);
    }

    public function getPendingCancelAppointmentProperty(): ?Appointment
    {
        if ($this->cancelAppointmentId === null) {
            return null;
        }

        $appointment = $this->baseAppointmentsQuery()->whereKey($this->cancelAppointmentId)->first();

        return $appointment instanceof Appointment ? $appointment : null;
    }

    public function cancelAppointment(int $appointmentId): void
    {
        $appointment = $this->baseAppointmentsQuery()->whereKey($appointmentId)->first();

        if (! $appointment instanceof Appointment) {
            abort(404);
        }

        if (! in_array($appointment->status, self::CANCELLABLE_STATUSES, true)) {
            Flux::toast(variant: 'warning', text: __('doctor.appointments.cancel_not_allowed'));

            return;
        }

        $doctor = Auth::guard('doctor')->user();
        abort_unless($doctor instanceof Doctor, 403);

        DB::transaction(function () use ($appointment): void {
            $appointment->forceFill([
                'status' => 'cancelled',
                'cancel_status' => 'doctor',
            ])->save();

            app(AppointmentWalletService::class)->refundToPatient($appointment->fresh());
        });

        $appointment->refresh()->loadMissing('doctor');
        app(PatientAppointmentNotifier::class)->notifyCancelled($appointment, $doctor);

        Flux::toast(variant: 'success', text: $this->cancelSuccessMessage($appointment));
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl" class="font-semibold tracking-tight text-zinc-900">{{ __('doctor.appointments.title') }}</flux:heading>
        <span class="inline-flex items-center rounded-full bg-[#10B981]/10 px-3 py-1 text-xs font-semibold text-[#2f49ca]">
            {{ trans_choice(':count records', $this->appointments->total(), ['count' => $this->appointments->total()]) }}
        </span>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm sm:p-5">
        <div class="mb-4 flex items-center justify-between gap-3">
            <flux:text class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                {{ __('doctor.appointments.filter_by_status') }}
            </flux:text>
            @if ($status !== 'all')
                <button
                    type="button"
                    wire:click="$set('status', 'all')"
                    class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-[#047857] transition hover:text-[#2f49ca]"
                >
                    <flux:icon name="x-mark" variant="micro" class="size-3.5" />
                    {{ __('doctor.appointments.clear_filter') }}
                </button>
            @endif
        </div>

        <div
            class="grid grid-cols-2 gap-2 min-[520px]:grid-cols-3 xl:grid-cols-4"
            role="tablist"
            aria-label="{{ __('doctor.appointments.filters_aria') }}"
        >
            @foreach ($this->statusOptions() as $key => $label)
                @php
                    $meta = $this->statusFilterMeta()[$key];
                    $isActive = $status === $key;
                    $count = $this->filterCount($key);
                @endphp
                <button
                    type="button"
                    role="tab"
                    wire:click="$set('status', '{{ $key }}')"
                    wire:key="doctor-appointment-filter-{{ $key }}"
                    aria-selected="{{ $isActive ? 'true' : 'false' }}"
                    title="{{ $label }}"
                    @class([
                        'group inline-flex w-full min-w-0 items-center gap-2 rounded-xl border px-3 py-2 text-start text-sm font-semibold transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
                        $isActive ? $meta['active'].' '.$meta['ring'] : $meta['idle'].' focus-visible:ring-zinc-300/80',
                    ])
                >
                    <flux:icon
                        :name="$meta['icon']"
                        variant="mini"
                        @class([
                            'size-4 shrink-0',
                            $isActive ? 'text-current' : 'text-zinc-400 group-hover:text-current',
                        ])
                    />
                    <span class="min-w-0 flex-1 truncate">{{ $label }}</span>
                    <span @class([
                        'inline-flex shrink-0 items-center justify-center rounded-full px-1.5 py-0.5 text-[0.6875rem] font-bold tabular-nums leading-none',
                        $isActive ? $meta['badge_active'] : $meta['badge_idle'],
                    ])>
                        {{ $count }}
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    @if ($this->appointments->isEmpty())
        <div class="rounded-2xl border border-zinc-200/90 bg-white px-4 py-10 text-center shadow-sm">
            @include('partials.patient-empty-record-illustration')
            <flux:text class="mt-4 text-zinc-600">{{ __('doctor.appointments.empty') }}</flux:text>
        </div>
    @else
        <div class="overflow-x-auto rounded-2xl border border-zinc-200/90 bg-white shadow-sm lg:overflow-x-visible">
            <table class="w-full table-fixed divide-y divide-zinc-100 text-sm">
                <colgroup>
                    <col class="w-[19%]" />
                    <col class="w-[11%]" />
                    <col class="w-[11%]" />
                    <col class="w-[11%]" />
                    <col class="w-[36%]" />
                    <col class="w-[12%]" />
                </colgroup>
                <thead class="bg-zinc-50 text-xs font-semibold uppercase text-zinc-500">
                    <tr>
                        <th class="px-4 py-3 text-start align-middle">{{ __('doctor.appointments.patient') }}</th>
                        <th class="px-4 py-3 text-center align-middle whitespace-nowrap">{{ __('doctor.appointments.date') }}</th>
                        <th class="px-4 py-3 text-center align-middle whitespace-nowrap">{{ __('doctor.appointments.time') }}</th>
                        <th class="px-4 py-3 text-center align-middle whitespace-nowrap">{{ __('doctor.appointments.status') }}</th>
                        <th class="px-4 py-3 text-center align-middle whitespace-nowrap">{{ __('doctor.appointments.actions') }}</th>
                        <th class="px-4 py-3 text-center align-middle whitespace-nowrap">{{ __('doctor.appointments.starts_in_label') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($this->appointments as $row)
                        @php
                            $statusClasses = $this->statusBadgeClassesFor($row);
                            $statusLabel = $this->statusLabelFor($row);
                            $pendingFollowUp = $this->canScheduleFollowUp($row)
                                ? $this->pendingFollowUpFor($row)
                                : null;
                            $showStartTimer = $this->shouldShowStartTimer($row);
                            $hasSessionActions = in_array($row->status, ['new', 'rescheduled', 'in_process'], true);
                            $openSessionHref = route('doctor.appointments.conversation', $row);
                        @endphp
                        <tr
                            class="transition hover:bg-zinc-50/70"
                            @if ($hasSessionActions && $showStartTimer)
                                x-data="appointmentStartTimer(@js($this->sessionStartsAtIso($row)))"
                                x-init="start()"
                            @elseif ($hasSessionActions)
                                x-data="{ ready: @js($this->canOpenSessionNow($row)) }"
                            @endif
                        >
                            <td class="px-4 py-3 font-medium text-zinc-900">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold text-zinc-700">
                                        {{ \Illuminate\Support\Str::of((string) $row->patient_name)->explode(' ')->filter()->take(2)->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))->implode('') }}
                                    </span>
                                    <span class="truncate">{{ $row->patient_name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center tabular-nums text-zinc-700">{{ $row->appointment_date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-center tabular-nums text-zinc-700">{{ \Illuminate\Support\Str::limit((string) $row->start_time, 8, '') }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-middle text-center">
                                @if ($hasSessionActions)
                                    <div class="inline-flex flex-nowrap items-center justify-center gap-1.5">
                                        <template x-if="ready">
                                            <a
                                                href="{{ $openSessionHref }}"
                                                wire:navigate
                                                class="inline-flex shrink-0 items-center justify-center gap-1 rounded-lg bg-[#047857] px-2 py-1.5 text-[0.6875rem] font-semibold whitespace-nowrap text-white shadow-sm transition hover:brightness-95"
                                            >
                                                <flux:icon name="video-camera" variant="mini" class="size-3.5 shrink-0" />
                                                {{ __('doctor.appointments.open_session') }}
                                            </a>
                                        </template>
                                        <template x-if="!ready">
                                            <span
                                                class="inline-flex shrink-0 cursor-not-allowed items-center justify-center gap-1 rounded-lg bg-zinc-200 px-2 py-1.5 text-[0.6875rem] font-semibold whitespace-nowrap text-zinc-500"
                                                title="{{ __('doctor.appointments.open_session_wait') }}"
                                            >
                                                <flux:icon name="video-camera" variant="mini" class="size-3.5 shrink-0 opacity-60" />
                                                {{ __('doctor.appointments.open_session') }}
                                            </span>
                                        </template>
                                        <button
                                            type="button"
                                            wire:click="promptCancelAppointment({{ $row->id }})"
                                            class="inline-flex shrink-0 items-center justify-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2 py-1.5 text-[0.6875rem] font-semibold whitespace-nowrap text-rose-700 transition hover:bg-rose-100"
                                        >
                                            <flux:icon name="x-circle" variant="mini" class="size-3.5 shrink-0" />
                                            {{ $this->cancelActionLabel($row) }}
                                        </button>
                                    </div>
                                @elseif ($this->canScheduleFollowUp($row))
                                    <div class="mx-auto inline-flex w-full min-w-[11rem] max-w-[13rem] flex-col gap-1.5">
                                        @if ($pendingFollowUp instanceof \App\Models\Appointment)
                                            <a
                                                href="{{ route('doctor.appointments.follow-up', $row) }}"
                                                wire:navigate
                                                class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-violet-300 bg-violet-50 px-3 py-2 text-xs font-semibold text-violet-800 transition hover:bg-violet-100"
                                            >
                                                <flux:icon name="clock" variant="mini" class="size-4 shrink-0" />
                                                {{ __('doctor.appointments.follow_up_pending') }}
                                            </a>
                                        @else
                                            <a
                                                href="{{ route('doctor.appointments.follow-up', $row) }}"
                                                wire:navigate
                                                class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-[#047857] px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:brightness-95"
                                            >
                                                <flux:icon name="calendar-days" variant="mini" class="size-4 shrink-0" />
                                                {{ __('doctor.workspace.tab_follow_up') }}
                                            </a>
                                        @endif
                                        <a
                                            href="{{ route('doctor.appointments.conversation', $row) }}"
                                            wire:navigate
                                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-[#047857]/30 bg-white px-3 py-2 text-xs font-semibold text-[#047857] transition hover:bg-[#047857]/5"
                                        >
                                            <flux:icon name="chat-bubble-left-right" variant="mini" class="size-4 shrink-0" />
                                            {{ __('doctor.appointments.view_session') }}
                                        </a>
                                    </div>
                                @elseif ($row->status === 'pending_follow_up' && $row->parentAppointment instanceof \App\Models\Appointment)
                                    <div class="mx-auto inline-flex w-full min-w-[11rem] max-w-[13rem] flex-col">
                                        <a
                                            href="{{ route('doctor.appointments.follow-up', $row->parentAppointment) }}"
                                            wire:navigate
                                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-violet-300 bg-violet-50 px-3 py-2 text-xs font-semibold text-violet-800 transition hover:bg-violet-100"
                                        >
                                            <flux:icon name="calendar-days" variant="mini" class="size-4 shrink-0" />
                                            {{ __('doctor.appointments.follow_up_pending') }}
                                        </a>
                                    </div>
                                @elseif ($row->status === 'completed' && $this->canOpenChat($row))
                                    <a
                                        href="{{ route('doctor.appointments.conversation', $row) }}"
                                        wire:navigate
                                        class="inline-flex shrink-0 items-center justify-center gap-1 rounded-lg border border-[#047857]/30 bg-[#047857]/5 px-2.5 py-1.5 text-[0.6875rem] font-semibold whitespace-nowrap text-[#047857] transition hover:bg-[#047857]/10"
                                        title="{{ __('doctor.appointments.chat_open_until', ['date' => $row->chatOpenUntil()->locale(app()->getLocale())->translatedFormat('d M Y')]) }}"
                                    >
                                        <flux:icon name="chat-bubble-left-right" variant="mini" class="size-3.5 shrink-0" />
                                        {{ __('doctor.appointments.open_chat') }}
                                    </a>
                                @else
                                    <span class="block text-center text-xs text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-middle text-center whitespace-nowrap">
                                @if ($showStartTimer)
                                    <div
                                        class="mx-auto inline-flex w-full max-w-[7rem] items-center justify-center gap-1.5 rounded-md border border-[#10B981]/25 bg-[#10B981]/10 px-2.5 py-2 text-sm font-bold tabular-nums text-[#047857]"
                                        :aria-label="ariaLabel"
                                    >
                                        <flux:icon name="clock" variant="micro" class="size-3.5 shrink-0" />
                                        <span x-text="label"></span>
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-1">
            {{ $this->appointments->links() }}
        </div>
    @endif

    <flux:modal wire:model.self="showCancelModal" class="max-w-md rounded-2xl shadow-xl" :closable="true">
        <div class="px-6 py-8 sm:px-8">
            <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                <flux:icon name="exclamation-triangle" variant="outline" class="size-8" />
            </div>

            <flux:heading size="lg" class="mt-5 text-center font-semibold text-zinc-900">
                {{ __('doctor.appointments.cancel_modal.title') }}
            </flux:heading>

            <flux:text class="mt-2 text-center text-sm leading-relaxed text-zinc-600">
                @if ($this->pendingCancelAppointment instanceof \App\Models\Appointment && $this->cancelRequiresRefund($this->pendingCancelAppointment))
                    {{ __('doctor.appointments.cancel_modal.body') }}
                @else
                    {{ __('doctor.appointments.cancel_modal.body_no_refund') }}
                @endif
            </flux:text>

            @if ($this->pendingCancelAppointment instanceof \App\Models\Appointment)
                <div class="mt-5 rounded-xl border border-zinc-200/90 bg-zinc-50 px-4 py-3 text-sm">
                    <p class="font-semibold text-zinc-900">{{ $this->pendingCancelAppointment->patient_name }}</p>
                    <p class="mt-1 tabular-nums text-zinc-600">
                        {{ $this->pendingCancelAppointment->appointment_date?->format('d/m/Y') }}
                        ·
                        {{ \Illuminate\Support\Str::limit((string) $this->pendingCancelAppointment->start_time, 8, '') }}
                    </p>
                    @if ((float) $this->pendingCancelAppointment->total > 0)
                        <p class="mt-2 text-xs font-medium text-rose-700">
                            {{ __('doctor.appointments.cancel_modal.refund_note', ['amount' => number_format((float) $this->pendingCancelAppointment->total, 2)]) }}
                        </p>
                    @endif
                </div>
            @endif

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <flux:button
                    type="button"
                    variant="ghost"
                    class="w-full sm:w-auto"
                    wire:click="dismissCancelAppointmentModal"
                >
                    {{ __('doctor.appointments.cancel_modal.dismiss') }}
                </flux:button>
                <flux:button
                    type="button"
                    variant="danger"
                    class="w-full !bg-rose-600 hover:!bg-rose-700 sm:w-auto"
                    wire:click="confirmCancelAppointment"
                    wire:loading.attr="disabled"
                    wire:target="confirmCancelAppointment"
                >
                    <span wire:loading.remove wire:target="confirmCancelAppointment">
                        @if ($this->pendingCancelAppointment instanceof \App\Models\Appointment && $this->cancelRequiresRefund($this->pendingCancelAppointment))
                            {{ __('doctor.appointments.cancel_modal.confirm') }}
                        @else
                            {{ __('doctor.appointments.cancel_modal.confirm_no_refund') }}
                        @endif
                    </span>
                    <span wire:loading wire:target="confirmCancelAppointment">
                        {{ __('doctor.appointments.cancel_modal.confirming') }}
                    </span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>

@include('partials.appointment-start-timer-script')
