<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\DoctorAvailabilityService;
use App\Services\DoctorScheduledAppointmentService;
use App\Services\FollowUpAppointmentService;
use App\Support\AppTimezone;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Follow Up')] class extends Component
{
    public Appointment $appointment;

    public string $newDate = '';

    public string $selectedTime = '';

    public string $paidNewDate = '';

    public string $paidSelectedTime = '';

    public int $durationMinutes = 15;

    public bool $followUpNotNeeded = false;

    public function mount(Appointment $appointment): void
    {
        $this->appointment = $appointment;
        $this->durationMinutes = $this->resolveFollowUpPageDurationMinutes($appointment);
        $this->followUpNotNeeded = app(FollowUpAppointmentService::class)->parentDeclinedFollowUp($appointment);

        $followUpService = app(FollowUpAppointmentService::class);
        $timezone = AppTimezone::name();
        $maxDate = $followUpService->maxSelectableDate($appointment);
        $suggested = $appointment->appointment_date
            ? Carbon::parse($appointment->appointment_date, $timezone)->addDays(min(7, FollowUpAppointmentService::windowDays()))
            : now($timezone)->addDay();

        if ($suggested->lessThan(now($timezone)->startOfDay())) {
            $suggested = now($timezone)->addDay();
        }

        if ($suggested->greaterThan($maxDate)) {
            $suggested = $maxDate->copy();
        }

        $doctor = Auth::guard('doctor')->user();
        $preferredDate = $suggested->format('Y-m-d');

        if ($doctor instanceof Doctor) {
            $firstAvailable = app(DoctorAvailabilityService::class)->firstDateWithSlots(
                $doctor,
                $this->durationMinutes,
                $preferredDate,
                FollowUpAppointmentService::windowDays() + 1,
                $maxDate->format('Y-m-d'),
            );

            $this->newDate = $firstAvailable ?? $preferredDate;
            $this->paidNewDate = $this->newDate;

            return;
        }

        $this->newDate = $preferredDate;
        $this->paidNewDate = $preferredDate;
    }

    private function resolveFollowUpPageDurationMinutes(Appointment $appointment): int
    {
        if ($appointment->is_follow_up) {
            $originalSession = $appointment->parent_id
                ? Appointment::query()->find($appointment->parent_id)
                : null;

            return max(15, (int) ($originalSession?->duration ?? $appointment->duration));
        }

        return FollowUpAppointmentService::sessionDurationMinutes();
    }

    protected function doctor(): Doctor
    {
        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        return $doctor;
    }

    public function updatedNewDate(?string $value = null): void
    {
        if ($value !== null && $value !== '') {
            try {
                $this->newDate = Carbon::parse($value, AppTimezone::name())->format('Y-m-d');
            } catch (\Throwable) {
                $this->newDate = '';
            }
        }

        $this->selectedTime = '';
    }

    public function updatedPaidNewDate(?string $value = null): void
    {
        if ($value !== null && $value !== '') {
            try {
                $this->paidNewDate = Carbon::parse($value, AppTimezone::name())->format('Y-m-d');
            } catch (\Throwable) {
                $this->paidNewDate = '';
            }
        }

        $this->paidSelectedTime = '';
    }

    public function getCanSchedulePaidAppointmentProperty(): bool
    {
        return app(DoctorScheduledAppointmentService::class)->parentCanSchedulePaidAppointment($this->appointment);
    }

    public function getPendingPaidAppointmentProperty(): ?Appointment
    {
        return app(DoctorScheduledAppointmentService::class)->pendingPaidAppointmentFor($this->appointment);
    }

    public function getExistingPaidAppointmentProperty(): ?Appointment
    {
        return app(DoctorScheduledAppointmentService::class)->existingPaidAppointmentFor($this->appointment);
    }

    public function paidSessionPrice(): float
    {
        $durationRow = $this->doctor()->durations()->where('durations.duration', $this->durationMinutes)->first();

        return round((float) ($durationRow?->pivot->price ?? 0), 2);
    }

    /**
     * @return list<string>
     */
    public function getPaidAvailableSlotsProperty(): array
    {
        if ($this->paidNewDate === '') {
            return [];
        }

        return app(DoctorAvailabilityService::class)->availableSlots(
            $this->doctor(),
            $this->paidNewDate,
            $this->durationMinutes,
        );
    }

    public function savePaidAppointment(): void
    {
        $this->validate([
            'paidNewDate' => ['required', 'date', 'after_or_equal:'.$this->minDate(), 'before_or_equal:'.$this->maxDate()],
            'paidSelectedTime' => ['required', 'string'],
        ], [], [
            'paidNewDate' => __('doctor.scheduled_appointment.date_label'),
            'paidSelectedTime' => __('doctor.scheduled_appointment.time_label'),
        ]);

        try {
            app(DoctorScheduledAppointmentService::class)->createPaid(
                $this->doctor(),
                $this->appointment,
                $this->paidNewDate,
                $this->paidSelectedTime,
            );
        } catch (ValidationException $exception) {
            throw $exception;
        }

        Flux::toast(
            variant: 'success',
            text: __('doctor.scheduled_appointment.success', [
                'date' => Carbon::parse($this->paidNewDate)->locale(app()->getLocale())->translatedFormat('d M Y'),
                'time' => $this->displaySlot($this->paidSelectedTime),
                'minutes' => DoctorScheduledAppointmentService::paymentGraceMinutes(),
            ]),
        );

        $this->redirectRoute('doctor.appointments', navigate: true);
    }

    public function minDate(): string
    {
        return app(FollowUpAppointmentService::class)
            ->windowStartFor($this->appointment)
            ->format('Y-m-d');
    }

    public function sessionDateLabel(): string
    {
        if ($this->appointment->appointment_date === null) {
            return '';
        }

        try {
            return Carbon::parse($this->appointment->appointment_date, AppTimezone::name())
                ->locale(app()->getLocale())
                ->translatedFormat('d M Y');
        } catch (\Throwable) {
            return '';
        }
    }

    public function maxDate(): string
    {
        return app(FollowUpAppointmentService::class)
            ->maxSelectableDate($this->appointment)
            ->format('Y-m-d');
    }

    public function windowDays(): int
    {
        return FollowUpAppointmentService::windowDays();
    }

    public function selectedWeekdayLabel(): string
    {
        if ($this->newDate === '') {
            return '';
        }

        try {
            return Carbon::parse($this->newDate, AppTimezone::name())
                ->locale(app()->getLocale())
                ->translatedFormat('l');
        } catch (\Throwable) {
            return '';
        }
    }

    public function doctorHasWorkingHours(): bool
    {
        return $this->doctor()->workingDays()
            ->where('is_working', true)
            ->whereHas('workingHours')
            ->exists();
    }

    /**
     * @return list<string>
     */
    public function getAvailableSlotsProperty(): array
    {
        if ($this->newDate === '') {
            return [];
        }

        return app(DoctorAvailabilityService::class)->availableSlots(
            $this->doctor(),
            $this->newDate,
            $this->durationMinutes,
        );
    }

    public function getCanScheduleFollowUpProperty(): bool
    {
        return app(FollowUpAppointmentService::class)->parentCanScheduleFollowUp($this->appointment);
    }

    public function getPendingFollowUpProperty(): ?Appointment
    {
        return app(FollowUpAppointmentService::class)->pendingFollowUpFor($this->appointment);
    }

    public function getExistingFollowUpProperty(): ?Appointment
    {
        return app(FollowUpAppointmentService::class)->existingFollowUpFor($this->appointment);
    }

    public function updatedFollowUpNotNeeded(bool $value): void
    {
        $service = app(FollowUpAppointmentService::class);

        try {
            if ($value) {
                $service->markFollowUpNotNeeded($this->doctor(), $this->appointment);
                $this->selectedTime = '';

                Flux::toast(
                    variant: 'success',
                    text: __('doctor.follow_up.no_need_success'),
                );

                return;
            }

            $service->clearFollowUpNotNeeded($this->doctor(), $this->appointment);

            Flux::toast(
                variant: 'success',
                text: __('doctor.follow_up.no_need_cleared'),
            );
        } catch (ValidationException $exception) {
            $this->followUpNotNeeded = app(FollowUpAppointmentService::class)->parentDeclinedFollowUp($this->appointment);

            throw $exception;
        }
    }

    public function followUpStatusLabel(Appointment $appointment): string
    {
        if ($appointment->isPendingFollowUp()) {
            return __('doctor.appointment_status.pending_follow_up');
        }

        if ($appointment->is_follow_up) {
            return __('doctor.appointment_status.follow_up');
        }

        return __('doctor.appointment_status.'.$appointment->status);
    }

    public function isSelectedDateToday(): bool
    {
        if ($this->newDate === '') {
            return false;
        }

        $timezone = AppTimezone::name();

        try {
            return Carbon::parse($this->newDate, $timezone)->toDateString() === now($timezone)->toDateString();
        } catch (\Throwable) {
            return false;
        }
    }

    public function refreshSlotsForToday(): void
    {
        if ($this->selectedTime === '') {
            return;
        }

        if (! in_array($this->selectedTime, $this->availableSlots, true)) {
            $this->selectedTime = '';
        }
    }

    public function displaySlot(string $slot): string
    {
        try {
            return Carbon::createFromFormat('H:i', $slot, AppTimezone::name())
                ->locale(app()->getLocale())
                ->translatedFormat('g:i a');
        } catch (\Throwable) {
            return $slot;
        }
    }

    public function save(): void
    {
        $this->validate([
            'newDate' => ['required', 'date', 'after_or_equal:'.$this->minDate(), 'before_or_equal:'.$this->maxDate()],
            'selectedTime' => ['required', 'string'],
        ]);

        try {
            app(FollowUpAppointmentService::class)->create(
                $this->doctor(),
                $this->appointment,
                $this->newDate,
                $this->selectedTime,
            );
        } catch (ValidationException $exception) {
            throw $exception;
        }

        $followUpService = app(FollowUpAppointmentService::class);

        Flux::toast(
            variant: 'success',
            text: __(
                $followUpService->skipsPatientConfirmation()
                    ? 'doctor.follow_up.success_direct'
                    : 'doctor.follow_up.success',
                [
                    'date' => Carbon::parse($this->newDate)->locale(app()->getLocale())->translatedFormat('d M Y'),
                    'time' => $this->displaySlot($this->selectedTime),
                ],
            ),
        );

        $this->redirectRoute('doctor.appointments', navigate: true);
    }
}; ?>

<div class="space-y-6 px-4 pb-28 pt-6 lg:px-0 lg:pb-0 lg:pt-0">
    @include('partials.doctor-appointment-workspace-header', ['appointment' => $appointment, 'active' => 'follow_up'])

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm sm:p-6">
        <flux:heading size="lg" class="font-semibold text-zinc-900">{{ __('doctor.follow_up.title') }}</flux:heading>

        @if ($appointment->is_follow_up)
            @if ($this->pendingPaidAppointment)
                <flux:callout variant="warning" icon="clock" class="mt-6">
                    <div class="space-y-2">
                        <p class="font-semibold">{{ __('doctor.scheduled_appointment.pending_title') }}</p>
                        <p class="text-sm">{{ __('doctor.scheduled_appointment.pending_body', ['minutes' => \App\Services\DoctorScheduledAppointmentService::paymentGraceMinutes()]) }}</p>
                        <p class="text-sm font-medium">
                            {{ $this->pendingPaidAppointment->appointment_date?->format('d/m/Y') }}
                            {{ $this->displaySlot(substr((string) $this->pendingPaidAppointment->start_time, 0, 5)) }}
                        </p>
                    </div>
                </flux:callout>
            @elseif ($this->existingPaidAppointment)
                <flux:callout variant="success" icon="check-circle" class="mt-6">
                    <div class="space-y-2">
                        <p class="font-semibold">{{ __('doctor.scheduled_appointment.scheduled_title') }}</p>
                        <p class="text-sm">{{ __('doctor.scheduled_appointment.scheduled_body') }}</p>
                        <p class="text-sm font-medium">
                            {{ $this->existingPaidAppointment->appointment_date?->format('d/m/Y') }}
                            {{ $this->displaySlot(substr((string) $this->existingPaidAppointment->start_time, 0, 5)) }}
                            — {{ $this->followUpStatusLabel($this->existingPaidAppointment) }}
                        </p>
                    </div>
                </flux:callout>
            @elseif ($this->canSchedulePaidAppointment)
                <flux:text class="mt-2 text-zinc-600">{{ __('doctor.scheduled_appointment.subtitle', ['days' => $this->windowDays()]) }}</flux:text>

                <div class="mt-6 rounded-2xl border border-emerald-200/80 bg-emerald-50/40 p-5 shadow-sm sm:p-6">
                    <flux:heading size="md" class="font-semibold text-emerald-950">{{ __('doctor.scheduled_appointment.title') }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-emerald-900/80">{{ __('doctor.scheduled_appointment.option_body') }}</flux:text>

                    <form wire:submit="savePaidAppointment" class="mt-5 space-y-5">
                        <flux:field>
                            <flux:label>{{ __('doctor.scheduled_appointment.date_label') }}</flux:label>
                            <flux:input wire:model.live="paidNewDate" type="date" min="{{ $this->minDate() }}" max="{{ $this->maxDate() }}" required />
                            <flux:error name="paidNewDate" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('doctor.scheduled_appointment.time_label') }}</flux:label>
                            @if ($this->paidAvailableSlots === [])
                                <flux:callout variant="warning" icon="exclamation-circle" class="mt-2">
                                    {{ __('doctor.follow_up.no_slots') }}
                                </flux:callout>
                            @else
                                <div wire:key="paid-slots-{{ $paidNewDate }}" class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                                    @foreach ($this->paidAvailableSlots as $slot)
                                        <button
                                            type="button"
                                            wire:click="$set('paidSelectedTime', '{{ $slot }}')"
                                            @class([
                                                'rounded-full border px-4 py-2 text-sm font-semibold transition',
                                                'border-[#047857] bg-[#047857] text-white' => $paidSelectedTime === $slot,
                                                'border-zinc-200 bg-white text-zinc-700 hover:border-emerald-400' => $paidSelectedTime !== $slot,
                                            ])
                                        >
                                            {{ $this->displaySlot($slot) }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            <flux:error name="paidSelectedTime" />
                        </flux:field>

                        <flux:callout variant="secondary" icon="banknotes" class="text-sm [&_[data-slot=content]]:!text-black">
                            {{ __('doctor.scheduled_appointment.price_hint', [
                                'amount' => number_format($this->paidSessionPrice(), 2),
                                'minutes' => \App\Services\DoctorScheduledAppointmentService::paymentGraceMinutes(),
                            ]) }}
                        </flux:callout>

                        <div class="flex justify-end">
                            <flux:button type="submit" variant="primary" class="!bg-[#047857] !text-white hover:!brightness-95">
                                {{ __('doctor.scheduled_appointment.submit') }}
                            </flux:button>
                        </div>
                    </form>
                </div>
            @else
                <flux:callout variant="success" icon="check-circle" class="mt-6">
                    {{ __('doctor.follow_up.session_finished') }}
                </flux:callout>
            @endif
        @else
        <flux:text class="mt-2 text-zinc-600">{{ __('doctor.follow_up.subtitle', ['days' => $this->windowDays()]) }}</flux:text>

        @if ($this->pendingFollowUp)
            <flux:callout variant="success" icon="check-circle" class="mt-6">
                <div class="space-y-2">
                    <p class="font-semibold">{{ __('doctor.follow_up.pending_title') }}</p>
                    <p class="text-sm">{{ __('doctor.follow_up.pending_body') }}</p>
                    <p class="text-sm font-medium">
                        {{ __('doctor.follow_up.pending_status') }} —
                        {{ $this->pendingFollowUp->appointment_date?->format('d/m/Y') }}
                        {{ $this->displaySlot(substr((string) $this->pendingFollowUp->start_time, 0, 5)) }}
                    </p>
                </div>
            </flux:callout>
        @elseif ($this->existingFollowUp)
            <flux:callout variant="warning" icon="information-circle" class="mt-6">
                <div class="space-y-2">
                    <p class="font-semibold">{{ __('doctor.follow_up.scheduled_title') }}</p>
                    <p class="text-sm">{{ __('doctor.follow_up.scheduled_body') }}</p>
                    <p class="text-sm font-medium">
                        {{ $this->existingFollowUp->appointment_date?->format('d/m/Y') }}
                        {{ $this->displaySlot(substr((string) $this->existingFollowUp->start_time, 0, 5)) }}
                        — {{ $this->followUpStatusLabel($this->existingFollowUp) }}
                    </p>
                </div>
            </flux:callout>
        @elseif ($followUpNotNeeded)
            <flux:callout variant="success" icon="check-circle" class="mt-6">
                <div class="space-y-2">
                    <p class="font-semibold">{{ __('doctor.follow_up.no_need_title') }}</p>
                    <p class="text-sm">{{ __('doctor.follow_up.no_need_body') }}</p>
                </div>
            </flux:callout>

            <div class="mt-6 rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <flux:heading size="md" class="font-semibold text-zinc-900">
                            {{ __('doctor.follow_up.no_need_toggle_label') }}
                        </flux:heading>
                        <flux:text class="mt-1 text-sm text-zinc-600">
                            {{ __('doctor.follow_up.no_need_locked') }}
                        </flux:text>
                    </div>
                    <div class="shrink-0 [--color-accent:#10B981] [--color-accent-foreground:#ffffff]">
                        <flux:switch wire:model.live="followUpNotNeeded" />
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <flux:button :href="route('doctor.appointments')" wire:navigate variant="primary" class="!bg-[#047857] !text-white hover:!brightness-95">
                    {{ __('doctor.follow_up.back_to_appointments') }}
                </flux:button>
            </div>
        @elseif (! $this->canScheduleFollowUp)
            <flux:callout variant="warning" icon="exclamation-circle" class="mt-6">
                {{ __('doctor.follow_up.complete_session_first') }}
            </flux:callout>
        @else
        <div class="mt-6 grid gap-6 lg:grid-cols-2 xl:grid-cols-12 xl:items-start">
            <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/40 p-5 shadow-sm sm:p-6 xl:col-span-7">
                <flux:heading size="md" class="font-semibold text-emerald-950">{{ __('doctor.follow_up.option_schedule_title') }}</flux:heading>
                <flux:text class="mt-1 text-sm text-emerald-900/80">{{ __('doctor.follow_up.option_schedule_body') }}</flux:text>

        <form wire:submit="save" class="mt-5 space-y-5">
            <flux:field>
                <flux:label>{{ __('doctor.follow_up.date_label') }}</flux:label>
                <flux:input wire:model.live="newDate" type="date" min="{{ $this->minDate() }}" max="{{ $this->maxDate() }}" required />
                <flux:description class="text-xs leading-relaxed sm:text-sm">{{ __('doctor.follow_up.date_window_hint', [
                    'min' => $this->minDate(),
                    'max' => $this->maxDate(),
                    'days' => $this->windowDays(),
                    'session' => $this->sessionDateLabel(),
                ]) }}</flux:description>
                <flux:error name="newDate" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('doctor.follow_up.time_label') }}</flux:label>
                @if ($this->availableSlots === [])
                    <flux:callout variant="warning" icon="exclamation-circle" class="mt-2">
                        <div class="space-y-2">
                            <p>{{ __('doctor.follow_up.no_slots') }}</p>
                            @if ($weekday = $this->selectedWeekdayLabel())
                                <p class="text-sm opacity-90">
                                    {{ __('doctor.follow_up.no_slots_weekday', ['day' => $weekday]) }}
                                </p>
                            @endif
                            @if (! $this->doctorHasWorkingHours())
                                <flux:link :href="route('doctor.settings.working-hours')" wire:navigate class="text-sm font-semibold">
                                    {{ __('doctor.follow_up.configure_working_hours') }}
                                </flux:link>
                            @endif
                        </div>
                    </flux:callout>
                @else
                    <div
                        @if ($this->isSelectedDateToday()) wire:poll.60s="refreshSlotsForToday" @endif
                        wire:key="follow-up-slots-{{ $newDate }}"
                        class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
                    >
                        @foreach ($this->availableSlots as $slot)
                            <button
                                type="button"
                                wire:click="$set('selectedTime', '{{ $slot }}')"
                                @class([
                                    'rounded-full border px-4 py-2 text-sm font-semibold transition',
                                    'border-[#047857] bg-[#047857] text-white' => $selectedTime === $slot,
                                    'border-zinc-200 bg-white text-zinc-700 hover:border-[#10B981]' => $selectedTime !== $slot,
                                ])
                            >
                                {{ $this->displaySlot($slot) }}
                            </button>
                        @endforeach
                    </div>
                @endif
                <flux:error name="selectedTime" />
            </flux:field>

                <flux:text class="text-sm text-zinc-500">
                {{ __('doctor.follow_up.patient_flow_hint_direct') }}
            </flux:text>

            <flux:callout variant="success" icon="gift" class="text-sm">
                {{ __('doctor.follow_up.free_hint') }}
            </flux:callout>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                <flux:button type="submit" variant="primary" class="!bg-[#047857] !text-white hover:!brightness-95">
                    {{ __('doctor.follow_up.submit_direct') }}
                </flux:button>
            </div>
        </form>
            </div>

            <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm sm:p-6 xl:col-span-5">
                <flux:heading size="md" class="font-semibold text-zinc-900">{{ __('doctor.follow_up.option_no_need_title') }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600">{{ __('doctor.follow_up.option_no_need_body') }}</flux:text>

                <div class="mt-5 flex items-center justify-between gap-4 rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-4">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-zinc-900">{{ __('doctor.follow_up.no_need_toggle_label') }}</p>
                        <p class="mt-0.5 text-xs text-zinc-600">{{ __('doctor.follow_up.no_need_toggle_hint') }}</p>
                    </div>
                    <div class="shrink-0 [--color-accent:#10B981] [--color-accent-foreground:#ffffff]">
                        <flux:switch wire:model.live="followUpNotNeeded" />
                    </div>
                </div>
                <flux:error name="followUpNotNeeded" />
            </div>
        </div>
        @endif
        @endif
    </div>
</div>
