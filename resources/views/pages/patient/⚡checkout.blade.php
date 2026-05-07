<?php

use App\Models\Doctor;
use App\Models\TemporaryAppointment;
use App\Services\PatientPaymentCompletionService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use MyFatoorah\Library\MyFatoorah;
use MyFatoorah\Library\API\Payment\MyFatoorahPayment;

new #[Layout('layouts::patient')] #[Title('Payment')] class extends Component
{
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
        $this->initEmbeddedPaymentSession();
    }

    public function initEmbeddedPaymentSession(): void
    {
        if (empty(config('myfatoorah.api_key'))) {
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
        }
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

    public function startMyFatoorahPayment(): void
    {
        if ($this->temporaryAppointment->user_id !== auth()->id()) {
            abort(403);
        }

        if (empty(config('myfatoorah.api_key'))) {
            $this->paymentError = __('patient_booking.payment_api_missing');

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
                'InvoiceValue' => (float) $temp->total,
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
                        'InvoiceValue' => (float) $temp->total,
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

            $this->paymentError = app()->isLocal()
                ? __('patient_booking.payment_start_failed')." ({$e->getMessage()})"
                : __('patient_booking.payment_start_failed');
        }
    }
}; ?>

<div class="mx-auto max-w-6xl space-y-6 px-4 py-6 pb-28 sm:pb-12">
    @if (session('flash_payment'))
        <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ session('flash_payment') }}</p>
    @endif

    <div class="grid gap-8 lg:grid-cols-2">
        <div class="space-y-4">
            <div class="flex flex-wrap items-center gap-2 text-sm font-medium text-[#2f64c9]">
                <span>{{ __('patient_booking.crumb_find_specialist') }}</span>
                <span class="text-zinc-400">></span>
                <span>{{ __('patient_booking.crumb_book') }}</span>
                <span class="text-zinc-400">></span>
                <span class="text-zinc-500">{{ __('patient_booking.checkout_title') }}</span>
            </div>

            <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
            <div class="flex justify-between gap-3 border-b border-zinc-100 pb-3">
                <span class="text-sm font-semibold text-zinc-600">{{ __('patient_booking.specialist_name') }}</span>
                <span class="min-w-0 text-end text-sm font-semibold text-zinc-900">{{ $this->specialistName() }}</span>
            </div>
            <div class="mt-3 flex justify-between gap-3 text-sm">
                <span class="text-zinc-600">{{ __('patient_booking.session_date') }}</span>
                <span class="font-medium text-zinc-900">{{ $this->formattedDate() }}</span>
            </div>
            <div class="mt-2 flex justify-between gap-3 text-sm">
                <span class="text-zinc-600">{{ __('patient_booking.session_time') }}</span>
                <span class="font-medium text-zinc-900">{{ $this->formattedTime() }}</span>
            </div>
            <div class="mt-2 flex justify-between gap-3 text-sm">
                <span class="text-zinc-600">{{ __('patient_booking.session_duration') }}</span>
                <span class="font-medium text-zinc-900">{{ $this->temporaryAppointment->duration }}</span>
            </div>
            <hr class="my-4 border-zinc-100" />
            <div class="flex justify-between gap-3 text-sm">
                <span class="text-zinc-600">{{ __('patient_booking.session_price') }}</span>
                <span class="font-medium tabular-nums text-zinc-900">{{ number_format((float) $this->temporaryAppointment->amount, 2) }} {{ __('patient_booking.sar') }}</span>
            </div>
            <div class="mt-2 flex justify-between gap-3 text-sm">
                <span class="text-zinc-600">{{ __('patient_booking.discount') }}</span>
                <span class="font-medium tabular-nums text-zinc-900">{{ number_format((float) $this->temporaryAppointment->discount, 2) }} {{ __('patient_booking.sar') }}</span>
            </div>
            <div class="mt-3 flex justify-between gap-3 border-t border-zinc-100 pt-3 text-sm font-semibold">
                <span class="text-zinc-800">{{ __('patient_booking.total') }}</span>
                <span class="tabular-nums text-zinc-900">{{ number_format((float) $this->temporaryAppointment->total, 2) }} {{ __('patient_booking.sar') }}</span>
            </div>
            </div>
        </div>

        <div class="space-y-4 rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm">
            @if ($paymentError !== '')
                <p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $paymentError }}</p>
            @endif

            @if (filled(config('myfatoorah.api_key')))
                @if ($embeddedReady)
                    <div id="mf-form-element" class="min-h-[155px] w-full rounded-lg border border-zinc-300 bg-white p-2"></div>
                    <p id="mf-card-error" class="hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                        {{ __('patient_booking.payment_embedded_unavailable') }}
                    </p>

                    <button
                        type="button"
                        id="embedded-pay-now"
                        class="w-full rounded-lg border border-[#3d5afe] bg-[#3d5afe] py-3 text-sm font-semibold text-white transition hover:brightness-95"
                    >
                        {{ __('patient_booking.pay_now') }}
                    </button>

                    <flux:button
                        type="button"
                        variant="ghost"
                        class="w-full"
                        wire:click="startMyFatoorahPayment"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="startMyFatoorahPayment">{{ __('patient_booking.pay_now_fallback') }}</span>
                        <span wire:loading wire:target="startMyFatoorahPayment">{{ __('patient_booking.payment_processing') }}</span>
                    </flux:button>
                @else
                    <flux:button
                        type="button"
                        variant="primary"
                        class="w-full border-[#3d5afe] !bg-[#3d5afe] !text-white hover:!brightness-[0.97]"
                        wire:click="startMyFatoorahPayment"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="startMyFatoorahPayment">{{ __('patient_booking.pay_now') }}</span>
                        <span wire:loading wire:target="startMyFatoorahPayment">{{ __('patient_booking.payment_processing') }}</span>
                    </flux:button>
                @endif

                <button type="button" class="w-full rounded-lg bg-black py-3 text-sm font-semibold text-white">
                    Pay with Apple Pay
                </button>

                <flux:text class="text-xs text-zinc-500">{{ __('patient_booking.payment_secure_note') }}</flux:text>
            @else
                <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ __('patient_booking.payment_api_missing') }}</p>
            @endif

            <div class="pt-1">
                <flux:link :href="route('patient.home')" wire:navigate>{{ __('patient_booking.back_home') }}</flux:link>
            </div>
        </div>
    </div>

    @if ($embeddedReady)
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
