<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\AppointmentRescheduleService;
use App\Services\DoctorAvailabilityService;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::doctor')] #[Title('Reschedule appointment')] class extends Component
{
    public Appointment $appointment;

    public string $newDate = '';

    public string $selectedTime = '';

    public int $durationMinutes = 15;

    public function mount(Appointment $appointment): void
    {
        $this->appointment = $appointment;
        $this->durationMinutes = max(15, (int) $appointment->duration);

        $timezone = config('app.timezone');
        $today = now($timezone)->startOfDay();

        $preferredDate = $appointment->appointment_date
            ? Carbon::parse($appointment->appointment_date, $timezone)->format('Y-m-d')
            : $today->format('Y-m-d');

        if (Carbon::parse($preferredDate, $timezone)->lessThan($today)) {
            $preferredDate = $today->format('Y-m-d');
        }

        $doctor = Auth::guard('doctor')->user();

        if ($doctor instanceof Doctor) {
            $firstAvailable = app(DoctorAvailabilityService::class)->firstDateWithSlots(
                $doctor,
                $this->durationMinutes,
                $preferredDate,
            );

            $this->newDate = $firstAvailable ?? $preferredDate;
            $this->preselectCurrentTime();

            return;
        }

        $this->newDate = $preferredDate;
    }

    protected function doctor(): Doctor
    {
        $doctor = Auth::guard('doctor')->user();
        if (! $doctor instanceof Doctor) {
            abort(403);
        }

        return $doctor;
    }

    public function updatedNewDate(): void
    {
        $this->selectedTime = '';
    }

    public function minDate(): string
    {
        return now(config('app.timezone'))->format('Y-m-d');
    }

    public function selectedWeekdayLabel(): string
    {
        if ($this->newDate === '') {
            return '';
        }

        try {
            return Carbon::parse($this->newDate, config('app.timezone'))
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
            $this->appointment->id,
        );
    }

    public function displaySlot(string $slot): string
    {
        try {
            return Carbon::createFromFormat('H:i', $slot, config('app.timezone'))
                ->locale(app()->getLocale())
                ->translatedFormat('g:i a');
        } catch (\Throwable) {
            return $slot;
        }
    }

    private function preselectCurrentTime(): void
    {
        if (! filled($this->appointment->start_time)) {
            return;
        }

        try {
            $currentSlot = Carbon::parse($this->appointment->start_time, config('app.timezone'))->format('H:i');
        } catch (\Throwable) {
            return;
        }

        if (in_array($currentSlot, $this->availableSlots, true)) {
            $this->selectedTime = $currentSlot;
        }
    }

    public function save(): void
    {
        $this->validate([
            'newDate' => ['required', 'date', 'after_or_equal:'.$this->minDate()],
            'selectedTime' => ['required', 'string'],
        ]);

        try {
            app(AppointmentRescheduleService::class)->reschedule(
                $this->doctor(),
                $this->appointment,
                $this->newDate,
                $this->selectedTime,
            );
        } catch (ValidationException $exception) {
            throw $exception;
        }

        Flux::toast(
            variant: 'success',
            text: __('doctor.reschedule.success', [
                'date' => Carbon::parse($this->newDate)->locale(app()->getLocale())->translatedFormat('d M Y'),
                'time' => $this->displaySlot($this->selectedTime),
            ]),
        );

        $this->redirectRoute('doctor.appointments', navigate: true);
    }
}; ?>

<div class="mx-auto w-full max-w-2xl space-y-6">
    @include('partials.doctor-appointment-workspace-header', ['appointment' => $appointment, 'active' => 'reschedule'])

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm sm:p-6">
        <flux:heading size="lg" class="font-semibold text-zinc-900">{{ __('doctor.reschedule.title') }}</flux:heading>
        <flux:text class="mt-2 text-zinc-600">{{ __('doctor.reschedule.subtitle') }}</flux:text>

        <form wire:submit="save" class="mt-6 space-y-5">
            <flux:field>
                <flux:label>{{ __('doctor.reschedule.date_label') }}</flux:label>
                <flux:input wire:model.live="newDate" type="date" min="{{ $this->minDate() }}" required />
                <flux:error name="newDate" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('doctor.reschedule.time_label') }}</flux:label>
                @if ($this->availableSlots === [])
                    <flux:callout variant="warning" icon="exclamation-circle" class="mt-2">
                        <div class="space-y-2">
                            <p>{{ __('doctor.reschedule.no_slots') }}</p>
                            @if ($weekday = $this->selectedWeekdayLabel())
                                <p class="text-sm opacity-90">
                                    {{ __('doctor.reschedule.no_slots_weekday', ['day' => $weekday]) }}
                                </p>
                            @endif
                            @if (! $this->doctorHasWorkingHours())
                                <flux:link :href="route('doctor.settings.working-hours')" wire:navigate class="text-sm font-semibold">
                                    {{ __('doctor.reschedule.configure_working_hours') }}
                                </flux:link>
                            @endif
                        </div>
                    </flux:callout>
                @else
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($this->availableSlots as $slot)
                            <button
                                type="button"
                                wire:click="$set('selectedTime', '{{ $slot }}')"
                                @class([
                                    'rounded-full border px-4 py-2 text-sm font-semibold transition',
                                    'border-[#132A6E] bg-[#132A6E] text-white' => $selectedTime === $slot,
                                    'border-zinc-200 bg-white text-zinc-700 hover:border-[#3C5CF7]' => $selectedTime !== $slot,
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
                {{ __('doctor.reschedule.patient_flow_hint') }}
            </flux:text>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                <flux:button :href="route('doctor.appointments.prescription', $appointment)" wire:navigate variant="ghost">
                    {{ __('doctor.auth.back') }}
                </flux:button>
                <flux:button type="submit" variant="primary" class="!bg-[#132A6E] !text-white hover:!brightness-95">
                    {{ __('doctor.reschedule.submit') }}
                </flux:button>
            </div>
        </form>
    </div>
</div>
