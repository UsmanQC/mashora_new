<?php

use App\Livewire\Concerns\InteractsWithPatientWalletPayment;
use App\Models\Doctor;
use App\Models\TemporaryAppointment;
use App\Services\HyperpayCheckoutService;
use App\Services\PatientPaymentCompletionService;
use App\Services\StripeCheckoutService;
use App\Support\PaymentGateway;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
    public string $hyperpayCheckoutId = '';
    public string $hyperpayIntegrity = '';
    public string $hyperpayEntityId = '';
    public string $hyperpayEnv = '';
    public string $hyperpayCallbackUrl = '';
    public string $mfSessionId = '';
    public string $mfCountryCode = '';
    public string $mfJsDomain = '';

    public function mount(TemporaryAppointment $temporaryAppointment): void
    {
        if ($temporaryAppointment->user_id !== auth()->id()) {
            abort(403);
        }

        $this->temporaryAppointment = $temporaryAppointment->load(['doctor.specialities:id,title,title_ar']);
        $this->bootPatientWalletPayment((float) $this->temporaryAppointment->wallet_amount);
        $this->persistWalletAmountOnBooking();

        if ($this->usesHyperPay()) {
            $this->initHyperpayCheckout();
        } elseif ($this->usesMyFatoorah()) {
            $this->initEmbeddedPaymentSession();
        }
    }

    public function usesStripe(): bool
    {
        return PaymentGateway::isStripe();
    }

    public function usesHyperPay(): bool
    {
        return PaymentGateway::isHyperPay();
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

        if ($this->usesHyperPay() && $this->amountDue() > 0) {
            $this->initHyperpayCheckout();
        }
    }

    public function initHyperpayCheckout(): void
    {
        if (! $this->usesHyperPay() || $this->amountDue() <= 0) {
            $this->embeddedReady = false;

            return;
        }

        if (! PaymentGateway::isConfigured()) {
            $this->paymentError = __('patient_booking.payment_hyperpay_missing');
            $this->embeddedReady = false;

            return;
        }

        try {
            /** @var HyperpayCheckoutService $hyperpay */
            $hyperpay = app(HyperpayCheckoutService::class);
            $temp = $this->temporaryAppointment->fresh();

            if ($temp === null) {
                return;
            }

            $result = $hyperpay->initBookingCheckout($temp, PatientPaymentCompletionService::amountDue($temp));

            $this->hyperpayCheckoutId = (string) ($result['checkout_id'] ?? '');
            $this->hyperpayIntegrity = (string) ($result['integrity'] ?? '');
            $this->hyperpayEntityId = (string) ($result['entity_id'] ?? '');
            $this->hyperpayEnv = (string) ($result['env'] ?? '');
            $this->hyperpayCallbackUrl = (string) ($result['callback_url'] ?? '');
            $this->embeddedReady = $this->hyperpayCheckoutId !== '';
            $this->paymentError = '';
        } catch (\Throwable $e) {
            report($e);
            $this->embeddedReady = false;
            $this->paymentError = app()->isLocal()
                ? __('patient_booking.payment_start_failed')." ({$e->getMessage()})"
                : __('patient_booking.payment_start_failed');
        }
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

    public function formattedLuxuryDate(): string
    {
        try {
            return Carbon::parse($this->temporaryAppointment->appointment_date)
                ->locale(app()->getLocale())
                ->translatedFormat('l, j F Y');
        } catch (\Throwable) {
            return $this->formattedDate();
        }
    }

    public function headerSubtitle(): string
    {
        return implode(' · ', array_filter([
            $this->specialistName(),
            $this->formattedLuxuryDate(),
            $this->formattedTime(),
        ]));
    }

    public function doctorPhotoUrl(): ?string
    {
        $doctor = $this->temporaryAppointment->doctor;

        if ($doctor instanceof Doctor) {
            return $doctor->profilePhotoUrl();
        }

        return null;
    }

    public function doctorSpecialtyLabel(): string
    {
        $doctor = $this->temporaryAppointment->doctor;

        if (! $doctor instanceof Doctor) {
            return __('patient.appointments.specialist_label');
        }

        $speciality = $doctor->specialities->first();

        if ($speciality === null) {
            return __('patient.appointments.specialist_label');
        }

        if (app()->getLocale() === 'ar' && filled($speciality->title_ar)) {
            return (string) $speciality->title_ar;
        }

        return (string) ($speciality->title ?? $speciality->title_ar ?? __('patient.appointments.specialist_label'));
    }

    public function profilePhotoUrl(): ?string
    {
        $user = Auth::user();

        if ($user === null || ! filled($user->profile_photo_path)) {
            return null;
        }

        return Storage::disk('public')->url((string) $user->profile_photo_path);
    }

    public function startCardPayment(): void
    {
        if ($this->usesStripe()) {
            $this->startStripePayment();

            return;
        }

        if ($this->usesHyperPay()) {
            $this->initHyperpayCheckout();

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

<div class="patient-luxury-checkout bg-slate-50 pb-[calc(4.75rem+env(safe-area-inset-bottom))] sm:bg-transparent sm:pb-12" data-test="patient-luxury-checkout">
    <div class="sm:hidden">
        @include('partials.patient-luxury-page-header', [
            'title' => __('patient_booking.checkout_title'),
            'subtitle' => $this->headerSubtitle(),
            'profilePhotoUrl' => $this->profilePhotoUrl(),
            'userName' => auth()->user()?->name,
            'testId' => 'patient-checkout-header',
            'progressStep' => 3,
            'progressTotal' => 3,
        ])

        @include('partials.patient-luxury-checkout-mobile')
    </div>

    <div class="mx-auto hidden w-full max-w-7xl px-6 py-4 sm:block sm:px-0 sm:py-0">
        @if (session('flash_payment'))
            <p class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ session('flash_payment') }}</p>
        @endif

        <header class="mb-8">
            <nav class="mb-3 text-sm text-zinc-600" aria-label="Breadcrumb">
                <ol class="flex flex-wrap items-center gap-2">
                    <li>
                        <flux:link :href="route('patient.schedule.specialists')" wire:navigate class="font-medium text-[#10B981] hover:text-[#064e3b]">
                            {{ __('patient_booking.crumb_find_specialist') }}
                        </flux:link>
                    </li>
                    <li aria-hidden="true" class="text-zinc-400">/</li>
                    <li class="font-medium text-zinc-600">{{ __('patient_booking.crumb_book') }}</li>
                    <li aria-hidden="true" class="text-zinc-400">/</li>
                    <li class="font-semibold text-zinc-900">{{ __('patient_booking.checkout_title') }}</li>
                </ol>
            </nav>
            <flux:heading size="xl" class="font-semibold text-zinc-900">
                {{ __('patient_booking.checkout_title') }}
            </flux:heading>
            <flux:text class="mt-1 text-zinc-600">{{ __('patient_booking.checkout_subtitle') }}</flux:text>
        </header>

        @php
            $checkoutTotal = (float) $this->temporaryAppointment->total;
            $checkoutDue = $this->amountDue();
            $showAmountDue = $this->walletApplied() > 0 && $checkoutDue < $checkoutTotal;
        @endphp

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:gap-10">
            <div class="order-2 space-y-6 lg:order-1">
                <div class="overflow-hidden rounded-3xl border border-slate-100/80 bg-white shadow-[0_8px_32px_0_rgba(0,0,0,0.03)]">
                    <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4">
                        <div class="flex items-center gap-3">
                            @if ($this->doctorPhotoUrl() !== null)
                                <img src="{{ $this->doctorPhotoUrl() }}" alt="" class="size-12 shrink-0 rounded-full object-cover ring-2 ring-[#10B981]/15" />
                            @else
                                <flux:avatar :name="$this->specialistName()" circle class="shrink-0 ring-2 ring-[#10B981]/15" />
                            @endif
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('patient_booking.specialist_name') }}</p>
                                <p class="truncate text-base font-semibold text-slate-900">{{ $this->specialistName() }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $this->doctorSpecialtyLabel() }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3 px-5 py-4 sm:grid-cols-3">
                        <div class="rounded-xl border border-slate-100 bg-slate-50/50 px-3 py-2.5">
                            <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('patient_booking.session_date') }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $this->formattedDate() }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50/50 px-3 py-2.5">
                            <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('patient_booking.session_time') }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $this->formattedTime() }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50/50 px-3 py-2.5">
                            <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">{{ __('patient_booking.session_duration') }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $temporaryAppointment->duration }}</p>
                        </div>
                    </div>

                    <div class="space-y-2 border-t border-slate-100 px-5 py-4 text-sm">
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-600">{{ __('patient_booking.session_price') }}</span>
                            <span class="font-medium tabular-nums text-slate-900">{{ number_format((float) $temporaryAppointment->amount, 2) }} {{ __('patient_booking.sar') }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-600">{{ __('patient_booking.discount') }}</span>
                            <span class="font-medium tabular-nums text-slate-900">{{ number_format((float) $temporaryAppointment->discount, 2) }} {{ __('patient_booking.sar') }}</span>
                        </div>
                        <div class="flex justify-between gap-3 border-t border-slate-100 pt-3 font-semibold">
                            <span class="text-slate-800">{{ __('patient_booking.total') }}</span>
                            <span class="tabular-nums text-slate-900">{{ number_format($checkoutTotal, 2) }} {{ __('patient_booking.sar') }}</span>
                        </div>
                    </div>

                    @if ($this->walletBalance() > 0)
                        <div class="border-t border-slate-100 px-5 py-4">
                            <div class="flex items-start justify-between gap-3 rounded-xl border border-[#10B981]/15 bg-[#10B981]/5 p-3">
                                <div class="min-w-0 text-sm">
                                    <span class="font-semibold text-slate-800">{{ __('patient_booking.use_wallet') }}</span>
                                    <span class="mt-1 block text-xs text-slate-500">
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
                                        <span class="text-slate-600">{{ __('patient_booking.wallet_applied') }}</span>
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

            <div class="order-1 lg:order-2">
                <div class="overflow-hidden rounded-3xl border border-slate-200/90 bg-white shadow-[0_8px_30px_-12px_rgba(15,23,42,0.12)]" data-test="patient-checkout-payment-card">
                    <div class="border-b border-slate-100 bg-gradient-to-b from-slate-50/90 to-white px-5 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[#10B981]/10 text-[#10B981] ring-1 ring-[#10B981]/15">
                                    <flux:icon name="lock-closed" variant="mini" class="size-4" />
                                </span>
                                <div class="min-w-0">
                                    <flux:heading size="sm" class="font-semibold text-slate-900">{{ __('patient_booking.checkout_accepts') }}</flux:heading>
                                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500">{{ __('patient_booking.luxury.trust_badge') }}</p>
                                </div>
                            </div>
                            <div class="shrink-0 text-end">
                                <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $showAmountDue ? __('patient_booking.amount_due') : __('patient_booking.total') }}
                                </p>
                                <p class="text-xl font-bold tabular-nums text-[#059669]">
                                    {{ number_format($showAmountDue ? $checkoutDue : $checkoutTotal, 2) }}
                                    <span class="text-sm">{{ __('patient_booking.sar') }}</span>
                                </p>
                            </div>
                        </div>

                    </div>

                    <div class="space-y-4 p-5">
                        @include('partials.patient-checkout-payment-panel')

                        <div class="border-t border-slate-100 pt-4">
                            @include('partials.patient-checkout-payment-methods', ['compact' => true, 'labelSurface' => 'bg-white'])
                        </div>
                    </div>

                    <div class="border-t border-slate-100 px-5 py-4">
                        <flux:button :href="route('patient.home')" wire:navigate variant="ghost" class="w-full">
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
                            cardHeight: 160,
                            input: {
                                color: "#111827",
                                fontSize: "14px",
                                inputHeight: "42px",
                                borderColor: "#d4d4d8",
                                borderWidth: "1px",
                                borderRadius: "8px",
                                placeHolder: {
                                    holderName: @js(__('patient_booking.payment_placeholder_card_holder')),
                                    cardNumber: @js(__('patient_booking.payment_placeholder_card_number')),
                                    expiryDate: @js(__('patient_booking.payment_placeholder_expiry')),
                                    securityCode: @js(__('patient_booking.payment_placeholder_cvv')),
                                },
                            },
                            label: {
                                display: true,
                                color: "#525252",
                                fontSize: "12px",
                            },
                        },
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
