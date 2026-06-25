<?php

use App\Livewire\Concerns\InteractsWithPatientWalletPayment;
use App\Models\Appointment;
use App\Services\FollowUpPaymentCompletionService;
use App\Services\HyperpayCheckoutService;
use App\Services\StripeCheckoutService;
use App\Support\PaymentGateway;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use MyFatoorah\Library\API\Payment\MyFatoorahPayment;

new #[Layout('layouts::patient')] #[Title('Pay follow-up')] class extends Component
{
    use InteractsWithPatientWalletPayment;

    public Appointment $appointment;

    public string $paymentError = '';
    public bool $embeddedReady = false;
    public string $hyperpayCheckoutId = '';
    public string $hyperpayIntegrity = '';
    public string $hyperpayEntityId = '';
    public string $hyperpayEnv = '';
    public string $hyperpayCallbackUrl = '';

    public function mount(Appointment $appointment): void
    {
        if ($appointment->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $appointment->isPendingFollowUp()) {
            abort(404);
        }

        if ($appointment->patient_confirmed_at === null) {
            $this->redirect(route('patient.follow-up.confirm', $appointment));

            return;
        }

        $this->appointment = $appointment->load('doctor');
        $this->bootPatientWalletPayment((float) $this->appointment->wallet_amount);
        $this->persistWalletAmountOnFollowUp();

        if ($this->usesHyperPay() && $this->amountDue() > 0) {
            $this->initHyperpayCheckout();
        }
    }

    public function walletBalance(): float
    {
        return $this->patientWalletBalance();
    }

    public function walletApplied(): float
    {
        return $this->walletAppliedToward((float) $this->appointment->total);
    }

    public function amountDue(): float
    {
        return $this->amountDueAfterWallet((float) $this->appointment->total);
    }

    public function updatedUseWallet(): void
    {
        $this->persistWalletAmountOnFollowUp();

        if ($this->usesHyperPay() && $this->amountDue() > 0) {
            $this->initHyperpayCheckout();
        }
    }

    public function usesHyperPay(): bool
    {
        return PaymentGateway::isHyperPay();
    }

    public function usesMyFatoorah(): bool
    {
        return PaymentGateway::isMyFatoorah();
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
            $appointment = $this->appointment->fresh();

            if ($appointment === null) {
                return;
            }

            $result = $hyperpay->initFollowUpCheckout($appointment, FollowUpPaymentCompletionService::amountDue($appointment));

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

    private function persistWalletAmountOnFollowUp(): void
    {
        $this->appointment->wallet_amount = $this->walletApplied();
        $this->appointment->save();
    }

    public function payWithWalletOnly(): void
    {
        $this->appointment->wallet_amount = $this->walletApplied();
        $this->appointment->save();

        if ($this->amountDue() > 0) {
            $this->paymentError = __('patient_booking.wallet_insufficient');

            return;
        }

        $booked = app(FollowUpPaymentCompletionService::class)->completeWithWalletOnly($this->appointment->fresh(['doctor']));

        if ($booked !== null && $booked->status === 'new') {
            $this->redirect(route('patient.follow-up.payment.success', $booked));

            return;
        }

        $this->paymentError = __('patient_booking.payment_start_failed');
    }

    public function usesStripe(): bool
    {
        return PaymentGateway::isStripe();
    }

    public function paymentGatewayConfigured(): bool
    {
        return PaymentGateway::isConfigured();
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
        $this->appointment->wallet_amount = $this->walletApplied();
        $this->appointment->save();

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
            $appointment = $this->appointment->fresh();

            $session = $stripe->createFollowUpSession($appointment, FollowUpPaymentCompletionService::amountDue($appointment));

            $appointment->payment_session_id = (string) $session->id;
            $appointment->save();

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
        $this->appointment->wallet_amount = $this->walletApplied();
        $this->appointment->save();

        if ($this->amountDue() <= 0) {
            $this->payWithWalletOnly();

            return;
        }

        if (! PaymentGateway::isConfigured()) {
            $this->paymentError = __('patient_booking.payment_api_missing');

            return;
        }

        try {
            $mfConfig = [
                'apiKey' => config('myfatoorah.api_key'),
                'isTest' => (bool) config('myfatoorah.is_test'),
                'vcCode' => (string) config('myfatoorah.vc_code'),
            ];

            $appointment = $this->appointment->fresh();
            $mfObj = new MyFatoorahPayment($mfConfig);

            $paymentData = [
                'NotificationOption' => 'LNK',
                'CustomerName' => (string) ($appointment->patient_name ?: auth()->user()?->name ?: 'Patient'),
                'InvoiceValue' => FollowUpPaymentCompletionService::amountDue($appointment),
                'DisplayCurrencyIso' => 'SAR',
                'CallBackUrl' => route('patient.follow-up.payment.success', $appointment),
                'ErrorUrl' => route('patient.follow-up.payment.failed', $appointment),
                'CustomerReference' => 'FOLLOWUP-'.$appointment->id,
                'Language' => app()->getLocale() === 'ar' ? 'ar' : 'en',
            ];

            $data = $mfObj->sendPayment($paymentData);

            $invoiceUrl = $data->InvoiceURL ?? null;
            $invoiceId = $data->InvoiceId ?? null;

            $appointment->payment_invoice_id = $invoiceId !== null ? (string) $invoiceId : null;
            $appointment->payment_invoice_url = $invoiceUrl !== null ? (string) $invoiceUrl : null;
            $appointment->save();

            if (is_string($invoiceUrl) && $invoiceUrl !== '') {
                $this->redirect($invoiceUrl);

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

    public function formattedDate(): string
    {
        try {
            return Carbon::parse($this->appointment->appointment_date)
                ->locale(app()->getLocale())
                ->format(__('patient_booking.date_format'));
        } catch (\Throwable) {
            return (string) $this->appointment->appointment_date;
        }
    }

    public function formattedTime(): string
    {
        try {
            return Carbon::createFromFormat('H:i:s', (string) $this->appointment->start_time)
                ->timezone(config('app.timezone'))
                ->locale(app()->getLocale())
                ->translatedFormat('g:i a');
        } catch (\Throwable) {
            return (string) $this->appointment->start_time;
        }
    }
}; ?>

<div class="mx-auto max-w-xl space-y-6 px-4 py-8">
    @if (session('flash_payment'))
        <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ session('flash_payment') }}</p>
    @endif

    <div>
        <flux:heading size="xl" class="font-semibold text-[#10B981]">{{ __('patient.follow_up.pay_title') }}</flux:heading>
        <flux:text class="mt-2 text-zinc-600">{{ __('patient.follow_up.pay_subtitle') }}</flux:text>
    </div>

    <div class="rounded-2xl border border-zinc-200/90 bg-white p-5 shadow-sm space-y-3 text-sm">
        <div class="flex justify-between gap-3">
            <span class="text-zinc-600">{{ __('patient.follow_up.doctor') }}</span>
            <span class="font-semibold text-zinc-900">{{ $appointment->doctor?->displayName() }}</span>
        </div>
        <div class="flex justify-between gap-3">
            <span class="text-zinc-600">{{ __('patient.follow_up.date') }}</span>
            <span class="font-medium text-zinc-900">{{ $this->formattedDate() }}</span>
        </div>
        <div class="flex justify-between gap-3">
            <span class="text-zinc-600">{{ __('patient.follow_up.time') }}</span>
            <span class="font-medium text-zinc-900">{{ $this->formattedTime() }}</span>
        </div>
        <div class="flex justify-between gap-3 border-t border-zinc-100 pt-3 font-semibold">
            <span class="text-zinc-800">{{ __('patient_booking.total') }}</span>
            <span>{{ number_format((float) $appointment->total, 2) }} {{ config('currency.sa_riyal_symbol') }}</span>
        </div>

        @if ($this->walletBalance() > 0)
            <div class="mt-2 flex items-start justify-between gap-3 rounded-xl border border-[#3d5afe]/20 bg-[#3d5afe]/5 p-3">
                <div class="min-w-0 text-sm">
                    <span class="font-semibold text-zinc-800">{{ __('patient_booking.use_wallet') }}</span>
                    <span class="block text-xs text-zinc-500">
                        {{ __('patient_booking.wallet_balance') }}:
                        {{ number_format($this->walletBalance(), 2) }} {{ config('currency.sa_riyal_symbol') }}
                    </span>
                </div>
                <flux:switch wire:model.live="useWallet" />
            </div>

            @if ($this->walletApplied() > 0)
                <div class="flex justify-between gap-3">
                    <span class="text-zinc-600">{{ __('patient_booking.wallet_applied') }}</span>
                    <span class="font-medium text-emerald-600">- {{ number_format($this->walletApplied(), 2) }} {{ config('currency.sa_riyal_symbol') }}</span>
                </div>
                <div class="flex justify-between gap-3 border-t border-zinc-100 pt-2 font-semibold">
                    <span>{{ __('patient_booking.amount_due') }}</span>
                    <span>{{ number_format($this->amountDue(), 2) }} {{ config('currency.sa_riyal_symbol') }}</span>
                </div>
            @endif
        @endif
    </div>

    @if ($paymentError !== '')
        <p class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $paymentError }}</p>
    @endif

    <div class="flex flex-col gap-3">
        @if ($this->amountDue() <= 0 && $this->walletApplied() > 0)
            <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ __('patient_booking.wallet_covers_full') }}
            </p>
            <flux:button wire:click="payWithWalletOnly" variant="primary" class="w-full !bg-[#10B981] !text-white" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="payWithWalletOnly">{{ __('patient_booking.pay_with_wallet_only') }}</span>
                <span wire:loading wire:target="payWithWalletOnly">{{ __('patient_booking.payment_processing') }}</span>
            </flux:button>
        @elseif (! $this->paymentGatewayConfigured())
            <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                @if ($this->usesStripe())
                    {{ __('patient_booking.payment_stripe_missing') }}
                @elseif ($this->usesHyperPay())
                    {{ __('patient_booking.payment_hyperpay_missing') }}
                @else
                    {{ __('patient_booking.payment_api_missing') }}
                @endif
            </p>
        @elseif ($this->usesHyperPay() && $embeddedReady)
            @if ($this->walletApplied() > 0 && $this->amountDue() > 0)
                <p class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                    {{ __('patient_booking.wallet_partial_hint') }}
                </p>
            @endif

            @include('partials.hyperpay-widget', [
                'callbackUrl' => $hyperpayCallbackUrl,
                'checkoutId' => $hyperpayCheckoutId,
                'integrity' => $hyperpayIntegrity,
                'env' => $hyperpayEnv,
            ])

            <flux:button wire:click="initHyperpayCheckout" variant="ghost" class="w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="initHyperpayCheckout">{{ __('patient_booking.payment_retry') }}</span>
                <span wire:loading wire:target="initHyperpayCheckout">{{ __('patient_booking.payment_processing') }}</span>
            </flux:button>

            <flux:text class="text-center text-xs text-zinc-500">{{ __('patient_booking.payment_hyperpay_note') }}</flux:text>
        @else
            @if ($this->walletApplied() > 0 && $this->amountDue() > 0)
                <p class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                    {{ __('patient_booking.wallet_partial_hint') }}
                </p>
            @endif
            <flux:button wire:click="startCardPayment" variant="primary" class="w-full !bg-[#10B981] !text-white" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="startCardPayment">
                    @if ($this->walletApplied() > 0)
                        {{ __('patient_booking.pay_wallet_and_card', [
                            'wallet' => number_format($this->walletApplied(), 2),
                            'due' => number_format($this->amountDue(), 2),
                        ]) }}
                    @else
                        {{ $this->usesStripe() ? __('patient_booking.pay_now_stripe') : __('patient_booking.pay_now') }}
                    @endif
                </span>
                <span wire:loading wire:target="startCardPayment">{{ __('patient_booking.payment_processing') }}</span>
            </flux:button>
        @endif
    </div>
</div>
