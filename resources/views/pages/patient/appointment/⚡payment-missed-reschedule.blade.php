<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use App\Services\DoctorAvailabilityService;
use App\Services\PatientPaymentMissedAppointmentService;
use App\Support\AppTimezone;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient')] #[Title('Choose appointment')] class extends Component
{
    public Appointment $appointment;

    public string $newDate = '';

    public string $selectedTime = '';

    public int $durationMinutes = 15;

    public function mount(Appointment $appointment): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        abort_unless((int) $appointment->user_id === (int) $user->id, 403);

        $service = app(PatientPaymentMissedAppointmentService::class);

        if (! $service->canReschedule($appointment)) {
            $this->redirectRoute('patient.appointments', navigate: true);

            return;
        }

        $this->appointment = $appointment->loadMissing('doctor');
        $this->durationMinutes = max(15, (int) $appointment->duration);

        $timezone = AppTimezone::name();
        $this->newDate = now($timezone)->format('Y-m-d');

        $doctor = $this->doctor();

        if ($doctor instanceof Doctor) {
            $availability = app(DoctorAvailabilityService::class);

            $this->newDate = $availability->firstDateWithSlots(
                $doctor,
                $this->durationMinutes,
                $this->newDate,
            ) ?? $this->newDate;
        }
    }

    protected function doctor(): ?Doctor
    {
        $doctor = $this->appointment->doctor;

        return $doctor instanceof Doctor ? $doctor : null;
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

    public function minDate(): string
    {
        return now(AppTimezone::name())->format('Y-m-d');
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

    /**
     * @return list<string>
     */
    public function getAvailableSlotsProperty(): array
    {
        if ($this->newDate === '' || ! $this->doctor() instanceof Doctor) {
            return [];
        }

        return app(DoctorAvailabilityService::class)->availableSlots(
            $this->doctor(),
            $this->newDate,
            $this->durationMinutes,
            $this->appointment->id,
        );
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
        if ($this->selectedTime === '' || in_array($this->selectedTime, $this->availableSlots, true)) {
            return;
        }

        $this->selectedTime = '';
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
            'newDate' => ['required', 'date', 'after_or_equal:'.$this->minDate()],
            'selectedTime' => ['required', 'string'],
        ]);

        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        try {
            $updated = app(PatientPaymentMissedAppointmentService::class)->reschedule(
                $user,
                $this->appointment,
                $this->newDate,
                $this->selectedTime,
            );
        } catch (ValidationException $exception) {
            throw $exception;
        }

        Flux::toast(
            variant: 'success',
            text: __('patient.scheduled_appointment.reschedule_success', [
                'date' => Carbon::parse($this->newDate)->locale(app()->getLocale())->translatedFormat('d M Y'),
                'time' => $this->displaySlot($this->selectedTime),
            ]),
        );

        $this->redirectRoute('patient.follow-up.confirm', $updated, navigate: true);
    }
}; ?>

<div class="mx-auto max-w-2xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <div>
        <flux:heading size="xl" class="font-semibold text-orange-600">{{ __('patient.scheduled_appointment.missed_title') }}</flux:heading>
        <flux:text class="mt-2 text-zinc-600">{{ __('patient.scheduled_appointment.missed_subtitle') }}</flux:text>
    </div>

    <flux:callout variant="warning" icon="exclamation-triangle">
        {{ __('patient.scheduled_appointment.missed_hint') }}
    </flux:callout>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm sm:p-6">
        <form wire:submit="save" class="space-y-5">
            <flux:field>
                <flux:label>{{ __('patient.scheduled_appointment.date_label') }}</flux:label>
                <flux:input wire:model.live="newDate" type="date" min="{{ $this->minDate() }}" required />
                <flux:error name="newDate" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('patient.scheduled_appointment.time_label') }}</flux:label>
                @if ($this->availableSlots === [])
                    <flux:callout variant="warning" icon="exclamation-circle" class="mt-2">
                        <div class="space-y-2">
                            <p>{{ __('patient.missed.no_slots') }}</p>
                            @if ($weekday = $this->selectedWeekdayLabel())
                                <p class="text-sm opacity-90">
                                    {{ __('patient.missed.no_slots_weekday', ['day' => $weekday]) }}
                                </p>
                            @endif
                        </div>
                    </flux:callout>
                @else
                    <div
                        @if ($this->isSelectedDateToday()) wire:poll.60s="refreshSlotsForToday" @endif
                        wire:key="payment-missed-slots-{{ $newDate }}"
                        class="mt-2 flex flex-wrap gap-2"
                    >
                        @foreach ($this->availableSlots as $slot)
                            <button
                                type="button"
                                wire:click="$set('selectedTime', '{{ $slot }}')"
                                @class([
                                    'rounded-full border px-4 py-2 text-sm font-semibold transition',
                                    'border-violet-700 bg-violet-700 text-white' => $selectedTime === $slot,
                                    'border-zinc-200 bg-white text-zinc-700 hover:border-violet-400' => $selectedTime !== $slot,
                                ])
                            >
                                {{ $this->displaySlot($slot) }}
                            </button>
                        @endforeach
                    </div>
                @endif
                <flux:error name="selectedTime" />
            </flux:field>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                <flux:button :href="route('patient.appointments', ['tab' => 'missed'])" wire:navigate variant="ghost">
                    {{ __('patient.missed.back') }}
                </flux:button>
                <flux:button type="submit" variant="primary" class="!bg-violet-600 !text-white hover:!brightness-95">
                    {{ __('patient.scheduled_appointment.choose_appointment') }}
                </flux:button>
            </div>
        </form>
    </div>
</div>
