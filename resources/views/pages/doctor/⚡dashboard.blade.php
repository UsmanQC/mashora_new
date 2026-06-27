<?php

use App\Livewire\Concerns\CompletesDoctorAppointment;
use App\Models\Appointment;
use App\Models\Doctor;
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
    use CompletesDoctorAppointment;

    /** @var list<string> */
    private const PERIODS = ['today', 'week', 'month', 'year', 'all'];

    /** @var list<string> */
    private const REVENUE_STATUSES = ['new', 'in_process', 'completed'];

    /** @var list<string> */
    private const UPCOMING_STATUSES = ['new', 'in_process'];

    #[Url]
    public string $period = 'today';

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

        $now = \Illuminate\Support\Carbon::now();

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

        $now = \Illuminate\Support\Carbon::now();

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
}; ?>

@php
    $doc = $this->currentDoctor;
@endphp

<div class="space-y-8" @if ($doc?->status === 'approved') wire:poll.45s @endif>
    @if ($doc)
        <div class="relative overflow-hidden rounded-2xl border border-zinc-200/80 bg-white p-4 shadow-sm sm:p-5">
            <div class="pointer-events-none absolute end-0 top-0 h-20 w-56 bg-gradient-to-l from-[#10B981]/10 to-transparent"></div>
            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="rounded-full border border-zinc-200 bg-zinc-50 p-1.5 shadow-sm">
                        <flux:avatar
                            :name="$doc->displayName()"
                            :src="$doc->profilePhotoUrl()"
                            circle
                            size="xl"
                            class="shrink-0"
                        />
                    </div>
                    <div class="min-w-0 space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate text-xl font-semibold tracking-tight text-zinc-900">{{ $doc->displayName() }}</p>
                            <span class="inline-flex items-center rounded-full bg-[#10B981]/10 px-2.5 py-0.5 text-xs font-semibold text-[#2f49ca]">
                                {{ __('Welcome back') }}
                            </span>
                        </div>
                        <p class="text-sm text-zinc-500">{{ __('Hope you have a productive day.') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-xs font-medium text-zinc-600">
                        {{ now()->locale(app()->getLocale())->isoFormat('ddd, D MMM') }}
                    </span>
                    @if ($doc->status === 'approved' && app()->environment('production'))
                        <flux:button
                            variant="primary"
                            size="sm"
                            class="shrink-0 !bg-[#10B981] hover:!brightness-95"
                            :href="route('patient.book-appointments', $doc)"
                            target="_blank"
                            rel="noopener noreferrer"
                            icon="share"
                        >
                            {{ __('doctor.dashboard.share_profile') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if ($doc && $doc->status !== 'approved')
        <div class="rounded-2xl border border-zinc-200/90 bg-white px-4 py-10 text-center shadow-sm sm:px-8">
            @include('partials.patient-empty-record-illustration')
            <flux:heading size="lg" class="mt-4 font-semibold text-[#10B981]">
                {{ $doc->status === 'rejected' ? __('doctor.dashboard.verification_rejected_title') : __('doctor.dashboard.verification_pending_title') }}
            </flux:heading>
            <flux:text class="mx-auto mt-3 max-w-lg text-zinc-600">
                {!! __('doctor.dashboard.verification_body_html', ['email' => 'contact@mashora.co']) !!}
            </flux:text>
        </div>
    @elseif ($doc && $doc->status === 'approved')
        <div class="space-y-6">
            <div class="rounded-2xl bg-white p-3 shadow-sm">
                <nav class="grid grid-cols-5 gap-2" aria-label="{{ __('doctor.dashboard.period_label') }}">
                    @foreach (['today' => __('doctor.dashboard.tab_today'), 'week' => __('doctor.dashboard.tab_week'), 'month' => __('doctor.dashboard.tab_month'), 'year' => __('doctor.dashboard.tab_year'), 'all' => __('doctor.dashboard.tab_all')] as $key => $label)
                        <flux:button
                            :href="route('doctor.dashboard', ['period' => $key])"
                            wire:navigate
                            size="sm"
                            :variant="$this->period === $key ? 'primary' : 'outline'"
                            class="w-full rounded-xl py-2.5 text-sm font-semibold {{ $this->period === $key ? '!bg-[#047857] !text-white shadow-sm' : '!border-0 bg-zinc-50 text-zinc-700 hover:!bg-white' }}"
                        >
                            {{ $label }}
                        </flux:button>
                    @endforeach
                </nav>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="rounded-2xl border border-[#10B981]/20 bg-gradient-to-br from-[#eef2ff] via-white to-white p-5 shadow-sm transition hover:shadow-md">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-zinc-500">{{ __('doctor.dashboard.stat_revenue') }}</p>
                            <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-zinc-900">
                                {{ $this->revenueTotalFormatted }}
                                <span class="text-base font-semibold text-[#10B981]">{{ __('doctor.dashboard.sar_suffix') }}</span>
                            </p>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[#10B981]/10 text-[#10B981]" aria-hidden="true">
                            <flux:icon name="banknotes" variant="outline" class="size-6" />
                        </span>
                    </div>
                </div>
                <div class="rounded-2xl border border-sky-200/70 bg-gradient-to-br from-sky-50 via-white to-white p-5 shadow-sm transition hover:shadow-md">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-zinc-500">{{ __('doctor.dashboard.stat_appointments') }}</p>
                            <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-zinc-900">
                                {{ $this->periodBookingsCount }}
                                <span class="text-base font-semibold normal-case text-sky-600">{{ __('doctor.dashboard.reservations_suffix') }}</span>
                            </p>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-sky-100 text-sky-600" aria-hidden="true">
                            <flux:icon name="calendar-days" variant="outline" class="size-6" />
                        </span>
                    </div>
                </div>
                <div class="rounded-2xl border border-emerald-200/70 bg-gradient-to-br from-emerald-50 via-white to-white p-5 shadow-sm transition hover:shadow-md">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-zinc-500">{{ __('doctor.dashboard.stat_cases') }}</p>
                            <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-zinc-900">
                                {{ $this->periodBookingsCount }}
                                <span class="ms-1 text-xs font-semibold uppercase leading-tight text-emerald-600 sm:text-sm">{{ __('doctor.dashboard.cases_suffix') }}</span>
                            </p>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600" aria-hidden="true">
                            <flux:icon name="face-smile" variant="outline" class="size-6" />
                        </span>
                    </div>
                </div>
            </div>

            <div class="space-y-2">
                <flux:heading size="lg" class="font-semibold text-zinc-900">{{ __('doctor.dashboard.quick_links_title') }}</flux:heading>
                <flux:text class="text-sm text-zinc-600">{{ __('doctor.dashboard.quick_links_subtitle') }}</flux:text>
            </div>

            @include('partials.doctor-menu-sections', ['ariaLabel' => __('doctor.dashboard.quick_links_title')])

            @if ($this->upcomingAppointments->isEmpty())
                <div class="rounded-2xl border border-zinc-200/90 bg-white px-4 py-10 text-center shadow-sm">
                    @include('partials.patient-empty-record-illustration')
                    <flux:text class="mt-4 text-zinc-600">{{ __('doctor.dashboard.no_new_appointments') }}</flux:text>
                </div>
            @else
                <div>
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <flux:heading size="lg" class="font-semibold text-zinc-900">{{ __('doctor.dashboard.upcoming_title') }}</flux:heading>
                        <flux:link :href="route('doctor.appointments')" wire:navigate class="text-sm font-medium text-[#10B981]">
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

    @include('partials.doctor-complete-appointment-modals')
</div>
