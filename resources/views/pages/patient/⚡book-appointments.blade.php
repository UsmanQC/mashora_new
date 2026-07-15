<?php

use App\Support\PendingPatientBooking;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\TemporaryAppointment;
use App\Models\User;
use App\Services\MyFatoorahInvoiceService;
use App\Services\PatientPaymentCompletionService;
use App\Services\PatientWalletService;
use App\Support\PaymentGateway;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
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
    public array $bookingChannels = ['chat', 'video', 'voice'];

    public string $patientName = '';

    public string $patientEmail = '';

    public string $patientPhone = '';

    public string $patientNotes = '';

    public string $discountCode = '';

    public int $mobileStep = 1;

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

        $this->doctor = $doctor->load('specialities:id,title,title_ar');

        PendingPatientBooking::forget();

        /** @var User|null $user */
        $user = auth()->user();
        if ($user instanceof User) {
            $this->patientName = (string) ($user->name ?? '');
            $this->patientEmail = (string) ($user->email ?? '');
            $this->patientPhone = (string) ($user->phone ?? '');
        }

        $this->bookingChannels = ['chat', 'video', 'voice'];
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

    public function displayedLuxurySessionDate(): string
    {
        try {
            return Carbon::parse($this->appointmentDate)
                ->locale(app()->getLocale())
                ->translatedFormat('l, j F Y');
        } catch (\Throwable) {
            return $this->displayedSessionDate();
        }
    }

    public function totalPrice(): float
    {
        return max(0, round($this->sessionPrice - $this->discountAmount, 2));
    }

    public function headerSubtitle(): string
    {
        return implode(' · ', [
            $this->doctor->displayName(),
            $this->displayedSessionDate(),
            $this->displayedSessionTime(),
        ]);
    }

    public function mobileHeaderTitle(): string
    {
        return $this->mobileStep === 1
            ? (string) __('patient_booking.luxury.intake_title')
            : (string) __('patient_booking.luxury.summary_title');
    }

    public function doctorPhotoUrl(): ?string
    {
        return $this->doctor->profilePhotoUrl();
    }

    public function doctorSpecialtyLabel(): string
    {
        $speciality = $this->doctor->specialities->first();

        if ($speciality === null) {
            return __('patient.appointments.specialist_label');
        }

        if (app()->getLocale() === 'ar' && filled($speciality->title_ar)) {
            return (string) $speciality->title_ar;
        }

        return (string) ($speciality->title ?? $speciality->title_ar ?? __('patient.appointments.specialist_label'));
    }

    public function selectAppointmentFor(string $value): void
    {
        if (! in_array($value, ['self', 'another'], true)) {
            return;
        }

        $this->appointmentFor = $value;
        $this->updatedAppointmentFor($value);
    }

    public function goToSummaryStep(): void
    {
        $this->validate([
            'appointmentFor' => ['required', 'in:self,another'],
            'patientName' => ['required', 'string', 'max:255'],
            'patientPhone' => ['required', 'string', 'max:32'],
            'patientNotes' => ['nullable', 'string', 'max:300'],
            'bookingChannels' => ['required', 'array', 'min:1'],
        ]);

        if (! in_array('chat', $this->bookingChannels, true)) {
            throw ValidationException::withMessages([
                'bookingChannels' => __('patient_booking.chat_required'),
            ]);
        }

        $this->mobileStep = 2;
    }

    public function goBackToIntakeStep(): void
    {
        $this->mobileStep = 1;
    }

    public function profilePhotoUrl(): ?string
    {
        $user = Auth::user();

        if ($user === null || ! filled($user->profile_photo_path)) {
            return null;
        }

        return Storage::disk('public')->url((string) $user->profile_photo_path);
    }

    public function toggleCommunication(string $channel): void
    {
        if ($channel === '') {
            return;
        }

        if (in_array($channel, $this->bookingChannels, true)) {
            $this->bookingChannels = array_values(array_filter(
                $this->bookingChannels,
                static fn (string $c): bool => $c !== $channel
            ));
        } else {
            $this->bookingChannels = array_values(array_unique([...$this->bookingChannels, $channel]));
        }
    }

    public function communicationSelected(string $channel): bool
    {
        return in_array($channel, $this->bookingChannels, true);
    }

    public function submitBooking(): void
    {
        $this->validate([
            'appointmentFor' => ['required', 'in:self,another'],
            'patientName' => ['required', 'string', 'max:255'],
            'patientEmail' => ['nullable', 'email', 'max:255'],
            'patientPhone' => ['required', 'string', 'max:32'],
            'patientNotes' => ['nullable', 'string', 'max:300'],
            'bookingChannels' => ['required', 'array', 'min:1'],
        ]);

        if (! in_array('chat', $this->bookingChannels, true)) {
            throw ValidationException::withMessages([
                'bookingChannels' => __('patient_booking.chat_required'),
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
                'appointment_type' => Session::get('instant_booking') ? 'instant' : 'regular',
                'payment_status' => 'unpaid',
            ]);
        });

        // MyFatoorah: skip intermediate checkout page — go straight to hosted payment (mobile Confirm & pay).
        if (PaymentGateway::isMyFatoorah()) {
            $this->redirectToMyFatoorahOrCheckout($temp);

            return;
        }

        $this->redirect(route('patient.checkout', $temp));
    }

    /**
     * Start MyFatoorah hosted payment after booking. Falls back to checkout if invoice creation fails.
     */
    private function redirectToMyFatoorahOrCheckout(TemporaryAppointment $temp): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        $walletService = app(PatientWalletService::class);
        $walletService->ensureWallet($user);

        $balance = $walletService->balance($user);
        $walletApplied = round(min($balance, (float) $temp->total), 2);
        $temp->wallet_amount = $walletApplied;
        $temp->save();

        $amountDue = PatientPaymentCompletionService::amountDue($temp);

        if ($amountDue <= 0) {
            $appointment = app(PatientPaymentCompletionService::class)->completeWithWalletOnly($temp->fresh());

            if ($appointment !== null) {
                $this->redirect(route('patient.payment.success', ['temporaryAppointment' => $temp->id]));

                return;
            }

            $this->redirect(route('patient.checkout', $temp));

            return;
        }

        if (empty(config('myfatoorah.api_key'))) {
            session()->flash('flash_payment', __('patient_booking.payment_api_missing'));
            $this->redirect(route('patient.checkout', $temp));

            return;
        }

        $invoice = app(MyFatoorahInvoiceService::class)->createBookingInvoice($temp, $amountDue, $user);

        if ($invoice !== null) {
            $temp->payment_invoice_id = $invoice['invoice_id'];
            $temp->payment_invoice_url = $invoice['invoice_url'];
            $temp->save();

            $this->redirect($invoice['invoice_url']);

            return;
        }

        // Fallback: show checkout if MyFatoorah invoice could not be started.
        session()->flash('flash_payment', __('patient_booking.payment_start_failed'));
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
            $this->bookingChannels
        ));
    }
}; ?>

<div class="patient-luxury-booking bg-slate-50 pb-[calc(8.5rem+env(safe-area-inset-bottom))] sm:bg-transparent sm:pb-12" data-test="patient-luxury-booking">
    <div class="sm:hidden">
        @include('partials.patient-luxury-page-header', [
            'title' => $this->mobileHeaderTitle(),
            'subtitle' => $this->headerSubtitle(),
            'profilePhotoUrl' => $this->profilePhotoUrl(),
            'userName' => auth()->user()?->name,
            'testId' => 'patient-booking-header',
            'progressStep' => $mobileStep,
            'progressTotal' => 2,
        ])

        @include('partials.patient-luxury-booking-mobile')
    </div>

    <div class="mx-auto hidden w-full max-w-7xl px-6 py-4 sm:block sm:px-0 sm:py-0">
        <header class="mb-8">
            <nav class="mb-3 text-sm text-zinc-600" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-2">
                    <li>
                        <flux:link :href="route('patient.schedule.specialists')" wire:navigate class="font-medium text-[#10B981] hover:text-[#064e3b]">
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

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:gap-10">
            <div class="order-2 space-y-6 lg:order-1 lg:space-y-8">
                <section
                    class="space-y-3 rounded-3xl border border-slate-100/80 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] sm:border-0 sm:bg-transparent sm:p-0 sm:shadow-none"
                    aria-labelledby="bk-for"
                >
                    <flux:heading id="bk-for" level="4" size="sm" class="text-sm font-semibold text-slate-700">
                        {{ __('patient_booking.booking_for_label') }}
                    </flux:heading>
                    <div class="patient-booking-pill-radios">
                        <flux:radio.group variant="pills" wire:model.live="appointmentFor">
                            <flux:radio value="self">{{ __('patient_booking.for_self') }}</flux:radio>
                            <flux:radio value="another">{{ __('patient_booking.for_other') }}</flux:radio>
                        </flux:radio.group>
                    </div>
                </section>

                <section
                    class="space-y-3 rounded-3xl border border-slate-100/80 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] sm:border-0 sm:bg-transparent sm:p-0 sm:shadow-none"
                    aria-labelledby="bk-comm"
                >
                    <flux:heading id="bk-comm" level="4" size="sm" class="text-sm font-semibold text-slate-700">
                        {{ __('patient_booking.communication_label') }}
                    </flux:heading>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['chat', 'video', 'voice'] as $ch)
                            <button
                                type="button"
                                wire:key="comm-{{ $ch }}"
                                wire:click="toggleCommunication('{{ $ch }}')"
                                aria-pressed="{{ $this->communicationSelected($ch) ? 'true' : 'false' }}"
                                @class([
                                    'rounded-full border px-3.5 py-2 text-xs font-semibold shadow-sm transition sm:text-sm',
                                    'border-[#10B981] bg-[#10B981]/10 text-[#059669]' => $this->communicationSelected($ch),
                                    'border-slate-200 bg-white text-slate-800 hover:border-slate-300' => ! $this->communicationSelected($ch),
                                ])
                            >
                                {{ __('patient_booking.channel_'.$ch) }}
                            </button>
                        @endforeach
                    </div>
                    @error('bookingChannels')
                        <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
                    @enderror
                </section>

                <form wire:submit="submitBooking" class="space-y-4">
                    @error('appointment_conflict')
                        <p class="rounded-2xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">{{ $message }}</p>
                    @enderror

                    <div class="space-y-4 rounded-3xl border border-slate-100/80 bg-white p-4 shadow-[0_4px_20px_-2px_rgba(0,0,0,0.03)] sm:border-0 sm:bg-transparent sm:p-0 sm:shadow-none">
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
                    </div>

                    <div class="hidden flex-col gap-3 sm:flex sm:flex-row sm:justify-end sm:pt-2">
                        <flux:button
                            variant="outline"
                            tag="a"
                            :href="route('patient.schedule.specialists')"
                            wire:navigate
                            class="order-2 w-full border-zinc-300! bg-white! text-zinc-800! shadow-xs sm:order-1 sm:w-auto"
                        >
                            {{ __('patient_booking.cancel') }}
                        </flux:button>
                        <flux:button
                            variant="primary"
                            type="submit"
                            class="order-1 w-full border-[#064e3b] !bg-[#064e3b] !text-white hover:!brightness-[0.97] sm:order-2 sm:w-auto"
                        >
                            {{ __('patient_booking.next') }}
                        </flux:button>
                    </div>
                </form>
            </div>

            <div class="order-1 lg:order-2">
                <div class="rounded-3xl border border-slate-100/80 bg-white p-5 shadow-[0_8px_32px_0_rgba(0,0,0,0.03)] sm:rounded-2xl sm:shadow-md sm:shadow-black/10">
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-3">
                        <span class="text-sm font-semibold text-slate-600">{{ __('patient_booking.specialist_name') }}</span>
                        <span class="min-w-0 text-end text-sm font-semibold text-slate-900">{{ $this->doctor->displayName() }}</span>
                    </div>
                    <div class="mt-3 flex justify-between gap-3 text-sm">
                        <span class="text-slate-600">{{ __('patient_booking.session_date') }}</span>
                        <span class="font-medium text-slate-900">{{ $this->displayedSessionDate() }}</span>
                    </div>
                    <div class="mt-2 flex justify-between gap-3 text-sm">
                        <span class="text-slate-600">{{ __('patient_booking.session_time') }}</span>
                        <span class="font-medium text-slate-900">{{ $this->displayedSessionTime() }}</span>
                    </div>
                    <div class="mt-2 flex justify-between gap-3 text-sm">
                        <span class="text-slate-600">{{ __('patient_booking.session_duration') }}</span>
                        <span class="font-medium text-slate-900">{{ $this->durationMinutes }}</span>
                    </div>
                    <hr class="my-4 border-slate-100" />
                    <div class="flex justify-between gap-3 text-sm">
                        <span class="text-slate-600">{{ __('patient_booking.session_price') }}</span>
                        <span class="font-medium tabular-nums text-slate-900">{{ number_format($this->sessionPrice, 2) }} {{ __('patient_booking.sar') }}</span>
                    </div>
                    <div class="mt-2 flex justify-between gap-3 text-sm">
                        <span class="text-slate-600">{{ __('patient_booking.discount') }}</span>
                        <span class="font-medium tabular-nums text-slate-900">{{ number_format($this->discountAmount, 2) }} {{ __('patient_booking.sar') }}</span>
                    </div>
                    <div class="mt-3 flex justify-between gap-3 border-t border-slate-100 pt-3 text-sm font-semibold">
                        <span class="text-slate-800">{{ __('patient_booking.total') }}</span>
                        <span class="tabular-nums text-[#059669]">{{ number_format($this->totalPrice(), 2) }} {{ __('patient_booking.sar') }}</span>
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

    <div class="pointer-events-none fixed inset-x-0 bottom-[calc(4.75rem+env(safe-area-inset-bottom))] z-40 sm:hidden">
        <div class="patient-luxury-booking-glass-bar pointer-events-auto px-6 py-4">
            @if ($mobileStep === 2)
                <button
                    type="button"
                    wire:click="goBackToIntakeStep"
                    class="mb-3 flex w-full items-center justify-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-slate-700"
                >
                    <flux:icon name="chevron-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}" variant="mini" class="size-4" />
                    {{ __('session_filter.back') }}
                </button>
            @endif

            @if ($mobileStep === 1)
                <button
                    type="button"
                    wire:click="goToSummaryStep"
                    wire:loading.attr="disabled"
                    wire:target="goToSummaryStep"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-[#10B981] py-4 text-base font-bold text-white shadow-[0_8px_25px_-5px_rgba(16,185,129,0.3)] transition active:scale-[0.98] hover:bg-[#059669] disabled:opacity-70"
                    data-test="patient-booking-continue"
                >
                    <span wire:loading.remove wire:target="goToSummaryStep">{{ __('patient_booking.luxury.continue') }}</span>
                    <span wire:loading wire:target="goToSummaryStep">{{ __('patient_booking.payment_processing') }}</span>
                    <flux:icon name="arrow-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}" variant="mini" class="size-5" />
                </button>
            @else
                <button
                    type="button"
                    wire:click="submitBooking"
                    wire:loading.attr="disabled"
                    wire:target="submitBooking"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-[#10B981] py-4 text-base font-bold text-white shadow-[0_8px_25px_-5px_rgba(16,185,129,0.3)] transition active:scale-[0.98] hover:bg-[#059669] disabled:opacity-70"
                    data-test="patient-booking-confirm-pay"
                >
                    <span wire:loading.remove wire:target="submitBooking">
                        {{ __('patient_booking.luxury.confirm_pay', [
                            'amount' => number_format($this->totalPrice(), 0),
                            'currency' => __('patient_booking.sar'),
                        ]) }}
                    </span>
                    <span wire:loading wire:target="submitBooking">{{ __('patient_booking.payment_processing') }}</span>
                    <flux:icon name="credit-card" variant="mini" class="size-5" />
                </button>
            @endif
        </div>
    </div>
</div>
