<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\TemporaryAppointment;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::patient')] #[Title('Book an appointment')] class extends Component
{
    public Doctor $doctor;

    public string $appointmentDate = '';

    public string $startTime = '';

    public int $durationMinutes = 15;

    public float $sessionPrice = 0;

    public float $discountAmount = 0;

    public string $appointmentFor = 'self';

    /** @var list<string> */
    public array $communications = ['chat'];

    public string $patientName = '';

    public string $patientEmail = '';

    public string $patientPhone = '';

    public string $patientNotes = '';

    public string $discountCode = '';

    public function mount(Doctor $doctor): void
    {
        $date = request()->query('date');
        $time = request()->query('time');
        $duration = (int) request()->query('duration', 0);

        if (! is_string($date) || $date === '' || ! is_string($time) || $time === '' || $duration < 1) {
            abort(404);
        }

        try {
            $this->appointmentDate = Carbon::parse($date)->format('Y-m-d');
        } catch (\Throwable) {
            abort(404);
        }

        try {
            $parsed = Carbon::parse($time);
        } catch (\Throwable) {
            abort(404);
        }

        $this->startTime = $parsed->format('H:i:s');
        $this->durationMinutes = $duration;

        $durationRow = $doctor->durations()->where('durations.duration', $duration)->first();
        if ($durationRow === null) {
            abort(404);
        }

        $this->sessionPrice = (float) ($durationRow->pivot->price ?? 0);

        $this->doctor = $doctor;

        /** @var User|null $user */
        $user = auth()->user();
        if ($user instanceof User) {
            $this->patientName = (string) ($user->name ?? '');
            $this->patientEmail = (string) ($user->email ?? '');
            $this->patientPhone = (string) ($user->phone ?? '');
        }
    }

    public function updatedAppointmentFor(string $value): void
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return;
        }

        if ($value === 'self') {
            $this->patientName = (string) ($user->name ?? '');
            $this->patientEmail = (string) ($user->email ?? '');
            $this->patientPhone = (string) ($user->phone ?? '');
        } else {
            $this->patientName = '';
            $this->patientEmail = '';
            $this->patientPhone = '';
        }
    }

    public function updatedDiscountCode(string $value): void
    {
        if (trim($value) === '') {
            $this->discountAmount = 0.0;
        }
    }

    public function applyDiscountCode(): void
    {
        $code = strtoupper(trim($this->discountCode));
        if ($code === '') {
            $this->discountAmount = 0.0;

            return;
        }

        $rules = config('patient_booking.discount_codes', []);
        if (! isset($rules[$code])) {
            throw ValidationException::withMessages([
                'discountCode' => __('patient_booking.discount_invalid'),
            ]);
        }

        $rule = $rules[$code];
        $type = $rule['type'] ?? 'fixed';

        if ($type === 'fixed') {
            $this->discountAmount = min((float) ($rule['amount'] ?? 0), $this->sessionPrice);
        } elseif ($type === 'percent') {
            $this->discountAmount = min(
                round($this->sessionPrice * (float) ($rule['percent'] ?? 0) / 100, 2),
                $this->sessionPrice
            );
        } else {
            throw ValidationException::withMessages([
                'discountCode' => __('patient_booking.discount_invalid'),
            ]);
        }

        Flux::toast(variant: 'success', text: __('patient_booking.discount_applied'));
    }

    public function displayedSessionDate(): string
    {
        try {
            return Carbon::parse($this->appointmentDate)->locale(app()->getLocale())->format(__('patient_booking.date_format'));
        } catch (\Throwable) {
            return $this->appointmentDate;
        }
    }

    public function displayedSessionTime(): string
    {
        try {
            return Carbon::createFromFormat('H:i:s', $this->startTime)
                ->timezone(config('app.timezone'))
                ->locale(app()->getLocale())
                ->translatedFormat('g:i a');
        } catch (\Throwable) {
            return $this->startTime;
        }
    }

    public function totalPrice(): float
    {
        return max(0, round($this->sessionPrice - $this->discountAmount, 2));
    }

    public function toggleCommunication(string $channel): void
    {
        if ($channel === '') {
            return;
        }

        if (in_array($channel, $this->communications, true)) {
            $this->communications = array_values(array_filter(
                $this->communications,
                static fn (string $c): bool => $c !== $channel
            ));
        } else {
            $this->communications = array_values(array_unique([...$this->communications, $channel]));
        }
    }

    public function communicationSelected(string $channel): bool
    {
        return in_array($channel, $this->communications, true);
    }

    public function submitBooking(): void
    {
        $this->validate([
            'appointmentFor' => ['required', 'in:self,another'],
            'patientName' => ['required', 'string', 'max:255'],
            'patientEmail' => ['nullable', 'email', 'max:255'],
            'patientPhone' => ['required', 'string', 'max:32'],
            'patientNotes' => ['nullable', 'string', 'max:300'],
            'communications' => ['required', 'array', 'min:1'],
        ]);

        if (! in_array('chat', $this->communications, true)) {
            throw ValidationException::withMessages([
                'communications' => __('patient_booking.chat_required'),
            ]);
        }

        $startTime = $this->startTime;
        $endTime = Carbon::parse($this->appointmentDate.' '.$this->startTime)
            ->addMinutes($this->durationMinutes)
            ->format('H:i:s');

        $conflictingTemp = TemporaryAppointment::query()
            ->availableForFiveMin($this->doctor->id, $startTime, $endTime, $this->appointmentDate)
            ->exists();

        $conflictingAppointment = Appointment::query()
            ->whereIn('status', ['new', 'in_process', 'pending_follow_up'])
            ->availableFor($this->doctor->id, $startTime, $endTime, $this->appointmentDate)
            ->exists();

        if ($conflictingTemp || $conflictingAppointment) {
            throw ValidationException::withMessages([
                'appointment_conflict' => __('patient_booking.slot_conflict'),
            ]);
        }

        $scheduledAt = Carbon::parse($this->appointmentDate.' '.$this->startTime)->format('Y-m-d H:i:s');
        $extendAt = Carbon::parse($this->appointmentDate.' '.$this->startTime)
            ->addMinutes($this->durationMinutes)
            ->format('Y-m-d H:i:s');

        $temp = DB::transaction(function () use ($scheduledAt, $extendAt, $endTime): TemporaryAppointment {
            return TemporaryAppointment::create([
                'user_id' => (int) auth()->id(),
                'doctor_id' => $this->doctor->id,
                'scheduled_at' => $scheduledAt,
                'appointment_date' => $this->appointmentDate,
                'start_time' => $this->startTime,
                'end_time' => $endTime,
                'duration' => $this->durationMinutes,
                'extend_at' => $extendAt,
                'appointment_for' => $this->appointmentFor,
                'patient_name' => $this->patientName,
                'patient_email' => $this->patientEmail !== '' ? $this->patientEmail : null,
                'patient_phone' => $this->patientPhone,
                'patient_notes' => $this->patientNotes !== '' ? $this->patientNotes : null,
                'communications' => $this->communicationsForDatabase(),
                'amount' => $this->sessionPrice,
                'discount' => $this->discountAmount,
                'tax' => 0.0,
                'total' => $this->totalPrice(),
                'appointment_type' => 'regular',
                'payment_status' => 'unpaid',
            ]);
        });

        $this->redirect(route('patient.checkout', $temp));
    }

    /**
     * Aligns UI channel keys with legacy values stored for appointments (see Mashorapwa-prod).
     *
     * @return list<string>
     */
    private function communicationsForDatabase(): array
    {
        return array_values(array_map(
            fn (string $channel): string => match ($channel) {
                'video' => 'video_call',
                'voice' => 'voice_call',
                default => $channel,
            },
            $this->communications
        ));
    }
}; ?>

<div class="mx-auto max-w-5xl px-4 py-6 pb-28 sm:pb-12">
    <header class="mb-8">
        <nav class="mb-3 text-sm text-zinc-600" aria-label="Breadcrumb">
            <ol class="flex flex-wrap items-center gap-2">
                <li>
                    <flux:link :href="route('patient.schedule.specialists')" wire:navigate class="font-medium text-[#1565c0] hover:text-[#0B163E]">
                        {{ __('patient_booking.crumb_find_specialist') }}
                    </flux:link>
                </li>
                <li aria-hidden="true" class="text-zinc-400">/</li>
                <li class="font-semibold text-zinc-900">{{ __('patient_booking.crumb_book') }}</li>
            </ol>
        </nav>
        <flux:heading size="xl" class="font-semibold text-zinc-900">
            {{ __('patient_booking.title') }}
        </flux:heading>
    </header>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-10">
        <div class="space-y-8">
            <section class="space-y-3" aria-labelledby="bk-for">
                <flux:heading id="bk-for" level="4" size="sm" class="text-zinc-600">
                    {{ __('patient_booking.booking_for_label') }}
                </flux:heading>
                <flux:radio.group variant="pills" wire:model.live="appointmentFor">
                    <flux:radio value="self">{{ __('patient_booking.for_self') }}</flux:radio>
                    <flux:radio value="another">{{ __('patient_booking.for_other') }}</flux:radio>
                </flux:radio.group>
            </section>

            <section class="space-y-3" aria-labelledby="bk-comm">
                <flux:heading id="bk-comm" level="4" size="sm" class="text-zinc-600">
                    {{ __('patient_booking.communication_label') }}
                </flux:heading>
                <div class="flex flex-wrap gap-2">
                    @foreach (['chat', 'video', 'voice'] as $ch)
                        <button
                            type="button"
                            wire:key="comm-{{ $ch }}"
                            wire:click="toggleCommunication('{{ $ch }}')"
                            aria-pressed="{{ $this->communicationSelected($ch) ? 'true' : 'false' }}"
                            class="@if ($this->communicationSelected($ch)) border-mashora-brand bg-mashora-brand/12 text-mashora-brand @else border-zinc-200 bg-white text-zinc-800 hover:border-zinc-300 @endif rounded-full border px-3 py-1.5 text-xs font-semibold shadow-sm transition sm:text-sm"
                        >
                            {{ __('patient_booking.channel_'.$ch) }}
                        </button>
                    @endforeach
                </div>
                @error('communications')
                    <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
                @enderror
            </section>

            <form wire:submit="submitBooking" class="space-y-4">
                @error('appointment_conflict')
                    <p class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">{{ $message }}</p>
                @enderror
                <flux:field>
                    <flux:label>{{ __('patient_booking.patient_name') }}</flux:label>
                    <flux:input wire:model.blur="patientName" type="text" autocomplete="name" required />
                    <flux:error name="patientName" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('patient_booking.patient_email') }}</flux:label>
                    <flux:input wire:model.blur="patientEmail" type="email" autocomplete="email" />
                    <flux:error name="patientEmail" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('patient_booking.patient_phone') }}</flux:label>
                    <flux:input wire:model.blur="patientPhone" type="tel" autocomplete="tel" required />
                    <flux:error name="patientPhone" />
                </flux:field>
                <flux:field>
                    <flux:label>{{ __('patient_booking.patient_notes') }}</flux:label>
                    <flux:textarea wire:model.blur="patientNotes" rows="3" maxlength="300" />
                    <flux:error name="patientNotes" />
                </flux:field>

                <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:justify-end">
                    <flux:button variant="ghost" tag="a" :href="route('patient.schedule.specialists')" wire:navigate>
                        {{ __('patient_booking.cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit" class="border-[#0B163E] !bg-[#0B163E] !text-white hover:!brightness-[0.97]">
                        {{ __('patient_booking.next') }}
                    </flux:button>
                </div>
            </form>
        </div>

        <div>
            <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-md shadow-black/10">
                <div class="flex justify-between gap-3 border-b border-zinc-100 pb-3">
                    <span class="text-sm font-semibold text-zinc-600">{{ __('patient_booking.specialist_name') }}</span>
                    <span class="min-w-0 text-end text-sm font-semibold text-zinc-900">{{ $this->doctor->displayName() }}</span>
                </div>
                <div class="mt-3 flex justify-between gap-3 text-sm">
                    <span class="text-zinc-600">{{ __('patient_booking.session_date') }}</span>
                    <span class="font-medium text-zinc-900">{{ $this->displayedSessionDate() }}</span>
                </div>
                <div class="mt-2 flex justify-between gap-3 text-sm">
                    <span class="text-zinc-600">{{ __('patient_booking.session_time') }}</span>
                    <span class="font-medium text-zinc-900">{{ $this->displayedSessionTime() }}</span>
                </div>
                <div class="mt-2 flex justify-between gap-3 text-sm">
                    <span class="text-zinc-600">{{ __('patient_booking.session_duration') }}</span>
                    <span class="font-medium text-zinc-900">{{ $this->durationMinutes }}</span>
                </div>
                <hr class="my-4 border-zinc-100" />
                <div class="flex justify-between gap-3 text-sm">
                    <span class="text-zinc-600">{{ __('patient_booking.session_price') }}</span>
                    <span class="font-medium tabular-nums text-zinc-900">{{ number_format($this->sessionPrice, 2) }} {{ __('patient_booking.sar') }}</span>
                </div>
                <div class="mt-2 flex justify-between gap-3 text-sm">
                    <span class="text-zinc-600">{{ __('patient_booking.discount') }}</span>
                    <span class="font-medium tabular-nums text-zinc-900">{{ number_format($this->discountAmount, 2) }} {{ __('patient_booking.sar') }}</span>
                </div>
                <div class="mt-3 flex justify-between gap-3 border-t border-zinc-100 pt-3 text-sm font-semibold">
                    <span class="text-zinc-800">{{ __('patient_booking.total') }}</span>
                    <span class="tabular-nums text-zinc-900">{{ number_format($this->totalPrice(), 2) }} {{ __('patient_booking.sar') }}</span>
                </div>
                <div class="mt-6">
                    <flux:field>
                        <flux:label>{{ __('patient_booking.discount_code') }}</flux:label>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                            <div class="min-w-0 flex-1">
                                <flux:input wire:model.live="discountCode" type="text" autocomplete="off" />
                            </div>
                            <flux:button type="button" variant="outline" wire:click="applyDiscountCode" class="shrink-0">
                                {{ __('patient_booking.discount_apply') }}
                            </flux:button>
                        </div>
                        <flux:error name="discountCode" />
                    </flux:field>
                </div>
            </div>
        </div>
    </div>
</div>
