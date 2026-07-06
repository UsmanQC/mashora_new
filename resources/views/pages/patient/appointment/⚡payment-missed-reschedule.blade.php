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
use Illuminate\Support\Facades\Storage;
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

    public function profilePhotoUrl(): ?string
    {
        $user = Auth::user();

        if ($user === null || ! filled($user->profile_photo_path)) {
            return null;
        }

        return Storage::disk('public')->url((string) $user->profile_photo_path);
    }

    public function pageTitle(): string
    {
        return __('patient.scheduled_appointment.missed_title');
    }

    public function pageSubtitle(): string
    {
        return __('patient.scheduled_appointment.missed_subtitle');
    }
}; ?>

<div class="patient-luxury-payment-missed-reschedule bg-slate-50 pb-[calc(8.5rem+env(safe-area-inset-bottom))] sm:bg-transparent sm:pb-12" data-test="patient-luxury-payment-missed-reschedule">
    <div class="sm:hidden">
        @include('partials.patient-luxury-page-header', [
            'title' => $this->pageTitle(),
            'subtitle' => $this->pageSubtitle(),
            'profilePhotoUrl' => $this->profilePhotoUrl(),
            'userName' => auth()->user()?->name,
            'backUrl' => route('patient.appointments', ['tab' => 'missed']),
            'backLabel' => __('patient.appointments.title'),
            'testId' => 'patient-payment-missed-reschedule-header',
        ])
    </div>

    <div class="mx-auto max-w-2xl space-y-5 px-6 pt-5 sm:space-y-6 sm:px-6 sm:py-8 lg:px-8">
        <div class="hidden sm:block">
            <flux:heading size="xl" class="font-semibold text-orange-600">{{ $this->pageTitle() }}</flux:heading>
            <flux:text class="mt-2 text-zinc-600">{{ $this->pageSubtitle() }}</flux:text>
        </div>

    <flux:callout variant="warning" icon="exclamation-triangle" class="border-amber-200 bg-amber-50 text-amber-950 [&_[data-slot=heading]]:text-amber-950 [&_[data-slot=text]]:text-amber-900">
        {{ __('patient.scheduled_appointment.missed_hint') }}
    </flux:callout>

    <div class="rounded-3xl border border-slate-100/80 bg-white p-5 shadow-[0_8px_32px_0_rgba(0,0,0,0.03)] sm:rounded-2xl sm:border-zinc-200/90 sm:p-6 sm:shadow-sm">
        <form wire:submit="save" class="space-y-5" id="payment-missed-reschedule-form">
            <flux:field>
                <flux:label>{{ __('patient.scheduled_appointment.date_label') }}</flux:label>
                <flux:input wire:model.live="newDate" type="date" min="{{ $this->minDate() }}" required />
                <flux:error name="newDate" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('patient.scheduled_appointment.time_label') }}</flux:label>
                @if ($this->availableSlots === [])
                    <flux:callout variant="warning" icon="exclamation-circle" class="mt-2 border-amber-200 bg-amber-50 text-amber-950 [&_[data-slot=heading]]:text-amber-950 [&_[data-slot=text]]:text-amber-900">
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
                                    'border-[#10B981] bg-[#10B981] text-white' => $selectedTime === $slot,
                                    'border-zinc-200 bg-white text-zinc-700 hover:border-[#10B981]/50' => $selectedTime !== $slot,
                                ])
                            >
                                {{ $this->displaySlot($slot) }}
                            </button>
                        @endforeach
                    </div>
                @endif
                <flux:error name="selectedTime" />
            </flux:field>

            <div class="hidden flex-col gap-3 sm:flex sm:flex-row sm:justify-end">
                <flux:button :href="route('patient.appointments', ['tab' => 'missed'])" wire:navigate variant="ghost">
                    {{ __('patient.missed.back') }}
                </flux:button>
                <flux:button type="submit" variant="primary" class="!bg-[#10B981] !text-white hover:!brightness-95">
                    {{ __('patient.scheduled_appointment.choose_appointment') }}
                </flux:button>
            </div>
        </form>
    </div>
    </div>

    <div class="pointer-events-none fixed inset-x-0 bottom-[calc(4.75rem+env(safe-area-inset-bottom))] z-40 sm:hidden">
        <div class="patient-luxury-booking-glass-bar pointer-events-auto px-6 py-4">
            <button
                type="submit"
                form="payment-missed-reschedule-form"
                wire:loading.attr="disabled"
                wire:target="save"
                class="flex w-full items-center justify-center gap-2 rounded-2xl bg-[#10B981] py-4 text-base font-bold text-white shadow-[0_8px_25px_-5px_rgba(16,185,129,0.3)] transition active:scale-[0.98] hover:bg-[#059669] disabled:opacity-70"
                data-test="patient-payment-missed-reschedule-submit"
            >
                <span wire:loading.remove wire:target="save">{{ __('patient.scheduled_appointment.choose_appointment') }}</span>
                <span wire:loading wire:target="save">{{ __('patient_booking.payment_processing') }}</span>
            </button>
        </div>
    </div>
</div>
