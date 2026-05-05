<?php

use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Dashboard')] class extends Component
{
    /** @var list<string> */
    private const PERIODS = ['today', 'week', 'month', 'year', 'all'];

    /** @var list<string> */
    private const REVENUE_STATUSES = ['new', 'in_process', 'completed'];

    /** @var list<string> */
    private const UPCOMING_STATUSES = ['new', 'in_process'];

    #[Url]
    public string $period = 'today';

    public bool $showCompleteAppointmentModal = false;

    public bool $showDiagnosisRequiredModal = false;

    public bool $showPrescriptionRequiredModal = false;

    public ?int $appointmentPendingCompleteId = null;

    public function mount(): void
    {
        if (! in_array($this->period, self::PERIODS, true)) {
            $this->period = 'today';
        }
    }

    protected function doctor(): ?Doctor
    {
        $d = Auth::guard('doctor')->user();

        return $d instanceof Doctor ? $d : null;
    }

    #[Computed]
    public function currentDoctor(): ?Doctor
    {
        return $this->doctor();
    }

    /**
     * @param  Builder<Appointment>  $query
     */
    private function scopeAppointmentDatePeriod(Builder $query): void
    {
        if ($this->period === 'all') {
            return;
        }

        $now = Carbon::now();

        match ($this->period) {
            'today' => $query->whereDate('appointment_date', $now->toDateString()),
            'week' => $query->whereBetween('appointment_date', [
                $now->copy()->startOfWeek()->toDateString(),
                $now->copy()->endOfWeek()->toDateString(),
            ]),
            'month' => $query->whereMonth('appointment_date', $now->month)
                ->whereYear('appointment_date', $now->year),
            'year' => $query->whereYear('appointment_date', $now->year),
            default => null,
        };
    }

    /**
     * @param  Builder<Appointment>  $query
     */
    private function scopeCreatedAtPeriod(Builder $query): void
    {
        if ($this->period === 'all') {
            return;
        }

        $now = Carbon::now();

        match ($this->period) {
            'today' => $query->whereDate('created_at', $now->toDateString()),
            'week' => $query->whereBetween('created_at', [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
            ]),
            'month' => $query->whereMonth('created_at', $now->month)
                ->whereYear('created_at', $now->year),
            'year' => $query->whereYear('created_at', $now->year),
            default => null,
        };
    }

    #[Computed]
    public function revenueTotalFormatted(): string
    {
        $doctor = $this->doctor();
        if ($doctor === null) {
            return '0';
        }

        $sum = (float) $doctor->appointments()
            ->whereIn('status', self::REVENUE_STATUSES)
            ->tap(fn (Builder $q) => $this->scopeCreatedAtPeriod($q))
            ->sum('total');

        return number_format($sum, 0, '.', ',');
    }

    #[Computed]
    public function periodBookingsCount(): int
    {
        $doctor = $this->doctor();
        if ($doctor === null) {
            return 0;
        }

        return $doctor->appointments()
            ->tap(fn (Builder $q) => $this->scopeAppointmentDatePeriod($q))
            ->count();
    }

    /**
     * @return EloquentCollection<int, Appointment>
     */
    #[Computed]
    public function upcomingAppointments(): EloquentCollection
    {
        $doctor = $this->doctor();
        if ($doctor === null) {
            return new EloquentCollection;
        }

        return $doctor->appointments()
            ->whereIn('status', self::UPCOMING_STATUSES)
            ->orderBy('scheduled_at')
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->limit(10)
            ->get();
    }

    public function requestCompleteAppointment(int $appointmentId): void
    {
        $doctor = $this->doctor();
        if ($doctor === null) {
            abort(403);
        }

        $appointment = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereKey($appointmentId)
            ->first();

        if ($appointment === null || $appointment->status !== 'in_process') {
            return;
        }

        $this->appointmentPendingCompleteId = $appointmentId;
        $this->showCompleteAppointmentModal = true;
    }

    public function dismissCompleteAppointmentModal(): void
    {
        $this->showCompleteAppointmentModal = false;
        $this->appointmentPendingCompleteId = null;
    }

    public function updatedShowCompleteAppointmentModal(bool $value): void
    {
        if (! $value) {
            $this->appointmentPendingCompleteId = null;
        }
    }

    public function confirmCompleteAppointment(): void
    {
        $id = $this->appointmentPendingCompleteId;
        $this->dismissCompleteAppointmentModal();

        if ($id === null) {
            return;
        }

        $doctor = $this->doctor();
        if ($doctor === null) {
            abort(403);
        }

        $appointment = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereKey($id)
            ->with(['diagnosis', 'medications'])
            ->first();

        if ($appointment === null || $appointment->status !== 'in_process') {
            return;
        }

        if ($appointment->diagnosis === null) {
            $this->showDiagnosisRequiredModal = true;

            return;
        }

        if (! $appointment->prescription_not_needed && $appointment->medications->isEmpty()) {
            $this->showPrescriptionRequiredModal = true;

            return;
        }

        $this->finalizeInProcessAppointmentCompletion($appointment);
    }

    public function dismissDiagnosisRequiredModal(): void
    {
        $this->showDiagnosisRequiredModal = false;
    }

    public function dismissPrescriptionRequiredModal(): void
    {
        $this->showPrescriptionRequiredModal = false;
    }

    private function finalizeInProcessAppointmentCompletion(Appointment $appointment): void
    {
        $appointment->forceFill([
            'status' => 'completed',
            'actual_end_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ])->save();

        Flux::toast(variant: 'success', text: __('doctor.complete_flow.success'));

        $this->redirect(route('doctor.dashboard'));
    }
}; ?>

@php
    $doc = $this->currentDoctor;
@endphp

<div class="space-y-8" @if ($doc?->status === 'approved') wire:poll.45s @endif>
    @if ($doc)
        <div class="flex flex-col gap-4 rounded-2xl border border-zinc-200/90 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
            <div class="flex min-w-0 items-center gap-4">
                <flux:avatar
                    :name="$doc->displayName()"
                    :src="$doc->profilePhotoUrl()"
                    circle
                    size="xl"
                    class="shrink-0"
                />
                <div class="min-w-0">
                    <p class="truncate text-lg font-semibold text-zinc-900">{{ $doc->displayName() }}</p>
                    @if (filled($doc->aboutDisplay()))
                        <p class="mt-1 line-clamp-2 text-sm text-zinc-600">{{ $doc->aboutDisplay() }}</p>
                    @endif
                </div>
            </div>
            @if ($doc->status === 'approved' && app()->environment('production'))
                <flux:button
                    variant="primary"
                    size="sm"
                    class="shrink-0 !bg-[#3C5CF7] hover:!brightness-95"
                    :href="route('patient.book-appointments', $doc)"
                    target="_blank"
                    rel="noopener noreferrer"
                    icon="share"
                >
                    {{ __('doctor.dashboard.share_profile') }}
                </flux:button>
            @endif
        </div>
    @endif

    @if ($doc && $doc->status !== 'approved')
        <div class="rounded-2xl border border-zinc-200/90 bg-white px-4 py-10 text-center shadow-sm sm:px-8">
            @include('partials.patient-empty-record-illustration')
            <flux:heading size="lg" class="mt-4 font-semibold text-[#1565c0]">
                {{ $doc->status === 'rejected' ? __('doctor.dashboard.verification_rejected_title') : __('doctor.dashboard.verification_pending_title') }}
            </flux:heading>
            <flux:text class="mx-auto mt-3 max-w-lg text-zinc-600">
                {!! __('doctor.dashboard.verification_body_html', ['email' => 'contact@mashora.co']) !!}
            </flux:text>
        </div>
    @elseif ($doc && $doc->status === 'approved')
        <div class="space-y-6">
            <div class="-mx-1 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <nav class="flex min-w-max gap-1 px-1" aria-label="{{ __('doctor.dashboard.period_label') }}">
                    @foreach (['today' => __('doctor.dashboard.tab_today'), 'week' => __('doctor.dashboard.tab_week'), 'month' => __('doctor.dashboard.tab_month'), 'year' => __('doctor.dashboard.tab_year'), 'all' => __('doctor.dashboard.tab_all')] as $key => $label)
                        <flux:button
                            :href="route('doctor.dashboard', ['period' => $key])"
                            wire:navigate
                            size="sm"
                            :variant="$this->period === $key ? 'primary' : 'outline'"
                            class="{{ $this->period === $key ? '!bg-[#132A6E] !text-white' : '!border-zinc-200 text-zinc-700' }}"
                        >
                            {{ $label }}
                        </flux:button>
                    @endforeach
                </nav>
            </div>

            <div
                class="flex gap-4 overflow-x-auto pb-2 [-ms-overflow-style:none] snap-x snap-mandatory [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
            >
                <div class="w-[min(100%,22rem)] shrink-0 snap-start sm:w-auto sm:min-w-[14rem] sm:flex-1">
                    <div class="rounded-2xl border border-[#3C5CF7]/25 bg-white p-4 shadow-sm sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-zinc-500">{{ __('doctor.dashboard.stat_revenue') }}</p>
                                <p class="mt-1 text-3xl font-semibold tabular-nums text-zinc-900">
                                    {{ $this->revenueTotalFormatted }}
                                    <span class="text-base font-semibold text-[#3C5CF7]">{{ __('doctor.dashboard.sar_suffix') }}</span>
                                </p>
                            </div>
                            <span class="shrink-0 text-[#3C5CF7]" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="39" height="30" viewBox="0 0 39 30" fill="none">
                                    <path
                                        d="M23.992,20.045a1.5,1.5,0,0,0,0,3Zm5.607,3a1.5,1.5,0,0,0,0-3ZM2,22.25H2ZM2,8.75H2ZM2,12.5H38v-3H2ZM23.992,23.045H29.6v-3H23.992ZM.5,22.25A8.25,8.25,0,0,0,8.75,30.5v-3A5.25,5.25,0,0,1,3.5,22.25Zm36,0a5.25,5.25,0,0,1-5.25,5.25v3a8.25,8.25,0,0,0,8.25-8.25Zm3-13.5A8.25,8.25,0,0,0,31.25.5v3A5.25,5.25,0,0,1,36.5,8.75Zm-36,0A5.25,5.25,0,0,1,8.75,3.5V.5A8.25,8.25,0,0,0,.5,8.75Zm33,0v13.5h3V8.75Zm-33,13.5V8.75H.5v13.5ZM8.75,3.5h22.5V.5H8.75Zm22.5,24H8.75v3h22.5Z"
                                        transform="translate(-0.5 -0.5)"
                                        fill="currentColor"
                                    />
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="w-[min(100%,22rem)] shrink-0 snap-start sm:w-auto sm:min-w-[14rem] sm:flex-1">
                    <div class="rounded-2xl border border-[#3C5CF7]/25 bg-white p-4 shadow-sm sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-zinc-500">{{ __('doctor.dashboard.stat_appointments') }}</p>
                                <p class="mt-1 text-3xl font-semibold tabular-nums text-zinc-900">
                                    {{ $this->periodBookingsCount }}
                                    <span class="text-base font-semibold normal-case text-[#3C5CF7]">{{ __('doctor.dashboard.reservations_suffix') }}</span>
                                </p>
                            </div>
                            <span class="shrink-0 text-[#3C5CF7]" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 35 35" fill="none">
                                    <path d="M18,10v8l5,2m11-2A16,16,0,1,1,18,2,16,16,0,0,1,34,18Z" transform="translate(-0.5 -0.5)" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" />
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="w-[min(100%,22rem)] shrink-0 snap-start sm:w-auto sm:min-w-[14rem] sm:flex-1">
                    <div class="rounded-2xl border border-[#3C5CF7]/25 bg-white p-4 shadow-sm sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-zinc-500">{{ __('doctor.dashboard.stat_cases') }}</p>
                                <p class="mt-1 text-3xl font-semibold tabular-nums text-zinc-900">
                                    {{ $this->periodBookingsCount }}
                                    <span class="ms-1 text-xs font-semibold uppercase leading-tight text-[#3C5CF7] sm:text-sm">{{ __('doctor.dashboard.cases_suffix') }}</span>
                                </p>
                            </div>
                            <span class="shrink-0 text-[#3C5CF7]" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 39.667 39.667" fill="none">
                                    <path d="M13,24a9.9,9.9,0,0,0,7.333,3.667A9.9,9.9,0,0,0,27.667,24M14.852,15.75v-.917m10.982,0v.917m12.833,4.583A18.333,18.333,0,1,1,20.333,2,18.333,18.333,0,0,1,38.667,20.333Z" transform="translate(-0.5 -0.5)" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" />
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            @if ($this->upcomingAppointments->isEmpty())
                <div class="rounded-2xl border border-zinc-200/90 bg-white px-4 py-10 text-center shadow-sm">
                    @include('partials.patient-empty-record-illustration')
                    <flux:text class="mt-4 text-zinc-600">{{ __('doctor.dashboard.no_new_appointments') }}</flux:text>
                </div>
            @else
                <div>
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <flux:heading size="lg" class="font-semibold text-zinc-900">{{ __('doctor.dashboard.upcoming_title') }}</flux:heading>
                        <flux:link :href="route('doctor.appointments')" wire:navigate class="text-sm font-medium text-[#3C5CF7]">
                            {{ __('doctor.dashboard.view_all') }}
                            <flux:icon name="chevron-right" variant="mini" class="inline size-4 align-middle rtl:rotate-180" />
                        </flux:link>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($this->upcomingAppointments as $appointment)
                            @include('partials.doctor-dashboard-upcoming-card', compact('appointment'))
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    <flux:modal wire:model.self="showCompleteAppointmentModal" class="max-w-md rounded-2xl shadow-xl" :closable="true">
        <div class="flex flex-col items-center px-6 py-8 text-center sm:px-10 sm:py-10">
            <div
                class="mb-6 flex size-24 shrink-0 items-center justify-center rounded-full border-[3px] border-[#f8bb86] bg-amber-50/60"
                aria-hidden="true"
            >
                <span class="text-5xl font-bold leading-none text-[#f8bb86]">!</span>
            </div>

            <flux:heading size="xl" class="font-bold text-[#545454]">
                {{ __('doctor.complete_modal.title') }}
            </flux:heading>

            <flux:text class="mt-3 max-w-[20rem] leading-relaxed text-[#595959]">
                {{ __('doctor.complete_modal.body') }}
            </flux:text>

            <div class="mt-8 flex w-full max-w-sm flex-row gap-3 sm:justify-center">
                <flux:button
                    type="button"
                    class="flex-1 !border-0 bg-[#7066e0] text-white shadow-sm hover:brightness-105 sm:min-h-11"
                    variant="primary"
                    wire:click="confirmCompleteAppointment"
                >
                    {{ __('doctor.complete_modal.confirm') }}
                </flux:button>
                <flux:button
                    type="button"
                    class="flex-1 !border-0 bg-[#6e7881] text-white shadow-sm hover:bg-[#5d656c] sm:min-h-11"
                    variant="ghost"
                    wire:click="dismissCompleteAppointmentModal"
                >
                    {{ __('doctor.complete_modal.cancel') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model.self="showDiagnosisRequiredModal" class="max-w-md rounded-2xl shadow-xl" :closable="true">
        <div class="flex flex-col items-center px-6 py-8 text-center sm:px-10 sm:py-10">
            <div
                class="mb-6 flex size-24 shrink-0 items-center justify-center rounded-full border-[3px] border-[#f8bb86] bg-amber-50/60"
                aria-hidden="true"
            >
                <span class="text-5xl font-bold leading-none text-[#f8bb86]">!</span>
            </div>

            <flux:heading size="xl" class="font-bold text-[#545454]">
                {{ __('doctor.complete_flow.diagnosis_title') }}
            </flux:heading>

            <flux:text class="mt-3 max-w-[20rem] leading-relaxed text-[#595959]">
                {{ __('doctor.complete_flow.diagnosis_body') }}
            </flux:text>

            <div class="mt-8 flex w-full max-w-sm justify-center">
                <flux:button
                    type="button"
                    class="w-full !border-0 bg-[#7066e0] text-white shadow-sm hover:brightness-105 sm:max-w-xs sm:min-h-11"
                    variant="primary"
                    wire:click="dismissDiagnosisRequiredModal"
                >
                    {{ __('doctor.complete_flow.ok') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model.self="showPrescriptionRequiredModal" class="max-w-md rounded-2xl shadow-xl" :closable="true">
        <div class="flex flex-col items-center px-6 py-8 text-center sm:px-10 sm:py-10">
            <div
                class="mb-6 flex size-24 shrink-0 items-center justify-center rounded-full border-[3px] border-[#f8bb86] bg-amber-50/60"
                aria-hidden="true"
            >
                <span class="text-5xl font-bold leading-none text-[#f8bb86]">!</span>
            </div>

            <flux:heading size="xl" class="font-bold text-[#545454]">
                {{ __('doctor.complete_flow.prescription_title') }}
            </flux:heading>

            <flux:text class="mt-3 max-w-[20rem] leading-relaxed text-[#595959]">
                {{ __('doctor.complete_flow.prescription_body') }}
            </flux:text>

            <div class="mt-8 flex w-full max-w-sm justify-center">
                <flux:button
                    type="button"
                    class="w-full !border-0 bg-[#7066e0] text-white shadow-sm hover:brightness-105 sm:max-w-xs sm:min-h-11"
                    variant="primary"
                    wire:click="dismissPrescriptionRequiredModal"
                >
                    {{ __('doctor.complete_flow.ok') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
