<?php

use App\Livewire\Concerns\InteractsWithPatientWalletPayment;
use App\Models\Doctor;
use App\Models\TemporaryAppointment;
use App\Services\PatientPaymentCompletionService;
use App\Services\StripeCheckoutService;
use App\Support\PaymentGateway;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use MyFatoorah\Library\MyFatoorah;
use MyFatoorah\Library\API\Payment\MyFatoorahPayment;

new #[Layout('layouts::patient')] #[Title('Payment')] class extends Component
{
    use InteractsWithPatientWalletPayment;

    public TemporaryAppointment $temporaryAppointment;

    public string $paymentError = '';
    public bool $embeddedReady = false;
    public string $mfSessionId = '';
    public string $mfCountryCode = '';
    public string $mfJsDomain = '';

    public function mount(TemporaryAppointment $temporaryAppointment): void
    {
        if ($temporaryAppointment->user_id !== auth()->id()) {
            abort(403);
        }

        $this->temporaryAppointment = $temporaryAppointment->load('doctor');
        $this->bootPatientWalletPayment((float) $this->temporaryAppointment->wallet_amount);
        $this->persistWalletAmountOnBooking();

        if ($this->usesMyFatoorah()) {
            $this->initEmbeddedPaymentSession();
        }
    }

    public function usesStripe(): bool
    {
        return PaymentGateway::isStripe();
    }

    public function usesMyFatoorah(): bool
    {
        return PaymentGateway::isMyFatoorah();
    }

    public function paymentGatewayConfigured(): bool
    {
        return PaymentGateway::isConfigured();
    }

    public function walletBalance(): float
    {
        return $this->patientWalletBalance();
    }

    public function walletApplied(): float
    {
        return $this->walletAppliedToward((float) $this->temporaryAppointment->total);
    }

    public function amountDue(): float
    {
        return $this->amountDueAfterWallet((float) $this->temporaryAppointment->total);
    }

    public function updatedUseWallet(): void
    {
        $this->persistWalletAmountOnBooking();
    }

    private function persistWalletAmountOnBooking(): void
    {
        $this->temporaryAppointment->wallet_amount = $this->walletApplied();
        $this->temporaryAppointment->save();
    }

    public function payWithWalletOnly(): void
    {
        if ($this->temporaryAppointment->user_id !== auth()->id()) {
            abort(403);
        }

        $this->temporaryAppointment->wallet_amount = $this->walletApplied();
        $this->temporaryAppointment->save();

        if ($this->amountDue() > 0) {
            $this->paymentError = __('patient_booking.wallet_insufficient');

            return;
        }

        $completion = app(PatientPaymentCompletionService::class);
        $appointment = $completion->completeWithWalletOnly($this->temporaryAppointment->fresh());

        if ($appointment !== null) {
            $this->redirect(route('patient.payment.success', ['temporaryAppointment' => $this->temporaryAppointment->id]));

            return;
        }

        $this->paymentError = __('patient_booking.payment_start_failed');
    }

    public function initEmbeddedPaymentSession(): void
    {
        if ($this->myFatoorahConfigError() !== null) {
            $this->paymentError = $this->myFatoorahConfigError() ?? '';

            return;
        }

        try {
            $mfConfig = [
                'apiKey' => (string) config('myfatoorah.api_key'),
                'isTest' => (bool) config('myfatoorah.is_test'),
                'vcCode' => (string) config('myfatoorah.vc_code'),
            ];

            $temp = $this->temporaryAppointment->fresh();
            if ($temp === null) {
                return;
            }

            $mfObj = new MyFatoorahPayment($mfConfig);
            $mfSession = $mfObj->getEmbeddedSession('PATIENT-'.$temp->user_id);

            $temp->payment_session_id = (string) ($mfSession->SessionId ?? '');
            $temp->save();

            $countries = MyFatoorah::getMFCountries();
            $vcCode = (string) config('myfatoorah.vc_code');
            $isTest = (bool) config('myfatoorah.is_test');
            $country = $countries[$vcCode] ?? null;

            if ($country === null) {
                return;
            }

            $this->mfSessionId = (string) ($mfSession->SessionId ?? '');
            $this->mfCountryCode = (string) ($mfSession->CountryCode ?? $vcCode);
            $this->mfJsDomain = (string) ($isTest ? ($country['testPortal'] ?? '') : ($country['portal'] ?? ''));
            $this->embeddedReady = $this->mfSessionId !== '' && $this->mfJsDomain !== '';
        } catch (\Throwable $e) {
            report($e);
            $this->embeddedReady = false;
            $this->paymentError = $this->paymentErrorFrom($e);
        }
    }

    public function paymentErrorFrom(\Throwable $e): string
    {
        if ($this->myFatoorahConfigError() !== null && str_contains($e->getMessage(), 'Kindly review your MyFatoorah admin configuration')) {
            return $this->myFatoorahConfigError() ?? __('patient_booking.payment_gateway_misconfigured');
        }

        if (str_contains($e->getMessage(), 'Kindly review your MyFatoorah admin configuration')) {
            return __('patient_booking.payment_gateway_misconfigured');
        }

        if (str_contains(strtolower($e->getMessage()), 'ssl certificate')) {
            return app()->isLocal()
                ? __('patient_booking.payment_ssl_local')
                : __('patient_booking.payment_start_failed');
        }

        if (empty(config('myfatoorah.api_key'))) {
            return __('patient_booking.payment_api_missing');
        }

        return app()->isLocal()
            ? __('patient_booking.payment_start_failed')." ({$e->getMessage()})"
            : __('patient_booking.payment_start_failed');
    }

    public function myFatoorahConfigError(): ?string
    {
        if (empty(config('myfatoorah.api_key'))) {
            return __('patient_booking.payment_api_missing');
        }

        if (! config('myfatoorah.is_test') && ! filled(env('MYFATOORAH_API_KEY')) && ! filled(env('MYFATOORAH_TOKEN'))) {
            return __('patient_booking.payment_live_key_missing');
        }

        return null;
    }

    public function formattedDate(): string
    {
        try {
            return Carbon::parse($this->temporaryAppointment->appointment_date)
                ->locale(app()->getLocale())
                ->format(__('patient_booking.date_format'));
        } catch (\Throwable) {
            return (string) $this->temporaryAppointment->appointment_date;
        }
    }

    public function formattedTime(): string
    {
        try {
            return Carbon::createFromFormat('H:i:s', (string) $this->temporaryAppointment->start_time)
                ->timezone(config('app.timezone'))
                ->locale(app()->getLocale())
                ->translatedFormat('g:i a');
        } catch (\Throwable) {
            return (string) $this->temporaryAppointment->start_time;
        }
    }

    public function specialistName(): string
    {
        $doctor = $this->temporaryAppointment->doctor;
        if ($doctor instanceof Doctor) {
            return $doctor->displayName();
        }

        return '';
    }

    public function startCardPayment(): void
    {
        if ($this->usesStripe()) {
            $this->startStripePayment();

            return;
        }

        $this->startMyFatoorahPayment();
    }

    public function startStripePayment(): void
    {
        if ($this->temporaryAppointment->user_id !== auth()->id()) {
            abort(403);
        }

        $this->temporaryAppointment->wallet_amount = $this->walletApplied();
        $this->temporaryAppointment->save();

        if ($this->amountDue() <= 0) {
            $this->payWithWalletOnly();

            return;
        }

        if (! PaymentGateway::isConfigured()) {
            $this->paymentError = __('patient_booking.payment_stripe_missing');

            return;
        }

        try {
            /** @var StripeCheckoutService $stripe */
            $stripe = app(StripeCheckoutService::class);
            $temp = $this->temporaryAppointment->fresh();

            if ($temp === null) {
                abort(404);
            }

            $session = $stripe->createBookingSession($temp, PatientPaymentCompletionService::amountDue($temp));

            $temp->payment_session_id = (string) $session->id;
            $temp->save();

            if (filled($session->url)) {
                $this->redirect($session->url);

                return;
            }

            $this->paymentError = __('patient_booking.payment_invoice_failed');
        } catch (\Throwable $e) {
            report($e);
            $this->paymentError = app()->isLocal()
                ? __('patient_booking.payment_start_failed')." ({$e->getMessage()})"
                : __('patient_booking.payment_start_failed');
        }
    }

    public function startMyFatoorahPayment(): void
    {
        if ($this->temporaryAppointment->user_id !== auth()->id()) {
            abort(403);
        }

        $this->temporaryAppointment->wallet_amount = $this->walletApplied();
        $this->temporaryAppointment->save();

        if ($this->amountDue() <= 0) {
            $this->payWithWalletOnly();

            return;
        }

        if ($this->myFatoorahConfigError() !== null) {
            $this->paymentError = $this->myFatoorahConfigError() ?? '';

            return;
        }

        try {
            $mfConfig = [
                'apiKey' => config('myfatoorah.api_key'),
                'isTest' => (bool) config('myfatoorah.is_test'),
                'vcCode' => (string) config('myfatoorah.vc_code'),
            ];

            $temp = $this->temporaryAppointment->fresh();
            if ($temp === null) {
                abort(404);
            }

            $mfObj = new MyFatoorahPayment($mfConfig);

            $paymentData = [
                'NotificationOption' => 'LNK',
                'CustomerName' => (string) ($temp->patient_name ?: auth()->user()?->name ?: 'Patient'),
                'InvoiceValue' => PatientPaymentCompletionService::amountDue($temp),
                'DisplayCurrencyIso' => 'SAR',
                'CallBackUrl' => route('patient.payment.success', ['temporaryAppointment' => $temp->id]),
                'ErrorUrl' => route('patient.payment.failed', ['temporaryAppointment' => $temp->id]),
                'CustomerReference' => $temp->id,
                'Language' => app()->getLocale() === 'ar' ? 'ar' : 'en',
            ];

            $data = $mfObj->sendPayment($paymentData);

            $invoiceUrl = $data->InvoiceURL ?? null;
            $invoiceId = $data->InvoiceId ?? null;

            $temp->payment_invoice_id = $invoiceId !== null ? (string) $invoiceId : null;
            $temp->payment_invoice_url = $invoiceUrl !== null ? (string) $invoiceUrl : null;
            $temp->save();

            if (is_string($invoiceUrl) && $invoiceUrl !== '') {
                $this->redirect($invoiceUrl);

                return;
            }

            $this->paymentError = __('patient_booking.payment_invoice_failed');
        } catch (\Throwable $e) {
            report($e);

            // Fallback to embedded session invoice URL when available.
            try {
                $temp = $this->temporaryAppointment->fresh();
                if ($temp !== null && filled($temp->payment_session_id)) {
                    $mfConfig = [
                        'apiKey' => config('myfatoorah.api_key'),
                        'isTest' => (bool) config('myfatoorah.is_test'),
                        'vcCode' => (string) config('myfatoorah.vc_code'),
                    ];

                    $mfObj = new MyFatoorahPayment($mfConfig);
                    $mfInvoiceData = $mfObj->getInvoiceURL([
                        'SessionId' => $temp->payment_session_id,
                        'CustomerName' => (string) ($temp->patient_name ?: auth()->user()?->name ?: 'Patient'),
                        'InvoiceValue' => PatientPaymentCompletionService::amountDue($temp),
                        'CallBackUrl' => route('patient.payment.success', ['temporaryAppointment' => $temp->id]),
                        'ErrorUrl' => route('patient.payment.failed', ['temporaryAppointment' => $temp->id]),
                        'CustomerReference' => $temp->id,
                        'Language' => app()->getLocale() === 'ar' ? 'ar' : 'en',
                    ], 0, null, $temp->payment_session_id);

                    if (! empty($mfInvoiceData['invoiceURL'])) {
                        $temp->payment_invoice_url = $mfInvoiceData['invoiceURL'];
                        $temp->payment_invoice_id = isset($mfInvoiceData['invoiceId']) ? (string) $mfInvoiceData['invoiceId'] : null;
                        $temp->save();

                        $this->redirect($mfInvoiceData['invoiceURL']);

                        return;
                    }
                }
            } catch (\Throwable $fallbackException) {
                report($fallbackException);
            }

            // Local SSL-chain issues (common on Windows dev) should not block end-to-end checkout testing.
            if (app()->isLocal() && str_contains(strtolower($e->getMessage()), 'ssl certificate')) {
                /** @var PatientPaymentCompletionService $completion */
                $completion = app(PatientPaymentCompletionService::class);
                $appointment = $completion->forceCompleteForTesting($this->temporaryAppointment);

                if ($appointment !== null) {
                    $this->redirect(route('patient.payment.success', ['temporaryAppointment' => $this->temporaryAppointment->id]));

                    return;
                }
            }

            $this->paymentError = $this->paymentErrorFrom($e);
        }
    }
}; ?>

<div class="pb-28 sm:pb-10">
    <div class="w-full space-y-5 lg:space-y-6">
        @if (session('flash_payment'))
            <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ session('flash_payment') }}</p>
        @endif

        {{-- Booking steps --}}
        <nav class="flex flex-wrap items-center gap-2 text-xs font-semibold sm:text-sm" aria-label="{{ __('patient_booking.checkout_title') }}">
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-[#10B981]">{{ __('patient_booking.crumb_find_specialist') }}</span>
            <flux:icon name="chevron-right" variant="mini" class="size-4 text-zinc-300 rtl:rotate-180" />
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-[#10B981]">{{ __('patient_booking.crumb_book') }}</span>
            <flux:icon name="chevron-right" variant="mini" class="size-4 text-zinc-300 rtl:rotate-180" />
            <span class="rounded-full bg-[#10B981] px-3 py-1 text-white">{{ __('patient_booking.checkout_title') }}</span>
        </nav>

        @php
            $checkoutTotal = (float) $this->temporaryAppointment->total;
            $checkoutDue = $this->amountDue();
            $showAmountDue = $this->walletApplied() > 0 && $checkoutDue < $checkoutTotal;
        @endphp

        {{-- Hero — soft vertical split --}}
        <header class="grid min-h-[10.5rem] grid-rows-2 overflow-hidden rounded-2xl border border-emerald-100/90 bg-white shadow-sm">
            <div class="flex items-center gap-4 bg-emerald-50/70 p-4 sm:p-5">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[#10B981]/12 text-[#10B981] sm:size-12">
                    <flux:icon name="credit-card" variant="mini" class="size-5 sm:size-6" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-zinc-900 sm:text-base">{{ __('patient_booking.checkout_title') }}</p>
                    <p class="mt-0.5 text-xs text-zinc-600 sm:text-sm">{{ __('patient_booking.checkout_subtitle') }}</p>
                </div>
            </div>
            <div class="flex flex-col justify-center border-t border-emerald-100/90 bg-white px-4 py-4 sm:px-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    {{ $showAmountDue ? __('patient_booking.amount_due') : __('patient_booking.total') }}
                </p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-[#10B981] sm:text-3xl">
                    {{ number_format($showAmountDue ? $checkoutDue : $checkoutTotal, 2) }}
                    <span class="text-lg font-semibold sm:text-xl">{{ __('patient_booking.sar') }}</span>
                </p>
                @if ($showAmountDue)
                    <p class="mt-1 text-xs text-zinc-500">
                        {{ __('patient_booking.total') }}:
                        <span class="tabular-nums line-through">{{ number_format($checkoutTotal, 2) }} {{ __('patient_booking.sar') }}</span>
                    </p>
                @endif
            </div>
        </header>

        <div class="grid gap-5 lg:grid-cols-5 lg:items-stretch lg:gap-6">
            {{-- Session summary --}}
            <div class="flex h-full flex-col lg:col-span-3">
                <div class="flex h-full flex-col overflow-hidden rounded-2xl border border-zinc-200/90 bg-white shadow-sm">
                    <div class="border-b border-zinc-100 bg-zinc-50/80 px-5 py-4">
                        <div class="flex items-center gap-3">
                            <flux:avatar :name="$this->specialistName()" circle class="shrink-0 ring-2 ring-[#10B981]/15" />
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('patient_booking.specialist_name') }}</p>
                                <p class="truncate text-base font-semibold text-zinc-900">{{ $this->specialistName() }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3 px-5 py-4 sm:grid-cols-3">
                        <div class="rounded-xl border border-zinc-100 bg-zinc-50/50 px-3 py-2.5">
                            <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-zinc-500">{{ __('patient_booking.session_date') }}</p>
                            <p class="mt-1 text-sm font-semibold text-zinc-900">{{ $this->formattedDate() }}</p>
                        </div>
                        <div class="rounded-xl border border-zinc-100 bg-zinc-50/50 px-3 py-2.5">
                            <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-zinc-500">{{ __('patient_booking.session_time') }}</p>
                            <p class="mt-1 text-sm font-semibold text-zinc-900">{{ $this->formattedTime() }}</p>
                        </div>
                        <div class="rounded-xl border border-zinc-100 bg-zinc-50/50 px-3 py-2.5 sm:col-span-1">
                            <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-zinc-500">{{ __('patient_booking.session_duration') }}</p>
                            <p class="mt-1 text-sm font-semibold text-zinc-900">{{ $this->temporaryAppointment->duration }}</p>
                        </div>
                    </div>

                    <div class="space-y-2 border-t border-zinc-100 px-5 py-4 text-sm">
                        <div class="flex justify-between gap-3">
                            <span class="text-zinc-600">{{ __('patient_booking.session_price') }}</span>
                            <span class="font-medium tabular-nums text-zinc-900">{{ number_format((float) $this->temporaryAppointment->amount, 2) }} {{ __('patient_booking.sar') }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-zinc-600">{{ __('patient_booking.discount') }}</span>
                            <span class="font-medium tabular-nums text-zinc-900">{{ number_format((float) $this->temporaryAppointment->discount, 2) }} {{ __('patient_booking.sar') }}</span>
                        </div>
                        <div class="flex justify-between gap-3 border-t border-zinc-100 pt-3 font-semibold">
                            <span class="text-zinc-800">{{ __('patient_booking.total') }}</span>
                            <span class="tabular-nums text-zinc-900">{{ number_format($checkoutTotal, 2) }} {{ __('patient_booking.sar') }}</span>
                        </div>
                    </div>

                    @if ($this->walletBalance() > 0)
                        <div class="border-t border-zinc-100 px-5 py-4">
                            <div class="flex items-start justify-between gap-3 rounded-xl border border-[#10B981]/15 bg-[#10B981]/5 p-3">
                                <div class="min-w-0 text-sm">
                                    <span class="font-semibold text-zinc-800">{{ __('patient_booking.use_wallet') }}</span>
                                    <span class="mt-1 block text-xs text-zinc-500">
                                        {{ __('patient_booking.wallet_balance') }}:
                                        {{ number_format($this->walletBalance(), 2) }} {{ __('patient_booking.sar') }}
                                    </span>
                                    <flux:link :href="route('patient.wallet')" wire:navigate class="mt-1 inline-block text-xs font-medium text-[#10B981]">
                                        {{ __('patient_booking.view_wallet') }}
                                    </flux:link>
                                </div>
                                <flux:switch wire:model.live="useWallet" />
                            </div>

                            @if ($this->walletApplied() > 0)
                                <div class="mt-3 space-y-2 text-sm">
                                    <div class="flex justify-between gap-3">
                                        <span class="text-zinc-600">{{ __('patient_booking.wallet_applied') }}</span>
                                        <span class="font-medium tabular-nums text-emerald-600">- {{ number_format($this->walletApplied(), 2) }} {{ __('patient_booking.sar') }}</span>
                                    </div>
                                    <div class="flex justify-between gap-3 rounded-lg bg-emerald-50/80 px-3 py-2 font-semibold text-[#10B981]">
                                        <span>{{ __('patient_booking.amount_due') }}</span>
                                        <span class="tabular-nums">{{ number_format($checkoutDue, 2) }} {{ __('patient_booking.sar') }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Payment --}}
            <div class="flex h-full flex-col lg:col-span-2">
                <div class="flex h-full flex-col rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-2 border-b border-zinc-100 pb-4">
                    <span class="flex size-9 items-center justify-center rounded-lg bg-[#10B981]/10 text-[#10B981]">
                        <flux:icon name="lock-closed" variant="mini" class="size-4" />
                    </span>
                    <flux:heading size="sm" class="font-semibold text-zinc-900">{{ __('patient_booking.checkout_accepts') }}</flux:heading>
                </div>

                @if ($paymentError !== '')
                    <p class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $paymentError }}</p>
                @endif

                <div class="flex min-h-0 flex-1 flex-col pt-4">
                    <div class="space-y-3">
                    @if ($this->walletApplied() > 0 && $checkoutDue <= 0)
                        <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            {{ __('patient_booking.wallet_covers_full') }}
                        </p>
                        <flux:button type="button" variant="primary" class="min-h-11 w-full !border-[#10B981] !bg-[#10B981] !text-white hover:!brightness-[0.97]" wire:click="payWithWalletOnly" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="payWithWalletOnly">{{ __('patient_booking.pay_with_wallet_only') }}</span>
                            <span wire:loading wire:target="payWithWalletOnly">{{ __('patient_booking.payment_processing') }}</span>
                        </flux:button>
                    @elseif ($this->paymentGatewayConfigured())
                        @if ($this->walletApplied() > 0 && $checkoutDue > 0)
                            <p class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                                {{ __('patient_booking.wallet_partial_hint') }}
                            </p>
                        @endif
                        @if ($this->usesStripe())
                            <flux:button
                                type="button"
                                variant="primary"
                                class="min-h-11 w-full !border-[#10B981] !bg-[#10B981] !text-white shadow-md shadow-[#10B981]/20 hover:!brightness-[0.97]"
                                wire:click="startStripePayment"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove wire:target="startStripePayment">
                                    @if ($this->walletApplied() > 0)
                                        {{ __('patient_booking.pay_wallet_and_card', [
                                            'wallet' => number_format($this->walletApplied(), 2),
                                            'due' => number_format($checkoutDue, 2),
                                        ]) }}
                                    @else
                                        {{ __('patient_booking.pay_now_stripe') }}
                                    @endif
                                </span>
                                <span wire:loading wire:target="startStripePayment">{{ __('patient_booking.payment_processing') }}</span>
                            </flux:button>

                            <flux:text class="text-center text-xs text-zinc-500">{{ __('patient_booking.payment_stripe_note') }}</flux:text>
                        @elseif ($embeddedReady)
                            <div id="mf-form-element" class="min-h-[155px] w-full rounded-xl border border-zinc-200 bg-white p-2"></div>
                            <p id="mf-card-error" class="hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                {{ __('patient_booking.payment_embedded_unavailable') }}
                            </p>

                            <button
                                type="button"
                                id="embedded-pay-now"
                                class="min-h-11 w-full rounded-xl border border-[#10B981] bg-[#10B981] py-3 text-sm font-semibold text-white shadow-md shadow-[#10B981]/20 transition hover:brightness-[0.97]"
                            >
                                {{ __('patient_booking.pay_now') }}
                            </button>

                            <flux:button
                                type="button"
                                variant="ghost"
                                class="w-full"
                                wire:click="startCardPayment"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove wire:target="startCardPayment">{{ __('patient_booking.pay_now_fallback') }}</span>
                                <span wire:loading wire:target="startCardPayment">{{ __('patient_booking.payment_processing') }}</span>
                            </flux:button>
                        @else
                            <flux:button
                                type="button"
                                variant="primary"
                                class="min-h-11 w-full !border-[#10B981] !bg-[#10B981] !text-white shadow-md shadow-[#10B981]/20 hover:!brightness-[0.97]"
                                wire:click="startCardPayment"
                                wire:loading.attr="disabled"
                            >
                                <span wire:loading.remove wire:target="startCardPayment">{{ __('patient_booking.pay_now') }}</span>
                                <span wire:loading wire:target="startCardPayment">{{ __('patient_booking.payment_processing') }}</span>
                            </flux:button>
                        @endif

                        @if ($this->usesMyFatoorah())
                            <flux:text class="text-center text-xs text-zinc-500">{{ __('patient_booking.payment_secure_note') }}</flux:text>
                        @endif
                    @else
                        <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            {{ $this->usesStripe() ? __('patient_booking.payment_stripe_missing') : __('patient_booking.payment_api_missing') }}
                        </p>
                    @endif
                    </div>

                    <flux:button :href="route('patient.home')" wire:navigate variant="ghost" class="mt-auto w-full pt-4">
                        {{ __('patient_booking.back_home') }}
                    </flux:button>
                </div>
                </div>
            </div>
        </div>
    </div>

    @if ($this->usesMyFatoorah() && $embeddedReady && $this->amountDue() > 0)
        <form id="embedded-exec-form" action="{{ route('patient.payment.execute', ['temporaryAppointment' => $temporaryAppointment->id]) }}" method="POST" class="hidden">
            @csrf
        </form>

        <script src="{{ $mfJsDomain }}/cardview/v2/session.js" id="mf-session-js"></script>
        <script>
            (function () {
                const showCardError = () => {
                    const box = document.getElementById('mf-card-error');
                    if (box) {
                        box.classList.remove('hidden');
                    }
                };

                const startEmbedded = () => {
                    if (!window.myFatoorah) {
                        showCardError();
                        return;
                    }

                    const mfConfig = {
                        countryCode: @js($mfCountryCode),
                        sessionId: @js($mfSessionId),
                        cardViewId: "mf-form-element",
                        style: {
                            direction: @js(App::isLocale('ar') ? 'rtl' : 'ltr'),
                            cardHeight: 130,
                            input: {
                                color: "#111827",
                                fontSize: "14px",
                                inputHeight: "42px",
                                borderColor: "#d4d4d8",
                                borderWidth: "1px",
                                borderRadius: "8px",
                                placeHolder: {
                                    holderName: "Name On Card",
                                    cardNumber: "Card Number",
                                    expiryDate: "MM / YY",
                                    securityCode: "CVV"
                                }
                            },
                            label: { display: false }
                        }
                    };

                    window.myFatoorah.init(mfConfig);
                };

                const scriptEl = document.getElementById('mf-session-js');
                if (scriptEl) {
                    scriptEl.addEventListener('error', showCardError);
                }

                if (window.myFatoorah) {
                    startEmbedded();
                } else {
                    scriptEl?.addEventListener('load', startEmbedded, { once: true });
                }

                document.getElementById('embedded-pay-now')?.addEventListener('click', function () {
                    if (!window.myFatoorah) {
                        showCardError();
                        return;
                    }

                    window.myFatoorah.submit()
                        .then(function () {
                            document.getElementById('embedded-exec-form')?.submit();
                        })
                        .catch(function () {
                            showCardError();
                        });
                });
            })();
        </script>
    @endif
</div>
